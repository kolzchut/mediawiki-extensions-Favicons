<?php

namespace MediaWiki\Extension\Favicons;

use MediaWiki\Json\FormatJson;
use MediaWiki\Language\Language;
use MediaWiki\SpecialPage\UnlistedSpecialPage;

/**
 * Serves the web app manifest as application/manifest+json.
 *
 * This is the only entry point for the manifest: it goes through MediaWiki's
 * normal routing, so there is no standalone script under extensions/ that
 * bootstraps MediaWiki a second time.
 *
 * The feature is opt-in. Without a 'web-manifest' section in $wgFavicons the
 * page answers 404 with an empty body, and the hook emits no manifest link.
 */
class SpecialWebAppManifest extends UnlistedSpecialPage {

	/** One day: the manifest only changes with a config deploy. */
	private const MAX_AGE = 86400;

	public function __construct(
		private readonly Language $contentLanguage
	) {
		parent::__construct( 'WebAppManifest' );
	}

	/**
	 * @param string|null $subPage
	 */
	public function execute( $subPage ) {
		$this->getOutput()->disable();
		$response = $this->getRequest()->response();

		$manifestConfig = self::getManifestConfig( $this->getConfig()->get( 'Favicons' ) );
		if ( $manifestConfig === null ) {
			$response->statusHeader( 404 );
			$response->header( 'Content-Type: text/plain; charset=utf-8' );
			return;
		}

		$generator = new ManifestGenerator(
			$manifestConfig,
			(string)$this->getConfig()->get( 'Sitename' ),
			$this->contentLanguage->getHtmlCode(),
			$this->contentLanguage->getDir()
		);

		$response->header( 'Content-Type: application/manifest+json; charset=utf-8' );
		$response->header( 'X-Content-Type-Options: nosniff' );
		$response->header( 'Cache-Control: public, max-age=' . self::MAX_AGE );

		echo FormatJson::encode( $generator->generate(), "\t", FormatJson::UTF8_OK );
	}

	/**
	 * The 'web-manifest' section of $wgFavicons, or null when the feature is off.
	 *
	 * Any array, even an empty one, turns it on with the defaults; absent, null
	 * or false leaves it off. Shared with the hook so the link and the page
	 * cannot disagree about whether the manifest exists.
	 *
	 * @param mixed $favicons The value of $wgFavicons
	 * @return array|null
	 */
	public static function getManifestConfig( $favicons ): ?array {
		if ( !is_array( $favicons ) ) {
			return null;
		}
		$section = $favicons['web-manifest'] ?? null;
		return is_array( $section ) ? $section : null;
	}

	/** @inheritDoc */
	protected function getGroupName() {
		return 'other';
	}
}
