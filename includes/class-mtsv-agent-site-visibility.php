<?php
/**
 * Visibility: what WordPress's core sitemap treats as public.
 *
 * @package MTSV_Agent_Site
 */

defined( 'ABSPATH' ) || exit;

/**
 * Decides what is public. Spec: Gather, Visibility.
 *
 * Visibility 1, 3, 4 need no code here: published only is core's posts
 * query, which Providers calls; a latest-posts homepage is no post, so no
 * query returns it; and comments, meta, options, emails and logins are no
 * column of the fields sheet.
 */
class MTSV_Agent_Site_Visibility {

	/**
	 * Whether the plugin is on: blog_public, through the plugin's own filter,
	 * as core decides for its sitemaps. Visibility 2.
	 *
	 * @return bool Whether anything is published.
	 */
	public static function is_site_public() {
		/**
		 * Filters whether the agent site is on.
		 *
		 * @param bool $is_enabled Whether the agent site is on. Defaults to true for public sites.
		 */
		return (bool) apply_filters( 'mtsv_agent_site_enabled', (bool) get_option( 'blog_public' ) );
	}

	/**
	 * Whether one post is public: core's posts provider lists its type, and
	 * core finds it publicly viewable. Visibility 5.
	 *
	 * @param WP_Post $post The post.
	 * @return bool Whether it is public.
	 */
	public static function is_post_public( $post ) {
		return in_array( $post->post_type, MTSV_Agent_Site_Providers::subtypes( 'posts' ), true )
			&& is_post_publicly_viewable( $post );
	}
}
