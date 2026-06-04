<?php

namespace LlmsGenerator\Dumper;

class FileDumper
{
    private string $outputDir;

    public function __construct(string $outputDir)
    {
        $this->outputDir = rtrim($outputDir, '/');
    }

    public function dump(string $filename, string $content): string
    {
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }

        $path = $this->outputDir . '/' . $filename;
        file_put_contents($path, $content);

        return $path;
    }
}
