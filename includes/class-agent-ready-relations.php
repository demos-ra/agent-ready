<?php
/**
 * Relations: what was gathered, as the books' sheets.
 *
 * @package Agent_Ready
 */

defined( 'ABSPATH' ) || exit;

/**
 * Builds sheets. Spec: Convert, Relations.
 */
class Agent_Ready_Relations {

	/**
	 * The main book's sheets, in order, keyed by name. Relations 1.
	 *
	 * Relations 4 is the order of Agent_Ready_Fields::RELATIONS, and of the
	 * sheets here: new ones go at the end.
	 *
	 * @param callable $alternate Gives the URL of a post's page book.
	 * @return array The sheets.
	 */
	public static function main( $alternate ) {
		$posts = Agent_Ready_Providers::posts();
		$terms = Agent_Ready_Providers::terms();
		$users = Agent_Ready_Providers::users();

		$user_ids = array();
		foreach ( $users as $user ) {
			$user_ids[ $user->ID ] = true;
		}
		$post_ids = array();
		foreach ( $posts as $post ) {
			$post_ids[ $post->ID ] = true;
		}
		$term_ids     = array();
		$taxonomy_ids = array();
		foreach ( $terms as $term ) {
			$term_ids[ $term->term_id ]              = true;
			$taxonomy_ids[ $term->term_taxonomy_id ] = true;
		}

		$author = array_search( 'post_author', Agent_Ready_Fields::RELATIONS['posts'], true );
		$parent = array_search( 'post_parent', Agent_Ready_Fields::RELATIONS['posts'], true );

		$post_rows = array();
		foreach ( $posts as $post ) {
			$row = Agent_Ready_Fields::post_row( $post, call_user_func( $alternate, $post->ID ) );

			$row[ $author ] = isset( $user_ids[ $row[ $author ] ] ) ? $row[ $author ] : '';
			$row[ $parent ] = isset( $post_ids[ $row[ $parent ] ] ) ? $row[ $parent ] : '';
			$post_rows[]    = $row;
		}

		$term_parent = array_search( 'parent', Agent_Ready_Fields::RELATIONS['taxonomies'], true );

		$term_rows = array();
		foreach ( $terms as $term ) {
			$loc = get_term_link( $term, $term->taxonomy );
			if ( is_wp_error( $loc ) ) {
				continue;
			}
			$row = Agent_Ready_Fields::term_row( $term, $loc );

			$row[ $term_parent ] = isset( $term_ids[ $row[ $term_parent ] ] ) ? $row[ $term_parent ] : '';
			$term_rows[]         = $row;
		}

		$user_rows = array_map( array( 'Agent_Ready_Fields', 'user_row' ), $users );

		return array(
			'sitemap'            => Agent_Ready_Fields::sheet( 'sitemap', array() ),
			'posts'              => Agent_Ready_Fields::sheet( 'posts', $post_rows ),
			'taxonomies'         => Agent_Ready_Fields::sheet( 'taxonomies', $term_rows ),
			'users'              => Agent_Ready_Fields::sheet( 'users', $user_rows ),
			'term_relationships' => Agent_Ready_Fields::sheet( 'term_relationships', self::pairs( $post_ids, $taxonomy_ids ) ),
		);
	}

	/**
	 * A page book's sheets, in order. Relations 2.
	 *
	 * @param WP_Post $post    The post.
	 * @param array[] $sitemap The rows of the sitemap sheet.
	 * @return array The sheets.
	 */
	public static function page( $post, $sitemap ) {
		return array(
			Agent_Ready_Fields::sheet( 'sitemap', $sitemap ),
			Agent_Ready_Fields::sheet( 'post_content', Agent_Ready_Fields::content_rows( $post ) ),
		);
	}

	/**
	 * The rows of term_relationships, only between published rows. Relations 3.
	 *
	 * @param array $post_ids The published posts, as keys.
	 * @param array $term_ids The published term_taxonomy_ids, as keys.
	 * @return array[] The rows, in object_id order.
	 */
	private static function pairs( $post_ids, $term_ids ) {
		$ids = array_map( 'intval', array_keys( $post_ids ) );
		sort( $ids );

		$rows = array();
		foreach ( array_chunk( $ids, 1000 ) as $chunk ) {
			$found = wp_get_object_terms(
				$chunk,
				Agent_Ready_Providers::taxonomies(),
				array(
					'fields'  => 'all_with_object_id',
					'orderby' => 'none',
				)
			);
			if ( is_wp_error( $found ) ) {
				continue;
			}
			foreach ( $found as $term ) {
				if ( isset( $term_ids[ $term->term_taxonomy_id ] ) ) {
					$rows[] = array( $term->object_id, $term->term_taxonomy_id );
				}
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
}
