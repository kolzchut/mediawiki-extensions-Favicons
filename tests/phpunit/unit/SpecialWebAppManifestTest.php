<?php

namespace MediaWiki\Extension\Favicons\Tests\Unit;

use MediaWiki\Config\HashConfig;
use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\Favicons\SpecialWebAppManifest;
use MediaWiki\Language\Language;
use MediaWiki\Output\OutputPage;
use MediaWiki\Request\FauxRequest;
use MediaWikiUnitTestCase;

/**
 * @covers \MediaWiki\Extension\Favicons\SpecialWebAppManifest
 */
class SpecialWebAppManifestTest extends MediaWikiUnitTestCase {

	/**
	 * Run the page and return what it sent.
	 *
	 * @param mixed $favicons Value of $wgFavicons
	 * @return array{0:FauxRequest,1:string} The request (for its response) and the body
	 */
	private function execute( $favicons ): array {
		$language = $this->createMock( Language::class );
		$language->method( 'getHtmlCode' )->willReturn( 'he' );
		$language->method( 'getDir' )->willReturn( 'rtl' );

		$out = $this->createMock( OutputPage::class );
		$out->expects( $this->once() )->method( 'disable' );

		$request = new FauxRequest();
		$context = new RequestContext();
		$context->setRequest( $request );
		$context->setOutput( $out );
		$context->setConfig( new HashConfig( [
			'Favicons' => $favicons,
			'Sitename' => 'כל-זכות',
		] ) );

		$page = new SpecialWebAppManifest( $language );
		$page->setContext( $context );

		ob_start();
		$page->execute( null );
		$body = ob_get_clean();

		return [ $request, $body ];
	}

	public function testIsUnlisted(): void {
		$page = new SpecialWebAppManifest( $this->createMock( Language::class ) );

		$this->assertFalse( $page->isListed() );
		$this->assertSame( 'WebAppManifest', $page->getName() );
	}

	public static function provideDisabled(): array {
		return [
			'section absent' => [ [ 'icons' => [] ] ],
			'section null' => [ [ 'web-manifest' => null ] ],
			'section false' => [ [ 'web-manifest' => false ] ],
			'config not an array' => [ null ],
		];
	}

	/**
	 * @dataProvider provideDisabled
	 * @param mixed $favicons
	 */
	public function testNotFoundWhenDisabled( $favicons ): void {
		[ $request, $body ] = $this->execute( $favicons );
		$response = $request->response();

		$this->assertSame( 404, $response->getStatusCode() );
		$this->assertSame( '', $body );
		$this->assertNull( $response->getHeader( 'Cache-Control' ) );
	}

	public function testServesManifestWhenEnabled(): void {
		[ $request, $body ] = $this->execute( [
			'web-manifest' => [
				'short_name' => 'כל-זכות',
				'theme_color' => '#0b5394',
				'icons' => [ [ 'src' => '/icon-192.png', 'sizes' => '192x192' ] ],
			],
		] );
		$response = $request->response();

		$this->assertNull( $response->getStatusCode() );
		$this->assertSame( 'application/manifest+json; charset=utf-8', $response->getHeader( 'Content-Type' ) );
		$this->assertSame( 'nosniff', $response->getHeader( 'X-Content-Type-Options' ) );
		$this->assertSame( 'public, max-age=86400', $response->getHeader( 'Cache-Control' ) );

		// Hebrew stays readable rather than \u-escaped.
		$this->assertStringContainsString( '"כל-זכות"', $body );
		$this->assertSame( [
			'name' => 'כל-זכות',
			'short_name' => 'כל-זכות',
			'lang' => 'he',
			'dir' => 'rtl',
			'display' => 'standalone',
			'theme_color' => '#0b5394',
			'background_color' => '#ffffff',
			'icons' => [ [ 'src' => '/icon-192.png', 'type' => 'image/png', 'sizes' => '192x192' ] ],
		], json_decode( $body, true ) );
	}

	public function testEmptySectionEnablesWithDefaults(): void {
		[ $request, $body ] = $this->execute( [ 'web-manifest' => [] ] );

		$this->assertNull( $request->response()->getStatusCode() );
		$this->assertSame( 'כל-זכות', json_decode( $body, true )['name'] );
	}
}
