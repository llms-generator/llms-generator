<?php

namespace LlmsGenerator\Converter;

use League\HTMLToMarkdown\HtmlConverter;

class HtmlToMarkdownConverter implements ConverterInterface
{
    private HtmlConverter $converter;

    public function __construct(array $options = [])
    {
        $this->converter = new HtmlConverter($options);
    }

    public function convert(string $html): string
    {
        return $this->converter->convert($html);
    }
}
