<?php

namespace LlmsGenerator\Tests;

use LlmsGenerator\Config;
use LlmsGenerator\Converter\ConverterInterface;
use LlmsGenerator\Dumper\FileDumper;
use LlmsGenerator\Fetcher\FetcherInterface;
use LlmsGenerator\LlmsGenerator;
use PHPUnit\Framework\TestCase;

class LlmsGeneratorTest extends TestCase
{
    private function createMockFetcher(): FetcherInterface
    {
        return new class implements FetcherInterface {
            public function fetch(string $url): string
            {
                $path = parse_url($url, PHP_URL_PATH);

                return match ($path) {
                    '/docs/start' => '<html><head><title>Getting Started</title></head><body><h1>Start</h1><p>Content</p></body></html>',
                    '/docs/api' => '<html><head><title>API Reference</title></head><body><h1>API</h1><p>API docs</p></body></html>',
                    '/blog/post' => '<html><head><title>Blog Post</title></head><body><h1>Post</h1><p>Blog content</p></body></html>',
                    default => '<html><head><title>Untitled</title></head><body><p>Default</p></body></html>',
                };
            }
        };
    }

    private function createMockConverter(): ConverterInterface
    {
        return new class implements ConverterInterface {
            public function convert(string $html): string
            {
                if (str_contains($html, 'Content')) return 'Start content.';
                if (str_contains($html, 'API docs')) return 'API content.';
                if (str_contains($html, 'Blog content')) return 'Blog content.';
                return 'Default content.';
            }
        };
    }

    public function testAddPageAndGenerate()
    {
        $config = new Config([
            'base_url' => 'https://example.com',
            'title' => 'My Project',
            'description' => 'A test project',
        ]);

        $generator = new LlmsGenerator(
            $config,
            $this->createMockFetcher(),
            $this->createMockConverter(),
            new FileDumper(sys_get_temp_dir())
        );

        $generator
            ->addPage('/docs/start', ['section' => 'Docs', 'notes' => 'How to start'])
            ->addPage('/docs/api', ['section' => 'Docs'])
            ->addPage('/blog/post', ['section' => 'Blog']);

        $result = $generator->generate();

        $this->assertArrayHasKey('llms.txt', $result);
        $this->assertArrayHasKey('llms-full.txt', $result);

        $llmsTxt = file_get_contents($result['llms.txt']);
        $llmsFull = file_get_contents($result['llms-full.txt']);

        $this->assertStringContainsString('# My Project', $llmsTxt);
        $this->assertStringContainsString('> A test project', $llmsTxt);
        $this->assertStringContainsString('## Docs', $llmsTxt);
        $this->assertStringContainsString('## Blog', $llmsTxt);
        $this->assertStringContainsString('[Getting Started]', $llmsTxt);
        $this->assertStringContainsString('How to start', $llmsTxt);

        $this->assertStringContainsString('# Getting Started', $llmsFull);
        $this->assertStringContainsString('Source: https://example.com/docs/start', $llmsFull);
        $this->assertStringContainsString('Start content.', $llmsFull);

        unlink($result['llms.txt']);
        unlink($result['llms-full.txt']);
    }

    public function testTitleFallbackFromHtml()
    {
        $config = new Config([
            'base_url' => 'https://example.com',
            'title' => 'Test',
        ]);

        $generator = new LlmsGenerator(
            $config,
            $this->createMockFetcher(),
            $this->createMockConverter(),
            new FileDumper(sys_get_temp_dir())
        );

        $generator->addPage('/docs/start');

        $result = $generator->generate();
        $llmsTxt = file_get_contents($result['llms.txt']);

        $this->assertStringContainsString('[Getting Started]', $llmsTxt);

        unlink($result['llms.txt']);
        unlink($result['llms-full.txt']);
    }

    public function testEmptyPagesThrowsException()
    {
        $this->expectException(\RuntimeException::class);

        $config = new Config(['title' => 'Test']);
        $generator = new LlmsGenerator($config);
        $generator->generate();
    }
}
