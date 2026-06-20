<?php

namespace MediaWiki\Extension\Favicons;

use MediaWiki\Hook\OutputPageAfterGetHeadLinksArrayHook;
use MediaWiki\Html\Html;

/**
 * Hook handlers for the Favicons extension.
 */
class Hooks implements OutputPageAfterGetHeadLinksArrayHook {

	/**
	 * Replace MediaWiki's default favicon/apple-touch <link> tags with a
	 * configurable set driven by $wgFavicons.
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/OutputPageAfterGetHeadLinksArray
	 * @param string[] &$tags <head> links generated so far, keyed by name
	 * @param \MediaWiki\Output\OutputPage $out
	 */
	public function onOutputPageAfterGetHeadLinksArray( &$tags, $out ): void {
		// Drop core's default favicon/apple-touch tags; this extension owns them.
		unset( $tags['favicon'] );
		unset( $tags['apple-touch-icon'] );

		$favicons = $out->getConfig()->get( 'Favicons' );
		if ( !is_array( $favicons ) ) {
			return;
		}

		// Standard favicon icons (rel=icon).
		foreach ( $favicons['icons'] ?? [] as $key => $icon ) {
			if ( !isset( $icon['href'] ) ) {
				continue;
			}

			$attrs = [
				'rel' => 'icon',
				'href' => $icon['href'],
				'type' => MimeTypeHelper::getMimeType( $icon['href'] ),
			];
			if ( isset( $icon['sizes'] ) ) {
				$attrs['sizes'] = $icon['sizes'];
			}

			$tags["favicon-$key"] = Html::element( 'link', $attrs );
		}

		// Apple touch icons (rel=apple-touch-icon).
		foreach ( $favicons['apple-touch'] ?? [] as $key => $href ) {
			$attrs = [
				'rel' => 'apple-touch-icon',
				'href' => $href,
			];
			// A "NxN" key becomes an explicit sizes attribute; 'default' stays bare.
			if ( $key !== 'default' && preg_match( '/^\d+x\d+$/', $key ) ) {
				$attrs['sizes'] = $key;
			}

			$tags["apple-touch-icon-$key"] = Html::element( 'link', $attrs );
		}
	}
}
