<?php

namespace MediaWiki\Extension\Favicons;

/**
 * Builds the web app manifest from the 'web-manifest' section of $wgFavicons.
 *
 * Plain class with no MediaWiki service access: the caller hands in the
 * config section and the site facts the defaults are drawn from, so the
 * whole mapping is unit-testable.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/Manifest
 */
class ManifestGenerator {

	/**
	 * Optional manifest members copied through only when configured. A browser
	 * has its own fallback for each, which is better than one guessed here.
	 */
	private const OPTIONAL_MEMBERS = [ 'id', 'description', 'start_url', 'scope' ];

	/**
	 * @param array $config The 'web-manifest' section of $wgFavicons
	 * @param string $sitename Fallback for 'name' and 'short_name'
	 * @param string $langCode Fallback for 'lang' (BCP 47 code of the content language)
	 * @param string $dir Fallback for 'dir' ('ltr' or 'rtl')
	 */
	public function __construct(
		private readonly array $config,
		private readonly string $sitename,
		private readonly string $langCode,
		private readonly string $dir
	) {
	}

	/**
	 * @return array The manifest data structure, ready to JSON-encode
	 */
	public function generate(): array {
		$config = $this->config;

		$manifest = [
			'name' => $config['name'] ?? $this->sitename,
			'short_name' => $config['short_name'] ?? $config['name'] ?? $this->sitename,
			'lang' => $config['lang'] ?? $this->langCode,
			'dir' => $config['dir'] ?? $this->dir,
		];

		foreach ( self::OPTIONAL_MEMBERS as $member ) {
			if ( isset( $config[$member] ) ) {
				$manifest[$member] = $config[$member];
			}
		}

		$manifest['display'] = $config['display'] ?? 'standalone';
		$manifest['theme_color'] = $config['theme_color'] ?? '#ffffff';
		$manifest['background_color'] = $config['background_color'] ?? '#ffffff';
		$manifest['icons'] = $this->getIcons( $config['icons'] ?? [] );

		return $manifest;
	}

	/**
	 * @param mixed $icons The configured 'icons' list
	 * @return array[]
	 */
	private function getIcons( $icons ): array {
		if ( !is_array( $icons ) ) {
			return [];
		}

		$result = [];
		foreach ( $icons as $icon ) {
			if ( !is_array( $icon ) || !isset( $icon['src'] ) ) {
				continue;
			}

			$manifestIcon = [
				'src' => $icon['src'],
				'type' => $icon['type'] ?? MimeTypeHelper::getMimeType( $icon['src'] ),
			];
			// No default size: a wrong 'sizes' makes the browser pick the wrong icon,
			// while a missing one only makes it fetch the file to find out.
			if ( isset( $icon['sizes'] ) ) {
				$manifestIcon['sizes'] = $icon['sizes'];
			}
			if ( isset( $icon['purpose'] ) ) {
				$manifestIcon['purpose'] = $icon['purpose'];
			}

			$result[] = $manifestIcon;
		}

		return $result;
	}
}
