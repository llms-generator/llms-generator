<?php

namespace LlmsGenerator\Sanitizer;

interface SanitizerInterface
{
    public function sanitize(string $html): string;
}
