# llms-generator — Plan

## Vision

A PHP Composer library that generates `llms.txt` and `llms-full.txt` files for **any PHP project** by fetching rendered pages via HTTP and converting HTML to Markdown. Framework-agnostic — works with Concrete CMS, Laravel + Inertia, WordPress, Symfony, and plain PHP sites.

## Architecture

### Core concept

The package makes HTTP requests to the live site during `generate()`, fetches each page's HTML, parses `<title>` if not provided in options, converts content to clean Markdown, and assembles both output files per the [llmstxt.org spec](https://llmstxt.org/).

```
$generator->addPage('/docs/foo', ['title' => 'Foo'])
  → During generate(): HTTP GET https://site.com/docs/foo
  → Parse <title> if title not in options
  → HTML → Markdown (via league/html-to-markdown)
  → Appended to llms-full.txt
  → Link added to llms.txt under its section
```

### Directory structure

```
llms-generator/
├── src/
│   ├── Config.php                   # Immutable config (base_url, title, desc, etc.)
│   ├── Page.php                     # Value object: path, title, section, notes, content
│   ├── LlmsGenerator.php            # Main API — addPage(), generate()
│   ├── Fetcher/
│   │   ├── FetcherInterface.php     # Contract: fetch(string $url): string
│   │   └── HttpFetcher.php          # cURL/Guzzle HTTP client
│   ├── Converter/
│   │   ├── ConverterInterface.php   # Contract: convert(string $html): string
│   │   └── HtmlToMarkdownConverter.php  # Wraps league/html-to-markdown
│   ├── Discovery/
│   │   ├── DiscovererInterface.php  # Contract: discover(): Page[]
│   │   ├── SitemapDiscoverer.php    # Parses sitemap.xml, returns pages
│   │   └── PathListDiscoverer.php   # From user-provided array
│   └── Dumper/
│       └── FileDumper.php           # Writes llms.txt / llms-full.txt to disk
├── tests/
├── composer.json
└── README.md
```

### User-facing API

```php
use LlmsGenerator\Config;
use LlmsGenerator\LlmsGenerator;

$config = new Config([
    'base_url'    => 'https://example.com',
    'title'       => 'My Project',
    'description' => 'A short summary for the blockquote.',
    'details'     => 'Optional paragraphs after the blockquote.',
    'output_dir'  => __DIR__ . '/public',
]);

$generator = new LlmsGenerator($config);

// Option A: add pages manually
$generator->addPage('/docs/getting-started', [
    'title'   => 'Getting Started',   // optional — parsed from <title> if omitted
    'section' => 'Docs',              // optional — auto-derived from URL prefix if omitted
    'notes'   => 'How to install',    // optional
]);
$generator->addPage('/docs/api');
$generator->addPage('/blog/hello', ['section' => 'Blog']);
$generator->addPage('/changelog', ['section' => 'Optional']);

// Option B: discover from sitemap
$generator->discoverFromSitemap();

// Generate both files
$result = $generator->generate();
// Writes: {output_dir}/llms.txt and {output_dir}/llms-full.txt
```

### How `generate()` works

During `generate()`, each page is fetched once. The same HTML is used for both title extraction (if omitted) and Markdown conversion.

1. **llms.txt** — built from metadata (titles from options or `<title>` tag):
   ```
   # My Project
   > A short summary...

   Optional details here.

   ## Docs
   - [Getting Started](https://example.com/docs/getting-started): How to install
   - [API Reference](https://example.com/docs/api): Full API docs

   ## Blog
   - [Hello World](https://example.com/blog/hello)

   ## Optional
   - [Changelog](https://example.com/changelog)
   ```

2. **llms-full.txt** — for each page, fetches HTML → converts to Markdown → concatenates:
   ```
   # Getting Started
   Source: https://example.com/docs/getting-started

   ...markdown content...

   # API Reference
   Source: https://example.com/docs/api

   ...markdown content...
   ```

### Page discovery from sitemap

`SitemapDiscoverer` fetches `{base_url}/sitemap.xml`, parses it, and groups URLs:
- `/docs/*` → "Docs" section
- `/blog/*` → "Blog" section
- Falls back to domain/path prefix grouping

Each discovered page gets its title fetched from `<title>` tag (one HEAD request) or derived from the URL slug.

### PHP version

Target **PHP 7.4+** for maximum compatibility (legacy CMS projects like older Concrete CMS versions).

### Dependencies

| Package | Purpose |
|---|---|
| `league/html-to-markdown` | HTML → Markdown conversion |
| `php-http/discovery` | PSR-18 HTTP client discovery (no强制 Guzzle) |
| `psr/http-client` + `psr/http-factory` | PSR-18 interfaces |
| `phpunit/phpunit` (dev) | Tests |

### Extension points

Framework-specific packages can provide custom Discoverers:

| Package | Discoverer |
|---|---|
| `llms-generator-laravel` | Reads Laravel routes from `Route::getRoutes()`, resolves Inertia pages |
| `llms-generator-concrete-cms` | Reads Concrete page tree via `Page::getByID()` |
| `llms-generator-symfony` | Reads Symfony router |

These are **not** part of v1 core.

### Key design decisions

- **No framework coupling** — zero Laravel/Symfony deps in core
- **HTTP-first** — captures rendered output, works with SPAs, dynamic CMS pages
- **Sitemap discoverer built-in** — zero-config for sites with sitemap.xml
- **Manual addPage() always available** — works for any project
- **llms-full.txt fetches on generate** — content is fresh, not stale
- **PHP 7.4+** — broadly compatible

### v1 scope (MVP)

1. `Config`, `Page`, `LlmsGenerator` core classes
2. `HttpFetcher` (cURL-based, PSR-18 compatible)
3. `HtmlToMarkdownConverter` (wraps league/html-to-markdown)
4. `SitemapDiscoverer` + `PathListDiscoverer`
5. `FileDumper`
6. Tests for each component
7. README with examples for Concrete CMS, Laravel, WordPress

### Out of scope for v1

- Framework-specific discoverers (separate packages)
- CLI command (library-only)
- Concurrency/parallel fetching (add in v2)
- Caching layer (add in v2)
- Authentication/cookie injection (add in v2)

## Implementation order

1. Bootstrap `composer.json`, PHP CS Fixer, PHPUnit config
2. `Config` value object
3. `Page` value object
4. `FetcherInterface` + `HttpFetcher`
5. `ConverterInterface` + `HtmlToMarkdownConverter`
6. `LlmsGenerator` — `addPage()`, section grouping, llms.txt rendering
7. `FileDumper`
8. `SitemapDiscoverer`
9. Integration test: generate from a real sitemap
10. README
