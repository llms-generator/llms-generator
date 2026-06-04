<?php

namespace LlmsGenerator\Tests;

use LlmsGenerator\Page;
use PHPUnit\Framework\TestCase;

class PageTest extends TestCase
{
    public function testMinimalPage()
    {
        $page = new Page('/docs/foo');
        $this->assertEquals('/docs/foo', $page->getUrl());
        $this->assertNull($page->getTitle());
        $this->assertNull($page->getSection());
        $this->assertNull($page->getNotes());
    }

    public function testPageWithOptions()
    {
        $page = new Page('/docs/foo', [
            'title' => 'Foo',
            'section' => 'Docs',
            'notes' => 'How to foo',
        ]);

        $this->assertEquals('Foo', $page->getTitle());
        $this->assertEquals('Docs', $page->getSection());
        $this->assertEquals('How to foo', $page->getNotes());
    }

    public function testSetContent()
    {
        $page = new Page('/test');
        $page->setContent('# Hello');
        $this->assertEquals('# Hello', $page->getContent());
    }
}
