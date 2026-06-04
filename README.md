# llms-generator

[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4)](https://php.net)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

A PHP library to generate `llms.txt` and `llms-full.txt` files for any PHP project. Framework-agnostic — works with Laravel, Concrete CMS, WordPress, Symfony, and plain PHP sites.

## Install

If published on Packagist:

```bash
composer require llms-generator/llms-generator
```

Or install directly from GitHub:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/madnanshah/llms-generator"
        }
    ],
    "require": {
        "llms-generator/llms-generator": "dev-main"
    }
}
```

## Usage

```php
use LlmsGenerator\Config;
use LlmsGenerator\LlmsGenerator;

$config = new Config([
    'base_url'    => 'https://example.com',
    'title'       => 'My Project',
    'description' => 'A short description for the blockquote.',
]);

$generator = new LlmsGenerator($config);

// Add pages manually
$generator->addPage('/docs/getting-started', [
    'title'   => 'Getting Started',
    'section' => 'Docs',
    'notes'   => 'How to install and configure',
]);
$generator->addPage('/docs/api', ['section' => 'Docs']);

// Or discover from sitemap
$generator->discoverFromSitemap();

// Generate both files
$result = $generator->generate();
// $result['llms.txt']      => /path/to/llms.txt
// $result['llms-full.txt'] => /path/to/llms-full.txt
```

## How it works

1. You register pages via `addPage()` or auto-discover from `sitemap.xml`
2. `generate()` fetches each page's HTML via HTTP
3. HTML is sanitized: nav, footer, aside, script, style, hr, select, option, input, button, and svg elements are removed; images are replaced with their alt or title text
4. Missing titles are parsed from `<title>` tags
5. Missing sections are derived from URL path prefixes (`/docs/*` → `Docs`)
6. Sanitized HTML is converted to clean Markdown (ATX headings, stripped unknown tags, collapsed blank lines)
7. `llms.txt` is written with the project index (H1, blockquote, sections with links)
8. `llms-full.txt` is written with full page content inline, separated by `---`

## Output examples

### llms.txt

```markdown
# My Project

> A short description for the blockquote.

## Docs

- [Getting Started](https://example.com/docs/getting-started): How to install and configure
- [API Reference](https://example.com/docs/api)

## Optional

- [Changelog](https://example.com/changelog)
```

### llms-full.txt

```markdown
# My Project

> A short description for the blockquote.

---

## Getting Started

URL: https://example.com/docs/getting-started

...full markdown content...

---

## API Reference

URL: https://example.com/docs/api

...full markdown content...
```

## API

### `addPage(string $url, array $options = []): self`

| Option | Default | Description |
|---|---|---|
| `title` | auto | Page title. Parsed from `<title>` if omitted |
| `section` | auto | H2 section. Derived from URL prefix (`/docs/*` → `Docs`) if omitted |
| `notes` | null | Description appended after the link in llms.txt |

### `discoverFromSitemap(?string $sitemapUrl = null): self`

Fetches and parses `{base_url}/sitemap.xml` (or a custom URL) and registers all discovered pages.

### `generate(): array`

Fetches all pages, converts to Markdown, writes both files. Returns `['llms.txt' => '/path', 'llms-full.txt' => '/path']`.

The constructor accepts optional implementations for testing or customization:

```php
$generator = new LlmsGenerator(
    $config,
    $fetcher,      // FetcherInterface|null — HTTP fetcher (default: HttpFetcher)
    $converter,    // ConverterInterface|null — HTML-to-markdown (default: HtmlToMarkdownConverter)
    $sanitizer,    // SanitizerInterface|null — HTML sanitizer (default: HtmlSanitizer)
    $dumper        // FileDumper|null — file writer (default: FileDumper)
);
```

## Config options

```php
$config = new Config([
    'base_url'        => 'https://example.com',
    'title'           => 'My Project',
    'description'     => 'A blockquote summary.',
    'details'         => 'Optional free-form details after the blockquote.',
    'output_dir'      => getcwd(),
    'default_section' => 'Pages',
    'http_timeout'    => 30,
]);
```

## Requirements

- PHP 7.4+
- `ext-dom`*, `ext-simplexml`*, `ext-libxml`* — bundled with PHP
- An HTTP client package (Guzzle, Symfony HttpClient, etc.) autodiscovered via `php-http/discovery`

<sup>* These are required for DOM sanitization and sitemap parsing.</sup>

## Contributing

Pull requests are welcome. Open an [issue](https://github.com/madnanshah/llms-generator/issues) first for significant changes.

## License

[MIT](LICENSE)
