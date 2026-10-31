<?php
/**
 * Providers: what WordPress's core sitemap providers list.
 *
 * Core's providers return only URLs and dates, and keep their queries
 * protected, so this class runs the same queries with the same filters.
 *
 * @package Agent_Ready
 */

defined( 'ABSPATH' ) || exit;

/**
 * Gathers posts, terms and users. Spec: Gather, Providers.
 */
class Agent_Ready_Providers {

	/**
	 * The public post types, without attachments. Providers 1.
	 *
	 * @return string[] The post type names.
	 */
	public static function post_types() {
		$provider = new WP_Sitemaps_Posts();
		return array_map( 'strval', array_keys( $provider->get_object_subtypes() ) );
	}

	/**
	 * The public taxonomies. Providers 2.
	 *
	 * @return string[] The taxonomy names.
	 */
	public static function taxonomies() {
		$provider = new WP_Sitemaps_Taxonomies();
		return array_map( 'strval', array_keys( $provider->get_object_subtypes() ) );
	}

	/**
	 * Every public post, in ID order. Providers 1, 4; Visibility 1.
	 *
	 * @return WP_Post[] The posts.
	 */
	public static function posts() {
		$posts = array();
		foreach ( self::post_types() as $post_type ) {
			$args = apply_filters(
				'wp_sitemaps_posts_query_args',
				array(
					'orderby'                => 'ID',
					'order'                  => 'ASC',
					'post_type'              => $post_type,
					'posts_per_page'         => wp_sitemaps_get_max_urls( 'post' ),
					'post_status'            => Agent_Ready_Visibility::post_statuses(),
					'no_found_rows'          => true,
					'update_post_term_cache' => false,
					'update_post_meta_cache' => false,
					'ignore_sticky_posts'    => true,
				),
				$post_type
			);

			$page = 1;
			do {
				$args['paged'] = $page;
				$query         = new WP_Query( $args );
				$posts         = array_merge( $posts, $query->posts );
				++$page;
			} while ( ! empty( $query->posts ) );
		}

		usort(
			$posts,
			static function ( $a, $b ) {
				return $a->ID - $b->ID;
			}
		);
		return $posts;
	}

	/**
	 * Every public, non-empty term. Providers 2, 4.
	 *
	 * @return WP_Term[] The terms.
	 */
	public static function terms() {
		$max   = wp_sitemaps_get_max_urls( 'term' );
		$terms = array();
		foreach ( self::taxonomies() as $taxonomy ) {
			$args = apply_filters(
				'wp_sitemaps_taxonomies_query_args',
				array(
					'taxonomy'               => $taxonomy,
					'orderby'                => 'term_order',
					'number'                 => $max,
					'hide_empty'             => true,
					'hierarchical'           => false,
					'update_term_meta_cache' => false,
				),
				$taxonomy
			);

			$args['fields'] = 'all';
			$args['offset'] = 0;
			do {
				$query = new WP_Term_Query( $args );
				$found = empty( $query->terms ) ? array() : $query->terms;
				$terms = array_merge( $terms, $found );

				$args['offset'] += $max;
			} while ( ! empty( $found ) );
		}
		return $terms;
	}

	/**
	 * Every user with published posts. Providers 3, 4.
	 *
	 * @return WP_User[] The users.
	 */
	public static function users() {
		$post_types = get_post_types( array( 'public' => true ) );
		unset( $post_types['attachment'], $post_types['page'] );

		$args = apply_filters(
			'wp_sitemaps_users_query_args',
			array(
				'has_published_posts' => array_keys( $post_types ),
				'number'              => wp_sitemaps_get_max_urls( 'user' ),
			)
		);

		$users = array();
		$page  = 1;
		do {
			$args['paged'] = $page;
			$query         = new WP_User_Query( $args );
			$found         = $query->get_results();
			$users         = array_merge( $users, $found );
			++$page;
		} while ( ! empty( $found ) );
		return $users;
	}
}
