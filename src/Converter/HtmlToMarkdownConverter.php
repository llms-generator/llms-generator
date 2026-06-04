<?php

namespace LlmsGenerator\Converter;

use League\HTMLToMarkdown\HtmlConverter;

class HtmlToMarkdownConverter implements ConverterInterface
{
    private HtmlConverter $converter;

    public function __construct(array $options = [])
    {
        $this->converter = new HtmlConverter($options + ['header_style' => 'atx', 'strip_tags' => true]);
    }

    public function convert(string $html): string
    {
        $markdown = $this->converter->convert($html);

        $lines = explode("\n", $markdown);
        $result = [];
        $prevBlank = false;
        $inFence = false;

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if (preg_match('/^```/', $trimmed)) {
                $inFence = !$inFence;
            }

            if ($inFence) {
                $result[] = $line;
            } elseif ($trimmed === '') {
                if (!$prevBlank) {
                    $result[] = '';
                    $prevBlank = true;
                }
            } else {
                $result[] = ltrim($line);
                $prevBlank = false;
            }
        }

        return html_entity_decode(implode("\n", $result), ENT_QUOTES, 'UTF-8');
    }
}
