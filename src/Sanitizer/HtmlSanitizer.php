<?php

namespace LlmsGenerator\Sanitizer;

use DOMDocument;
use DOMXPath;

class HtmlSanitizer implements SanitizerInterface
{
    private const REMOVE_TAGS = [
        'nav', 'footer', 'aside', 'script', 'style', 'hr',
        'select', 'option', 'input', 'button', 'svg',
    ];

    private const REMOVE_CLASSES = ['ad', 'advertisement'];

    public function sanitize(string $html): string
    {
        $dom = new DOMDocument();

        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();

        $body = $dom->getElementsByTagName('body')->item(0);
        if ($body === null) {
            return $html;
        }

        $xpath = new DOMXPath($dom);

        $this->removeElementsByTag($body, self::REMOVE_TAGS);
        $this->removeTablesWithSpan($xpath, $body);
        $this->removeElementsByClass($xpath, $body, self::REMOVE_CLASSES);
        $this->replaceImagesWithAltText($body);

        $innerHtml = $this->getInnerHtml($body);

        return $innerHtml;
    }

    private function removeElementsByTag(\DOMNode $parent, array $tags): void
    {
        foreach ($tags as $tag) {
            $nodes = $parent->getElementsByTagName($tag);
            $toRemove = [];

            foreach ($nodes as $node) {
                $toRemove[] = $node;
            }

            foreach ($toRemove as $node) {
                $node->parentNode->removeChild($node);
            }
        }
    }

    private function removeTablesWithSpan(DOMXPath $xpath, \DOMNode $context): void
    {
        $tables = $xpath->query('.//table[@colspan or @rowspan]', $context);

        if ($tables === false) {
            return;
        }

        $toRemove = [];

        foreach ($tables as $table) {
            $toRemove[] = $table;
        }

        foreach ($toRemove as $table) {
            $table->parentNode->removeChild($table);
        }
    }

    private function removeElementsByClass(DOMXPath $xpath, \DOMNode $context, array $classes): void
    {
        $queries = [];

        foreach ($classes as $class) {
            $queries[] = "contains(concat(' ', normalize-space(@class), ' '), ' {$class} ')";
        }

        $expr = './/*[' . implode(' or ', $queries) . ']';
        $nodes = $xpath->query($expr, $context);

        if ($nodes === false) {
            return;
        }

        $toRemove = [];

        foreach ($nodes as $node) {
            $toRemove[] = $node;
        }

        foreach ($toRemove as $node) {
            $node->parentNode->removeChild($node);
        }
    }

    private function replaceImagesWithAltText(\DOMNode $parent): void
    {
        $images = $parent->getElementsByTagName('img');
        $toReplace = [];

        foreach ($images as $img) {
            $toReplace[] = $img;
        }

        foreach ($toReplace as $img) {
            $text = $img->getAttribute('alt');
            if ($text === '') {
                $text = $img->getAttribute('title');
            }
            if ($text === '') {
                $img->parentNode->removeChild($img);
            } else {
                $textNode = $img->ownerDocument->createTextNode($text);
                $img->parentNode->replaceChild($textNode, $img);
            }
        }
    }

    private function getInnerHtml(\DOMNode $node): string
    {
        $html = '';

        foreach ($node->childNodes as $child) {
            $html .= $child->ownerDocument->saveHTML($child);
        }

        return $html;
    }
}
