<?php
/**
 * Relations: what was gathered, as the books' sheets.
 *
 * @package MTSV_Agent_Site
 */

defined( 'ABSPATH' ) || exit;

/**
 * Builds sheets. Spec: Convert, Relations.
 *
 * Relations 4 is the order of MTSV_Agent_Site_Fields::SCHEMA, and of the
 * sheets here: new ones go at the end. Relations 5 is the order the
 * providers return, and the key order of pairs and content_rows.
 */
class MTSV_Agent_Site_Relations {

	/**
	 * The index book: the sitemap sheet, listing every book of every
	 * provider's sitemaps, as core's index lists its sitemaps, then the
	 * fields sheet, the schema. Relations 1; Fields 2.
	 *
	 * @param callable $book_url Gives a book's URL: provider, subtype, page.
	 * @return array The sheets.
	 */
	public static function index( $book_url ) {
		$rows = array();
		foreach ( MTSV_Agent_Site_Providers::NAMES as $name ) {
			foreach ( MTSV_Agent_Site_Providers::type_data( $name ) as $type ) {
				for ( $page = 1; $page <= $type['pages']; $page++ ) {
					$rows[] = MTSV_Agent_Site_Fields::sitemap_row( call_user_func( $book_url, $name, $type['name'], $page ) );
				}
			}
		}
		return array(
			MTSV_Agent_Site_Fields::sheet( 'sitemap', $rows ),
			MTSV_Agent_Site_Fields::sheet( 'fields', MTSV_Agent_Site_Fields::fields_rows() ),
		);
	}

	/**
	 * A provider's book: posts, then term_relationships; terms, then
	 * term_taxonomy; or users. Relations 1.
	 *
	 * @param string   $name      The provider's name.
	 * @param string   $subtype   The subtype; empty for users.
	 * @param int      $page      The page, from 1.
	 * @param callable $alternate Gives the URL of a post's page book.
	 * @return array The sheets; none when the page has no rows.
	 */
	public static function book( $name, $subtype, $page, $alternate ) {
		$objects = MTSV_Agent_Site_Providers::objects( $name, $subtype, $page );
		if ( empty( $objects ) ) {
			return array();
		}
		if ( 'posts' === $name ) {
			return self::posts( $objects, $alternate );
		}
		if ( 'taxonomies' === $name ) {
			return self::taxonomies( $objects );
		}
		return array( MTSV_Agent_Site_Fields::sheet( 'users', array_map( array( 'MTSV_Agent_Site_Fields', 'user_row' ), $objects ) ) );
	}

	/**
	 * A page book: the post_content sheet. Relations 2.
	 *
	 * @param WP_Post $post The post.
	 * @return array The sheets.
	 */
	public static function page( $post ) {
		return array( MTSV_Agent_Site_Fields::sheet( 'post_content', MTSV_Agent_Site_Fields::content_rows( $post ) ) );
	}

	/**
	 * The posts and term_relationships sheets of a page of posts. Relations 1, 3.
	 *
	 * @param WP_Post[] $posts     The posts.
	 * @param callable  $alternate Gives the URL of a post's page book.
	 * @return array The sheets.
	 */
	private static function posts( $posts, $alternate ) {
		$authors = self::published_users( array_unique( wp_list_pluck( $posts, 'post_author' ) ) );
		$author  = array_search( 'post_author', MTSV_Agent_Site_Fields::columns( 'posts' ), true );
		$parent  = array_search( 'post_parent', MTSV_Agent_Site_Fields::columns( 'posts' ), true );

		$rows = array();
		foreach ( $posts as $post ) {
			$row = MTSV_Agent_Site_Fields::post_row( $post, call_user_func( $alternate, $post->ID ) );

			$row[ $author ] = in_array( (int) $row[ $author ], $authors, true ) ? $row[ $author ] : '';
			if ( '' !== $row[ $parent ] ) {
				$parent_post    = get_post( $row[ $parent ] );
				$row[ $parent ] = $parent_post && MTSV_Agent_Site_Visibility::is_post_public( $parent_post ) ? $row[ $parent ] : '';
			}
			$rows[] = $row;
		}

		return array(
			MTSV_Agent_Site_Fields::sheet( 'posts', $rows ),
			MTSV_Agent_Site_Fields::sheet( 'term_relationships', self::pairs( wp_list_pluck( $posts, 'ID' ) ) ),
		);
	}

