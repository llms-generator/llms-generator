<?php

namespace LlmsGenerator;

class Page
{
    private string $url;
    private ?string $title;
    private ?string $section;
    private ?string $notes;
    private ?string $content;

    public function __construct(string $url, array $options = [])
    {
        $this->url = $url;
        $this->title = $options['title'] ?? null;
        $this->section = $options['section'] ?? null;
        $this->notes = $options['notes'] ?? null;
        $this->content = null;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getSection(): ?string
    {
        return $this->section;
    }

    public function setSection(string $section): void
    {
        $this->section = $section;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }
}
