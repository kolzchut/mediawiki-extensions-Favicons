<?php

namespace MediaWiki\Extension\Favicons;

/**
 * Helper class for detecting MIME types from file extensions
 */
class MimeTypeHelper {

	/**
	 * Get MIME type from file extension
	 *
	 * @param string $href File path or URL
	 * @return string MIME type
	 */
	public static function getMimeType( string $href ): string {
		$ext = strtolower( pathinfo( $href, PATHINFO_EXTENSION ) );

		$mimeTypes = [
			'ico' => 'image/vnd.microsoft.icon',
			'png' => 'image/png',
			'svg' => 'image/svg+xml',
			'jpg' => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'webp' => 'image/webp',
			'gif' => 'image/gif'
		];

		return $mimeTypes[$ext] ?? 'image/png';
	}
}
