# Favicons Extension

A MediaWiki extension that emits configurable favicon and Apple touch icon
`<link>` tags from a single config variable, replacing MediaWiki core's
default favicon handling and any per-skin favicon hooks.

> **Scope:** this extension owns favicon `<link>` *tags*, plus an opt-in web
> app manifest served by `Special:WebAppManifest`. It does **not** serve the
> icon files — those are served at their paths by the web server (e.g. nginx
> root `location`s).

## Installation

```php
wfLoadExtension( 'Favicons' );
```

Verify on `Special:Version`.

## Configuration

All configuration lives in the `$wgFavicons` array, with three sections:
`icons` (emitted as `rel="icon"`), `apple-touch` (emitted as
`rel="apple-touch-icon"`) and the opt-in `web-manifest` (see below). MIME
types are detected automatically from the file extension.

### Standard favicons (`icons`)

```php
$wgFavicons['icons'] = [
	'ico' => [
		'href' => '/favicon.ico',
		'sizes' => '16x16 24x24 32x32 48x48'
	],
	'png-192' => [
		'href' => '/favicon.png',
		'sizes' => '192x192'
	],
	'svg' => [
		'href' => '/favicon.svg',
		'sizes' => 'any'
	]
];
```

The key (`ico`, `png-192`, …) is arbitrary, used only to name the tag.
Each entry supports:

- `href` (required): path or URL to the favicon file.
- `sizes` (optional): e.g. `'192x192'`, `'16x16 24x24'`, `'any'`.

### Apple touch icons (`apple-touch`)

```php
$wgFavicons['apple-touch'] = [
	'default' => '/apple-touch-icon.png',
	'180x180' => '/apple-touch-icon-180x180.png'
];
```

The key `'default'` emits a link with no `sizes` attribute. A key matching
`NxN` (e.g. `180x180`) emits that value as the `sizes` attribute.

### Web app manifest (`web-manifest`, opt-in)

Off unless you set it. With no `web-manifest` key (or `null` / `false`),
no `<link rel="manifest">` is emitted and `Special:WebAppManifest` answers
404 with an empty body. Any array, even `[]`, turns it on:

```php
$wgFavicons['web-manifest'] = [
	'name' => 'Kol-Zchut',
	'short_name' => 'Kol-Zchut',
	'theme_color' => '#ffffff',
	'background_color' => '#ffffff',
	'display' => 'standalone',
	'icons' => [
		[ 'src' => '/icon-192.png', 'sizes' => '192x192' ],
		[ 'src' => '/icon-512.png', 'sizes' => '512x512' ],
		[ 'src' => '/icon-maskable-512.png', 'sizes' => '512x512', 'purpose' => 'maskable' ],
	],
];
```

When enabled, every page gets
`<link rel="manifest" href="…/Special:WebAppManifest">`, and that page
serves the manifest as `application/manifest+json` with
`Cache-Control: public, max-age=86400`.

Members and their defaults:

| Key                | Default when unset                      |
|--------------------|-----------------------------------------|
| `name`             | `$wgSitename`                           |
| `short_name`       | `name`, else `$wgSitename`              |
| `lang`             | the content language's code (e.g. `he`) |
| `dir`              | the content language's direction        |
| `display`          | `standalone`                            |
| `theme_color`      | `#ffffff`                               |
| `background_color` | `#ffffff`                               |
| `icons`            | `[]`                                    |
| `id`, `description`, `start_url`, `scope` | omitted, so the browser's own fallback applies |

Other keys are ignored. Each icon needs `src`; `type` defaults to the MIME
type detected from `src`, and `sizes` / `purpose` are copied only when set.

Merge note: MediaWiki merges a site's `$wgFavicons` with the extension
default at the top level, so setting `$wgFavicons['web-manifest']` alone
keeps the default `icons` / `apple-touch` sections. The default has no
`web-manifest` key, which is what keeps the feature off.

There is deliberately no standalone `manifest.php` under the extension
directory: such a script would bootstrap MediaWiki a second time outside
normal routing. The special page is the only way the manifest is served.

## MIME type detection

Detected from the file extension:

| Extension       | MIME type                   |
|-----------------|-----------------------------|
| `.ico`          | `image/vnd.microsoft.icon`  |
| `.png`          | `image/png`                 |
| `.svg`          | `image/svg+xml`             |
| `.jpg` / `.jpeg`| `image/jpeg`                |
| `.webp`         | `image/webp`                |
| `.gif`          | `image/gif`                 |

Unknown extensions fall back to `image/png`.

## Default configuration

With no `$wgFavicons` override, the extension emits:

- ICO favicon at `/favicon.ico` (16x16 24x24 32x32 48x48)
- PNG favicon at `/favicon.png` (192x192)
- SVG favicon at `/favicon.svg` (any)
- Apple touch icon at `/apple-touch-icon.png`

## Migration from hardcoded favicons (skin hooks / core globals)

1. Remove any hook or code that adds favicon/apple-touch `<link>` tags
   (e.g. a skin's `onOutputPageAfterGetHeadLinksArray()`).
2. Remove `$wgFavicon` / `$wgAppleTouchIcon` from site settings — this
   extension unsets core's default `favicon` / `apple-touch-icon` head tags,
   so leaving the globals set would produce duplicate links.
3. Install this extension and set `$wgFavicons` to match.

## How it works

Hooks `OutputPageAfterGetHeadLinksArray`: unsets core's default
`favicon` / `apple-touch-icon` tags, then appends one tag per configured
entry, and the manifest link when `web-manifest` is set. Config is read via
the `Config` service (`$out->getConfig()`), not globals.

`Special:WebAppManifest` (unlisted) builds the manifest from the same config
and the content language, and disables the normal page output to send raw
JSON.

## Development

```sh
composer install
composer test        # parallel-lint, phpcs, minus-x
MW_INSTALL_PATH=/path/to/mediawiki composer phan
```

The PHPUnit tests in `tests/phpunit/unit/` are `MediaWikiUnitTestCase`s and
run under core's runner, e.g. from the core directory:
`php vendor/bin/phpunit /path/to/extensions/Favicons/tests/phpunit/unit`.

## Requirements

- MediaWiki 1.43.0 or later

## License

GPL-2.0-or-later
