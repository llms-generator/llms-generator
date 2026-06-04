<?php

namespace LlmsGenerator\Converter;

interface ConverterInterface
{
    public function convert(string $html): string;
}
