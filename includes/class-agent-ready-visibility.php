<?php
/**
 * Visibility: what WordPress's core sitemap treats as public.
 *
 * @package Agent_Ready
 */

defined( 'ABSPATH' ) || exit;

/**
 * Decides what is public. Spec: Gather, Visibility.
 *
 * Visibility 3 and 4 need no code: a latest-posts homepage is no post, so no
 * query returns it, and comments, meta, options, emails and logins are no
 * column of the fields sheet.
 */
class Agent_Ready_Visibility {

	/**
	 * Whether the site is public. Visibility 2.
	 *
	 * @return bool Whether anything is published.
	 */
	public static function is_site_public() {
		return (bool) get_option( 'blog_public' );
	}

	/**
	 * The post statuses published. Visibility 1.
	 *
	 * @return string[] The statuses.
	 */
	public static function post_statuses() {
		return array( 'publish' );
	}

	/**
	 * Whether a post is public: a published post of a public post type.
	 *
	 * @param WP_Post $post The post.
	 * @return bool Whether it is public.
	 */
	public static function is_post_public( $post ) {
		return in_array( $post->post_status, self::post_statuses(), true )
			&& in_array( $post->post_type, Agent_Ready_Providers::post_types(), true );
	}
}
