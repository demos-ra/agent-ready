<?php
/**
 * Response: the HTTP status and headers of each reply.
 *
 * @package MTSV_Agent_Site
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sends replies. Spec: Publish, Response.
 */
class MTSV_Agent_Site_Response {

	/**
	 * The media type of a book. Response 1.
	 */
	const MTSV_MEDIA = 'text/prs.mtsv';

	/**
	 * A book's Content-Type. Response 1.
	 */
	const MTSV_TYPE = self::MTSV_MEDIA . '; charset=utf-8';

	/**
	 * The media type of llms.txt. Response 3.
	 */
	const LLMS_TYPE = 'text/plain; charset=utf-8';

	/**
	 * Sends a reply. Response 1, 3.
	 *
	 * @param string $text The content.
	 * @param string $type The media type.
	 */
	public static function send( $text, $type ) {
		status_header( 200 );
		header( 'Content-Type: ' . $type );
		echo $text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plain text, not HTML.
	}

	/**
	 * Answers 404 with no-cache headers, as core's sitemap does. Response 2.
	 */
	public static function not_found() {
		global $wp_query;

		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
	}
}
