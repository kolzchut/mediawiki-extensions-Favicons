<?php

namespace MediaWiki\Extension\Favicons\Tests\Unit;

use MediaWiki\Config\HashConfig;
use MediaWiki\Extension\Favicons\Hooks;
use MediaWiki\Output\OutputPage;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleFactory;
use MediaWikiUnitTestCase;

/**
 * @covers \MediaWiki\Extension\Favicons\Hooks
 */
class HooksTest extends MediaWikiUnitTestCase {

	private const MANIFEST_URL = '/he/%D7%9E%D7%99%D7%95%D7%97%D7%93:WebAppManifest';

	/**
	 * @param mixed $favicons Value of $wgFavicons
	 * @param bool $expectManifestTitle Whether the manifest title may be built
	 * @return string[] The resulting head tags
	 */
	private function runHook( $favicons, bool $expectManifestTitle ): array {
		$title = $this->createMock( Title::class );
		$title->method( 'getLocalURL' )->willReturn( self::MANIFEST_URL );

		$titleFactory = $this->createMock( TitleFactory::class );
		$titleFactory->expects( $expectManifestTitle ? $this->once() : $this->never() )
			->method( 'makeTitle' )
			->with( NS_SPECIAL, 'WebAppManifest' )
			->willReturn( $title );

		$out = $this->createMock( OutputPage::class );
		$out->method( 'getConfig' )->willReturn( new HashConfig( [ 'Favicons' => $favicons ] ) );

		$tags = [
			'favicon' => '<link rel="icon" href="/core.ico">',
			'apple-touch-icon' => '<link rel="apple-touch-icon" href="/core.png">',
			'other' => '<link rel="search">',
		];
		( new Hooks( $titleFactory ) )->onOutputPageAfterGetHeadLinksArray( $tags, $out );
		return $tags;
	}

	public function testReplacesCoreTagsWithConfiguredIcons(): void {
		$tags = $this->runHook( [
			'icons' => [ 'svg' => [ 'href' => '/favicon.svg', 'sizes' => 'any' ] ],
			'apple-touch' => [ '180x180' => '/apple-touch-icon.png' ],
		], false );

		$this->assertArrayNotHasKey( 'favicon', $tags );
		$this->assertArrayNotHasKey( 'apple-touch-icon', $tags );
		$this->assertSame( '<link rel="search">', $tags['other'] );
		$this->assertSame(
			'<link rel="icon" href="/favicon.svg" type="image/svg+xml" sizes="any">',
			$tags['favicon-svg']
		);
		$this->assertSame(
			'<link rel="apple-touch-icon" href="/apple-touch-icon.png" sizes="180x180">',
			$tags['apple-touch-icon-180x180']
		);
	}

	public function testNoManifestLinkWithoutWebManifestSection(): void {
		$tags = $this->runHook( [ 'icons' => [] ], false );

		$this->assertArrayNotHasKey( 'web-manifest', $tags );
	}

	public static function provideDisabledSections(): array {
		return [
			'null' => [ null ],
			'false' => [ false ],
			'string' => [ '/manifest.json' ],
		];
	}

	/**
	 * @dataProvider provideDisabledSections
	 * @param mixed $section
	 */
	public function testNoManifestLinkForNonArraySection( $section ): void {
		$tags = $this->runHook( [ 'web-manifest' => $section ], false );

		$this->assertArrayNotHasKey( 'web-manifest', $tags );
	}

	public function testManifestLinkWhenSectionIsSet(): void {
		$tags = $this->runHook( [ 'web-manifest' => [] ], true );

		$this->assertSame(
			'<link rel="manifest" href="' . self::MANIFEST_URL . '">',
			$tags['web-manifest']
		);
	}

	public function testNonArrayConfigOnlyDropsCoreTags(): void {
		$tags = $this->runHook( null, false );

		$this->assertSame( [ 'other' => '<link rel="search">' ], $tags );
	}
}
