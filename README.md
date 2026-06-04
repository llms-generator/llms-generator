# llms-generator

A PHP library to generate `llms.txt` and `llms-full.txt` files for any PHP project. Framework-agnostic — works with Laravel, Concrete CMS, WordPress, Symfony, and plain PHP sites.

## Install

```bash
composer require llms-generator/llms-generator
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
3. Missing titles are parsed from `<title>` tags
4. Missing sections are derived from URL path prefixes (`/docs/*` → `Docs`)
5. HTML is converted to clean Markdown
6. `llms.txt` is written with the project index (H1, blockquote, sections with links)
7. `llms-full.txt` is written with full page content inline

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
# Getting Started
Source: https://example.com/docs/getting-started

...full markdown content...

# API Reference
Source: https://example.com/docs/api

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
- An HTTP client package (Guzzle, Symfony HttpClient, etc.) autodiscovered via `php-http/discovery`

## License

MIT
