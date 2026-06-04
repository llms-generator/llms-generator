<?php

namespace LlmsGenerator\Fetcher;

interface FetcherInterface
{
    public function fetch(string $url): string;
}
