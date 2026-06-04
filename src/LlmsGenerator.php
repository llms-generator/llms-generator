<?php

namespace LlmsGenerator;

use LlmsGenerator\Converter\ConverterInterface;
use LlmsGenerator\Converter\HtmlToMarkdownConverter;
use LlmsGenerator\Discovery\SitemapDiscoverer;
use LlmsGenerator\Dumper\FileDumper;
use LlmsGenerator\Fetcher\FetcherInterface;
use LlmsGenerator\Fetcher\HttpFetcher;
use LlmsGenerator\Sanitizer\HtmlSanitizer;
use LlmsGenerator\Sanitizer\SanitizerInterface;
use RuntimeException;

class LlmsGenerator
{
    private Config $config;
    private FetcherInterface $fetcher;
    private ConverterInterface $converter;
    private FileDumper $dumper;
    private SanitizerInterface $sanitizer;

    /** @var Page[] */
    private array $pages = [];

    public function __construct(
        Config $config,
        ?FetcherInterface $fetcher = null,
        ?ConverterInterface $converter = null,
        ?FileDumper $dumper = null,
        ?SanitizerInterface $sanitizer = null
    ) {
        $this->config = $config;
        $this->fetcher = $fetcher ?: new HttpFetcher(null, null, $config->getHttpTimeout());
        $this->converter = $converter ?: new HtmlToMarkdownConverter();
        $this->dumper = $dumper ?: new FileDumper($config->getOutputDir());
        $this->sanitizer = $sanitizer ?? new HtmlSanitizer();
    }

    public function addPage(string $url, array $options = []): self
    {
        $this->pages[] = new Page($url, $options);
        return $this;
    }

    public function discoverFromSitemap(?string $sitemapUrl = null, int $maxPages = 0): self
    {
        $discoverer = new SitemapDiscoverer($this->fetcher);
        $pages = $discoverer->discover($sitemapUrl ?: $this->config->getBaseUrl() . '/sitemap.xml', $maxPages);

        foreach ($pages as $page) {
            $this->pages[] = $page;
        }

        return $this;
    }

    public function generate(): array
    {
        if (empty($this->pages)) {
            throw new RuntimeException('No pages added. Call addPage() or discoverFromSitemap() first.');
        }

        foreach ($this->pages as $page) {
            $fullUrl = $this->resolveUrl($page->getUrl());
            $rawHtml = $this->fetcher->fetch($fullUrl);

            if ($page->getTitle() === null) {
                $page->setTitle($this->extractTitle($rawHtml, $fullUrl));
            }

            if ($page->getSection() === null) {
                $page->setSection($this->deriveSection($page->getUrl()));
            }

            $rawHtml = $this->sanitizer->sanitize($rawHtml);

            $markdown = $this->converter->convert($rawHtml);
            $page->setContent($markdown);
        }

        $llmsTxt = $this->buildLlmsTxt();
        $llmsFullTxt = $this->buildLlmsFullTxt();

        $llmsPath = $this->dumper->dump('llms.txt', $llmsTxt);
        $llmsFullPath = $this->dumper->dump('llms-full.txt', $llmsFullTxt);

        return [
            'llms.txt' => $llmsPath,
            'llms-full.txt' => $llmsFullPath,
        ];
    }

    private function buildLlmsTxt(): string
    {
        $lines = [];

        $lines[] = '# ' . $this->config->getTitle();

        if ($this->config->getDescription() !== '') {
            $lines[] = '';
            $lines[] = '> ' . $this->config->getDescription();
        }

        if ($this->config->getDetails() !== '') {
            $lines[] = '';
            $lines[] = $this->config->getDetails();
        }

        $sections = $this->groupPagesBySection();

        foreach ($sections as $sectionName => $sectionPages) {
            $lines[] = '';
            $lines[] = '## ' . $sectionName;
            $lines[] = '';

            foreach ($sectionPages as $page) {
                $fullUrl = $this->resolveUrl($page->getUrl());
                $line = '- [' . $page->getTitle() . '](' . $fullUrl . ')';

                if ($page->getNotes() !== null) {
                    $line .= ': ' . $page->getNotes();
                }

                $lines[] = $line;
            }
        }

        $lines[] = '';

        return implode("\n", $lines);
    }

    private function buildLlmsFullTxt(): string
    {
        $parts = [];

        $parts[] = '# ' . $this->config->getTitle();
        if ($this->config->getDescription() !== '') {
            $parts[] = '> ' . $this->config->getDescription();
        }
        if ($this->config->getDetails() !== '') {
            $parts[] = $this->config->getDetails();
        }

        foreach ($this->pages as $page) {
            $fullUrl = $this->resolveUrl($page->getUrl());

            $parts[] = '---';
            $parts[] = '# ' . $page->getTitle();
            $parts[] = 'URL: ' . $fullUrl;
            $parts[] = trim($page->getContent());
        }

        return implode("\n\n", $parts) . "\n";
    }

    private function groupPagesBySection(): array
    {
        $sections = [];

        foreach ($this->pages as $page) {
            $section = $page->getSection() ?? $this->config->getDefaultSection();
            $sections[$section][] = $page;
        }

        return $sections;
    }

    private function resolveUrl(string $url): string
    {
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        return $this->config->getBaseUrl() . '/' . ltrim($url, '/');
    }

    private function extractTitle(string $html, string $fallbackUrl): string
    {
        if (preg_match('/<title[^>]*>(.*?)<\/title>/si', $html, $matches)) {
            $title = trim(html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8'));
            if ($title !== '') {
                return $title;
            }
        }

        $path = parse_url($fallbackUrl, PHP_URL_PATH);
        $basename = basename($path);
        $name = str_replace(['-', '_'], ' ', pathinfo($basename, PATHINFO_FILENAME));

        return $name ?: 'Untitled';
    }

    private function deriveSection(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $path = trim($path, '/');

        if ($path === '') {
            return $this->config->getDefaultSection();
        }

        $parts = explode('/', $path);
        $first = $parts[0];

        if ($first === '') {
            return $this->config->getDefaultSection();
        }

        return ucfirst($first);
    }
}