	/**
	 * The terms and term_taxonomy sheets of a page of terms, terms first, as
	 * term_taxonomy references them. Relations 1, 3, 5.
	 *
	 * @param WP_Term[] $terms The terms, all of one taxonomy.
	 * @return array The sheets.
	 */
	private static function taxonomies( $terms ) {
		$taxonomy = $terms[0]->taxonomy;
		$parents  = self::published_terms( $taxonomy, array_filter( wp_list_pluck( $terms, 'parent' ) ), 'ids' );
		$parent   = array_search( 'parent', MTSV_Agent_Site_Fields::columns( 'term_taxonomy' ), true );

		$terms_rows         = array();
		$term_taxonomy_rows = array();
		foreach ( $terms as $term ) {
			$loc = get_term_link( $term, $taxonomy );
			if ( is_wp_error( $loc ) ) {
				continue;
			}
			$row = MTSV_Agent_Site_Fields::term_taxonomy_row( $term, $loc );

			$row[ $parent ]       = in_array( (int) $row[ $parent ], $parents, true ) ? $row[ $parent ] : '';
			$term_taxonomy_rows[] = $row;
			$terms_rows[]         = MTSV_Agent_Site_Fields::terms_row( $term );
		}
		return array(
			MTSV_Agent_Site_Fields::sheet( 'terms', $terms_rows ),
			MTSV_Agent_Site_Fields::sheet( 'term_taxonomy', $term_taxonomy_rows ),
		);
	}

	/**
	 * The rows of term_relationships of some posts, only to published terms.
	 * Relations 3.
	 *
	 * @param int[] $post_ids The posts.
	 * @return array[] The rows, in object_id order.
	 */
	private static function pairs( $post_ids ) {
		$taxonomies = MTSV_Agent_Site_Providers::subtypes( 'taxonomies' );
		if ( empty( $post_ids ) || empty( $taxonomies ) ) {
			return array();
		}
		$found = wp_get_object_terms(
			$post_ids,
			$taxonomies,
			array(
				'fields'  => 'all_with_object_id',
				'orderby' => 'none',
			)
		);
		if ( is_wp_error( $found ) ) {
			return array();
		}

		$published = array();
		foreach ( $taxonomies as $taxonomy ) {
			$in_taxonomy = wp_list_filter( $found, array( 'taxonomy' => $taxonomy ) );
			$published   = array_merge( $published, self::published_terms( $taxonomy, wp_list_pluck( $in_taxonomy, 'term_id' ), 'tt_ids' ) );
		}

		$rows = array();
		foreach ( $found as $term ) {
			if ( in_array( (int) $term->term_taxonomy_id, $published, true ) ) {
				$rows[] = array( $term->object_id, $term->term_taxonomy_id );
			}
		}
		usort(
			$rows,
			static function ( $a, $b ) {
				return array( $a[0], $a[1] ) <=> array( $b[0], $b[1] );
			}
		);
		return $rows;
	}

	/**
	 * Which of some users core's users provider lists. Relations 3.
	 *
	 * @param int[] $ids The users.
	 * @return int[] Those listed.
	 */
	private static function published_users( $ids ) {
		$ids = array_filter( array_map( 'intval', $ids ) );
		if ( empty( $ids ) ) {
			return array();
		}
		$args            = MTSV_Agent_Site_Providers::query_args( 'users', '' );
		$args['include'] = $ids;
		$args['number']  = -1;
		$args['fields']  = 'ID';
		$query           = new WP_User_Query( $args );
		return array_map( 'intval', $query->get_results() );
	}

	/**
	 * Which of some terms of a taxonomy core's taxonomies provider lists.
	 * Relations 3.
	 *
	 * @param string $taxonomy The taxonomy.
	 * @param int[]  $ids      The term IDs.
	 * @param string $fields   'ids' for term IDs, 'tt_ids' for term_taxonomy_ids.
	 * @return int[] Those listed.
	 */
	private static function published_terms( $taxonomy, $ids, $fields ) {
		$ids = array_filter( array_map( 'intval', $ids ) );
		if ( empty( $ids ) || ! in_array( $taxonomy, MTSV_Agent_Site_Providers::subtypes( 'taxonomies' ), true ) ) {
			return array();
		}
		$args            = MTSV_Agent_Site_Providers::query_args( 'taxonomies', $taxonomy );
		$args['include'] = $ids;
		$args['number']  = 0;
		$args['fields']  = $fields;
		$query           = new WP_Term_Query( $args );
		return empty( $query->terms ) ? array() : array_map( 'intval', $query->terms );
	}
}
