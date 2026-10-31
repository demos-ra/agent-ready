<?php
/**
 * Response: the HTTP status and headers of each reply.
 *
 * @package Agent_Sitemap
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sends replies. Spec: Publish, Response.
 */
class Agent_Sitemap_Response {

	/**
	 * The media type of a book. Response 1.
	 */
	const MTSV_MEDIA = 'text/prs.mtsv';

	/**
	 * A book's Content-Type. Response 1.
	 */
	const MTSV_TYPE = self::MTSV_MEDIA . '; charset=utf-8';

	/**
	 * The media type of llms.txt.
	 */
	const LLMS_TYPE = 'text/plain; charset=utf-8';

	/**
	 * Sends a reply, or 304 if the agent already has it. Response 2, 3.
	 *
	 * @param string $text     The content.
	 * @param string $type     The media type.
	 * @param int    $modified The newest lastmod, as a Unix timestamp.
	 */
	public static function send( $text, $type, $modified ) {
		if ( $modified ) {
			header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s', $modified ) . ' GMT' );
			if ( self::not_modified( $modified ) ) {
				status_header( 304 );
				return;
			}
		}

		status_header( 200 );
		header( 'Content-Type: ' . $type );
		echo $text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plain text, not HTML.
	}

	/**
	 * Evaluates If-Modified-Since. Response 3.
	 *
	 * @param int $modified The newest lastmod, as a Unix timestamp.
	 * @return bool Whether the agent's copy is current.
	 */
	private static function not_modified( $modified ) {
		if ( isset( $_SERVER['HTTP_IF_NONE_MATCH'] ) || ! isset( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) ) {
			return false;
		}
		$since = strtotime( sanitize_text_field( wp_unslash( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) ) );
		return false !== $since && $modified <= $since;
	}

	/**
	 * Answers 404 with no-cache headers, as core's sitemap does. Response 4.
	 */
	public static function not_found() {
		global $wp_query;

		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
	}
}
