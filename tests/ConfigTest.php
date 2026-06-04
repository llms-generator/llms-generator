<?php

namespace LlmsGenerator\Tests;

use LlmsGenerator\Config;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    public function testDefaults()
    {
        $config = new Config();
        $this->assertEquals(getcwd(), $config->getBaseUrl());
        $this->assertEquals('Untitled', $config->getTitle());
        $this->assertEquals('', $config->getDescription());
        $this->assertEquals(getcwd(), $config->getOutputDir());
        $this->assertEquals('Pages', $config->getDefaultSection());
    }

    public function testCustomValues()
    {
        $config = new Config([
            'base_url' => 'https://example.com/',
            'title' => 'My Site',
            'description' => 'A test site',
            'output_dir' => '/tmp/llms',
        ]);

        $this->assertEquals('https://example.com', $config->getBaseUrl());
        $this->assertEquals('My Site', $config->getTitle());
        $this->assertEquals('/tmp/llms', $config->getOutputDir());
    }
}
