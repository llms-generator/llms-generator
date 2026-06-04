<?php

namespace LlmsGenerator;

class Config
{
    private string $baseUrl;
    private string $title;
    private string $description;
    private string $details;
    private string $outputDir;
    private string $defaultSection;
    private int $httpTimeout;

    public function __construct(array $options = [])
    {
        $this->baseUrl = rtrim($options['base_url'] ?? getcwd(), '/');
        $this->title = $options['title'] ?? 'Untitled';
        $this->description = $options['description'] ?? '';
        $this->details = $options['details'] ?? '';
        $this->outputDir = $options['output_dir'] ?? getcwd();
        $this->defaultSection = $options['default_section'] ?? 'Pages';
        $this->httpTimeout = $options['http_timeout'] ?? 30;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getDetails(): string
    {
        return $this->details;
    }

    public function getOutputDir(): string
    {
        return $this->outputDir;
    }

    public function getDefaultSection(): string
    {
        return $this->defaultSection;
    }

    public function getHttpTimeout(): int
    {
        return $this->httpTimeout;
    }
}
