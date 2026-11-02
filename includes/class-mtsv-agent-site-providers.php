<?php
/**
 * Providers: what WordPress's core sitemap providers list.
 *
 * Core's providers return only URLs and dates, and keep their queries
 * protected, so this class extends them and calls their own methods.
 *
 * @package MTSV_Agent_Site
 */

defined( 'ABSPATH' ) || exit;

/**
 * Gathers posts, terms and users. Spec: Gather, Providers.
 */
class MTSV_Agent_Site_Providers {

	/**
	 * Core's provider names, in core's order.
	 */
	const NAMES = array( 'posts', 'taxonomies', 'users' );

	/**
	 * Core's provider, extended to call its query-args method. Providers 4.
	 *
	 * @param string $name The provider's name.
	 * @return WP_Sitemaps_Provider The provider.
	 */
	private static function core( $name ) {
		if ( 'posts' === $name ) {
			return new class() extends WP_Sitemaps_Posts {
				/**
				 * Core's query arguments.
				 *
				 * @param string $subtype The post type.
				 * @return array The arguments.
				 */
				public function query_args( $subtype ) {
					return $this->get_posts_query_args( $subtype );
				}
			};
		}
		if ( 'taxonomies' === $name ) {
			return new class() extends WP_Sitemaps_Taxonomies {
				/**
				 * Core's query arguments.
				 *
				 * @param string $subtype The taxonomy.
				 * @return array The arguments.
				 */
				public function query_args( $subtype ) {
					return $this->get_taxonomies_query_args( $subtype );
				}
			};
		}
		return new class() extends WP_Sitemaps_Users {
			/**
			 * Core's query arguments. Users have no subtype.
			 *
			 * @return array The arguments.
			 */
			public function query_args() {
				return $this->get_users_query_args();
			}
		};
	}

	/**
	 * A provider's subtypes. Providers 1, 2.
	 *
	 * @param string $name The provider's name.
	 * @return string[] The subtype names; none for users.
	 */
	public static function subtypes( $name ) {
		return array_map( 'strval', array_keys( self::core( $name )->get_object_subtypes() ) );
	}

	/**
	 * A provider's sitemaps: each subtype, or the whole provider, and its
	 * number of pages. Providers 5.
	 *
	 * @param string $name The provider's name.
	 * @return array[] Core's sitemap type data: name, pages.
	 */
	public static function type_data( $name ) {
		return self::core( $name )->get_sitemap_type_data();
	}

	/**
	 * Core's query arguments for a subtype. Providers 4.
	 *
	 * @param string $name    The provider's name.
	 * @param string $subtype The subtype; empty for users.
	 * @return array The arguments.
	 */
	public static function query_args( $name, $subtype ) {
		return self::core( $name )->query_args( $subtype );
	}

	/**
	 * One page of a provider's subtype, or of the whole provider when it has
	 * none, as core's get_url_list pages it. Providers 1-5.
	 *
	 * @param string $name    The provider's name.
	 * @param string $subtype The subtype; empty for users.
	 * @param int    $page    The page, from 1.
	 * @return array The posts, terms or users; none for a page or subtype core does not list.
	 */
	public static function objects( $name, $subtype, $page ) {
		if ( ! in_array( $name, self::NAMES, true ) ) {
			return array();
		}
		$subtypes = self::subtypes( $name );
		if ( $subtypes ? ! in_array( $subtype, $subtypes, true ) : '' !== $subtype ) {
			return array();
		}

		$args = self::query_args( $name, $subtype );
		if ( 'posts' === $name ) {
			$args['paged'] = $page;
			$query         = new WP_Query( $args );
			return $query->posts;
		}
		if ( 'taxonomies' === $name ) {
			$args['fields'] = 'all';
			$args['offset'] = ( $page - 1 ) * wp_sitemaps_get_max_urls( 'term' );
			$query          = new WP_Term_Query( $args );
			return empty( $query->terms ) ? array() : $query->terms;
		}
		$args['paged'] = $page;
		$query         = new WP_User_Query( $args );
		return $query->get_results();
	}
}
