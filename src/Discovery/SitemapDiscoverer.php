<?php

namespace LlmsGenerator\Discovery;

use LlmsGenerator\Fetcher\FetcherInterface;
use LlmsGenerator\Page;
use RuntimeException;
use SimpleXMLElement;

class SitemapDiscoverer
{
    private FetcherInterface $fetcher;

    public function __construct(FetcherInterface $fetcher)
    {
        $this->fetcher = $fetcher;
    }

    public function discover(string $sitemapUrl, int $maxPages = 0): array
    {
        $xmlContent = $this->fetcher->fetch($sitemapUrl);

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlContent);
        if ($xml === false) {
            throw new RuntimeException("Failed to parse sitemap XML from {$sitemapUrl}");
        }

        $namespaces = $xml->getNamespaces(true);
        $ns = $namespaces[''] ?? null;

        $pages = [];

        if (isset($xml->url)) {
            foreach ($xml->url as $urlElement) {
                $loc = (string) $urlElement->loc;
                if ($loc === '') {
                    continue;
                }
                $pages[] = new Page($loc);
                if ($maxPages > 0 && count($pages) >= $maxPages) {
                    break;
                }
            }
        } elseif (isset($xml->sitemap)) {
            foreach ($xml->sitemap as $sitemapElement) {
                $loc = (string) $sitemapElement->loc;
                if ($loc === '') {
                    continue;
                }
                $remaining = $maxPages > 0 ? $maxPages - count($pages) : 0;
                $subPages = $this->discover($loc, $remaining);
                $pages = array_merge($pages, $subPages);
                if ($maxPages > 0 && count($pages) >= $maxPages) {
                    break;
                }
            }
        }

        return $pages;
    }
}
