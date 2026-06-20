# Favicons Extension

A MediaWiki extension that emits configurable favicon and Apple touch icon
`<link>` tags from a single config variable, replacing MediaWiki core's
default favicon handling and any per-skin favicon hooks.

> **Scope:** this extension owns favicon `<link>` *tags* only. It does **not**
> serve the icon files — those are served at their paths by the web server
> (e.g. nginx root `location`s). Web app manifest / PWA support was
> deliberately left out of the first cut; it is tracked separately in
> kolzchut/kz-mediawiki-main#54.

## Installation

```php
wfLoadExtension( 'Favicons' );
```

Verify on `Special:Version`.

## Configuration

All configuration lives in the `$wgFavicons` array, with two sections:
`icons` (emitted as `rel="icon"`) and `apple-touch` (emitted as
`rel="apple-touch-icon"`). MIME types are detected automatically from the
file extension.

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
entry. Config is read via the `Config` service (`$out->getConfig()`), not
globals.

## Requirements

- MediaWiki 1.43.0 or later

## License

GPL-2.0-or-later
