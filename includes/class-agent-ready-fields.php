<?php
/**
 * Fields: each sheet's columns, as the spec's fields sheet lists them.
 *
 * @package Agent_Ready
 */

// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- PHP's own DOM property names.

defined( 'ABSPATH' ) || exit;

/**
 * Fills columns. Spec: Convert, Fields.
 */
class Agent_Ready_Fields {

	/**
	 * Every relation's columns, key first. Fields 1, 2.
	 */
	const RELATIONS = array(
		'sitemap'            => array( 'loc', 'lastmod' ),
		'posts'              => array( 'ID', 'loc', 'lastmod', 'post_type', 'post_title', 'post_author', 'post_date', 'post_parent', 'alternate' ),
		'taxonomies'         => array( 'term_taxonomy_id', 'term_id', 'loc', 'taxonomy', 'name', 'parent' ),
		'users'              => array( 'ID', 'loc', 'display_name' ),
		'term_relationships' => array( 'object_id', 'term_taxonomy_id' ),
		'post_content'       => array( 'ID', 'position', 'element', 'text' ),
	);

	/**
	 * The elements that are blocks of a page's text. Fields 4.
	 */
	const BLOCKS = array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'li', 'dt', 'dd', 'blockquote', 'pre', 'figcaption', 'td', 'th' );

	/**
	 * The elements that hold no text of the page.
	 */
	const SKIPPED = array( 'script', 'style', 'noscript', 'template' );

	/**
	 * Builds a sheet of a relation.
	 *
	 * @param string $name    The relation.
	 * @param array  $records The records.
	 * @return array The sheet.
	 */
	public static function sheet( $name, $records ) {
		return array(
			'name'    => $name,
			'header'  => self::RELATIONS[ $name ],
			'records' => $records,
		);
	}

	/**
	 * A date as W3C Datetime, as core's sitemap writes it. Fields 3.
	 *
	 * @param string $gmt A date from the database, in GMT.
	 * @return string The date.
	 */
	public static function lastmod( $gmt ) {
		return wp_date( DATE_W3C, strtotime( $gmt ) );
	}

	/**
	 * A row of sitemap.
	 *
	 * @param string $loc     The book's URL.
	 * @param string $lastmod When the book changed.
	 * @return array The row.
	 */
	public static function sitemap_row( $loc, $lastmod ) {
		return array( $loc, $lastmod );
	}

	/**
	 * A row of posts.
	 *
	 * @param WP_Post $post      The post.
	 * @param string  $alternate The URL of its page book.
	 * @return array The row.
	 */
	public static function post_row( $post, $alternate ) {
		return array(
			$post->ID,
			get_permalink( $post ),
			self::lastmod( $post->post_modified_gmt ),
			$post->post_type,
			$post->post_title,
			$post->post_author,
			$post->post_date,
			$post->post_parent ? $post->post_parent : '',
			$alternate,
		);
	}

	/**
	 * A row of taxonomies.
	 *
	 * @param WP_Term $term The term.
	 * @param string  $loc  Its URL.
	 * @return array The row.
	 */
	public static function term_row( $term, $loc ) {
		return array(
			$term->term_taxonomy_id,
			$term->term_id,
			$loc,
			$term->taxonomy,
			$term->name,
			$term->parent ? $term->parent : '',
		);
	}

	/**
	 * A row of users.
	 *
	 * @param WP_User $user The user.
	 * @return array The row.
	 */
	public static function user_row( $user ) {
		return array( $user->ID, get_author_posts_url( $user->ID ), $user->display_name );
	}

	/**
	 * The rows of post_content: the page as WordPress renders it, one row per
	 * block. Fields 4.
	 *
	 * @param WP_Post $post The post.
	 * @return array[] The rows.
	 */
	public static function content_rows( $post ) {
		$blocks = array();
		$html   = self::rendered( $post );
		if ( '' === trim( $html ) ) {
			return array();
		}

		$document = new DOMDocument();
		$document->loadHTML(
			'<?xml encoding="utf-8"?><div>' . $html . '</div>',
			LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NONET | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
		);
		foreach ( self::SKIPPED as $name ) {
			foreach ( iterator_to_array( $document->getElementsByTagName( $name ) ) as $node ) {
				$node->parentNode->removeChild( $node );
			}
		}
		foreach ( $document->childNodes as $node ) {
			if ( $node instanceof DOMElement ) {
				self::walk( $node, $blocks );
			}
		}

		$rows = array();
		foreach ( $blocks as $index => $block ) {
			$rows[] = array( $post->ID, $index + 1, $block[0], $block[1] );
		}
		return $rows;
	}

	/**
	 * Renders a post's content.
	 *
	 * @param WP_Post $post The post.
	 * @return string The HTML.
	 */
	private static function rendered( $post ) {
		$GLOBALS['post'] = $post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- the_content reads the global post.
		setup_postdata( $post );
		$html = apply_filters( 'the_content', get_the_content( null, false, $post ) );
		wp_reset_postdata();
		return (string) $html;
	}

	/**
	 * Collects the blocks of an element, in document order.
	 *
	 * @param DOMElement $element The element.
	 * @param array      $blocks  The blocks so far: element name, then text.
	 */
	private static function walk( $element, &$blocks ) {
		$name = strtolower( $element->nodeName );
		if ( in_array( $name, self::BLOCKS, true ) && ! self::has_block( $element ) ) {
			foreach ( 'pre' === $name ? explode( "\n", $element->textContent ) : array( $element->textContent ) as $text ) {
				self::add( $name, $text, $blocks );
			}
			return;
		}

		foreach ( $element->childNodes as $node ) {
			if ( $node instanceof DOMElement ) {
				self::walk( $node, $blocks );
			} elseif ( $node instanceof DOMText ) {
				self::add( $name, $node->nodeValue, $blocks );
			}
		}
	}

	/**
	 * Whether an element holds a block.
	 *
	 * @param DOMElement $element The element.
	 * @return bool Whether it does.
	 */
	private static function has_block( $element ) {
		foreach ( self::BLOCKS as $name ) {
			if ( $element->getElementsByTagName( $name )->length > 0 ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Adds a block, unless it holds only white space.
	 *
	 * @param string $name   The element's name.
	 * @param string $text   The text.
	 * @param array  $blocks The blocks so far.
	 */
	private static function add( $name, $text, &$blocks ) {
		$text = trim( preg_replace( '/\s+/u', ' ', $text ) );
		if ( '' !== $text ) {
			$blocks[] = array( $name, $text );
		}
	}
}
