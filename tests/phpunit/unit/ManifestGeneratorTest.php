<?php

namespace MediaWiki\Extension\Favicons\Tests\Unit;

use MediaWiki\Extension\Favicons\ManifestGenerator;
use MediaWikiUnitTestCase;

/**
 * @covers \MediaWiki\Extension\Favicons\ManifestGenerator
 */
class ManifestGeneratorTest extends MediaWikiUnitTestCase {

	private function generate( array $config ): array {
		return ( new ManifestGenerator( $config, 'כל-זכות', 'he', 'rtl' ) )->generate();
	}

	public function testEmptySectionFallsBackToSiteDefaults(): void {
		$this->assertSame( [
			'name' => 'כל-זכות',
			'short_name' => 'כל-זכות',
			'lang' => 'he',
			'dir' => 'rtl',
			'display' => 'standalone',
			'theme_color' => '#ffffff',
			'background_color' => '#ffffff',
			'icons' => [],
		], $this->generate( [] ) );
	}

	public function testNullValuesFallBackToDefaults(): void {
		$manifest = $this->generate( [ 'name' => null, 'short_name' => null, 'display' => null ] );

		$this->assertSame( 'כל-זכות', $manifest['name'] );
		$this->assertSame( 'כל-זכות', $manifest['short_name'] );
		$this->assertSame( 'standalone', $manifest['display'] );
	}

	public function testShortNameFallsBackToConfiguredName(): void {
		$manifest = $this->generate( [ 'name' => 'Kol-Zchut' ] );

		$this->assertSame( 'Kol-Zchut', $manifest['name'] );
		$this->assertSame( 'Kol-Zchut', $manifest['short_name'] );
	}

	public function testConfiguredValuesWin(): void {
		$manifest = $this->generate( [
			'name' => 'Kol-Zchut',
			'short_name' => 'KZ',
			'lang' => 'ar',
			'dir' => 'ltr',
			'display' => 'minimal-ui',
			'theme_color' => '#123456',
			'background_color' => '#abcdef',
		] );

		$this->assertSame( 'Kol-Zchut', $manifest['name'] );
		$this->assertSame( 'KZ', $manifest['short_name'] );
		$this->assertSame( 'ar', $manifest['lang'] );
		$this->assertSame( 'ltr', $manifest['dir'] );
		$this->assertSame( 'minimal-ui', $manifest['display'] );
		$this->assertSame( '#123456', $manifest['theme_color'] );
		$this->assertSame( '#abcdef', $manifest['background_color'] );
	}

	public function testOptionalMembersOnlyWhenConfigured(): void {
		$bare = $this->generate( [] );
		foreach ( [ 'id', 'description', 'start_url', 'scope' ] as $member ) {
			$this->assertArrayNotHasKey( $member, $bare );
		}

		$full = $this->generate( [
			'id' => '/',
			'description' => 'Rights portal',
			'start_url' => '/he/',
			'scope' => '/he/',
		] );
		$this->assertSame( '/', $full['id'] );
		$this->assertSame( 'Rights portal', $full['description'] );
		$this->assertSame( '/he/', $full['start_url'] );
		$this->assertSame( '/he/', $full['scope'] );
	}

	public function testUnknownKeysAreNotCopied(): void {
		$manifest = $this->generate( [ 'serviceworker' => '/sw.js' ] );

		$this->assertArrayNotHasKey( 'serviceworker', $manifest );
	}

	public function testIcons(): void {
		$manifest = $this->generate( [
			'icons' => [
				[ 'src' => '/icon-192.png', 'sizes' => '192x192' ],
				[ 'src' => '/icon-512.webp', 'sizes' => '512x512', 'purpose' => 'maskable' ],
				[ 'src' => '/icon.svg', 'type' => 'image/custom' ],
				[ 'sizes' => '48x48' ],
				'not-an-array',
			],
		] );

		$this->assertSame( [
			[ 'src' => '/icon-192.png', 'type' => 'image/png', 'sizes' => '192x192' ],
			[ 'src' => '/icon-512.webp', 'type' => 'image/webp', 'sizes' => '512x512', 'purpose' => 'maskable' ],
			[ 'src' => '/icon.svg', 'type' => 'image/custom' ],
		], $manifest['icons'] );
	}

	public function testNonArrayIconsGiveEmptyList(): void {
		$this->assertSame( [], $this->generate( [ 'icons' => '/icon.png' ] )['icons'] );
	}
}
