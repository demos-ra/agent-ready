<?php
/**
 * Fields: each sheet's columns, keys and references, as the spec's fields
 * sheet lists them.
 *
 * @package MTSV_Agent_Site
 */

// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- PHP's own DOM property names.

defined( 'ABSPATH' ) || exit;

/**
 * Fills columns. Spec: Convert, Fields.
 */
class MTSV_Agent_Site_Fields {

	/**
	 * The schema, as the spec's fields sheet lists it: relation, field, key,
	 * references. The index book publishes it as its fields sheet. Fields 1, 2.
	 */
	const SCHEMA = array(
		array( 'sitemap', 'loc', 'yes', '' ),
		array( 'fields', 'relation', 'yes', '' ),
		array( 'fields', 'field', 'yes', '' ),
		array( 'fields', 'key', '', '' ),
		array( 'fields', 'references', '', '' ),
		array( 'posts', 'ID', 'yes', '' ),
		array( 'posts', 'post_author', '', 'users.ID' ),
		array( 'posts', 'post_date', '', '' ),
		array( 'posts', 'post_title', '', '' ),
		array( 'posts', 'post_parent', '', 'posts.ID' ),
		array( 'posts', 'post_type', '', '' ),
		array( 'posts', 'loc', '', '' ),
		array( 'posts', 'lastmod', '', '' ),
		array( 'posts', 'alternate', '', '' ),
		array( 'term_relationships', 'object_id', 'yes', 'posts.ID' ),
		array( 'term_relationships', 'term_taxonomy_id', 'yes', 'term_taxonomy.term_taxonomy_id' ),
		array( 'terms', 'term_id', 'yes', '' ),
		array( 'terms', 'name', '', '' ),
		array( 'term_taxonomy', 'term_taxonomy_id', 'yes', '' ),
		array( 'term_taxonomy', 'term_id', '', 'terms.term_id' ),
		array( 'term_taxonomy', 'taxonomy', '', '' ),
		array( 'term_taxonomy', 'parent', '', 'terms.term_id' ),
		array( 'term_taxonomy', 'loc', '', '' ),
		array( 'users', 'ID', 'yes', '' ),
		array( 'users', 'display_name', '', '' ),
		array( 'users', 'loc', '', '' ),
		array( 'post_content', 'ID', 'yes', 'posts.ID' ),
		array( 'post_content', 'position', 'yes', '' ),
		array( 'post_content', 'element', '', '' ),
		array( 'post_content', 'text', '', '' ),
	);

	/**
	 * The elements that are phrasing content, WHATWG 3.2.5.2.5. Autonomous
	 * custom elements and text are phrasing content too. Fields 4.
	 */
	const PHRASING = array( 'a', 'abbr', 'area', 'audio', 'b', 'bdi', 'bdo', 'br', 'button', 'canvas', 'cite', 'code', 'data', 'datalist', 'del', 'dfn', 'em', 'embed', 'i', 'iframe', 'img', 'input', 'ins', 'kbd', 'label', 'link', 'map', 'mark', 'math', 'meta', 'meter', 'noscript', 'object', 'output', 'picture', 'progress', 'q', 'ruby', 's', 'samp', 'script', 'select', 'selectedcontent', 'slot', 'small', 'span', 'strong', 'sub', 'sup', 'svg', 'template', 'textarea', 'time', 'u', 'var', 'video', 'wbr' );

	/**
	 * The elements that are metadata content, WHATWG 3.2.5.2.1: they give no
	 * text. Fields 4.
	 */
	const METADATA = array( 'base', 'link', 'meta', 'noscript', 'script', 'style', 'template', 'title' );

	/**
	 * The elements replaced by their contents when finding runs, WHATWG
	 * 3.2.5.4. Fields 4.
	 */
	const TRANSPARENT = array( 'a', 'ins', 'del', 'map' );

	/**
	 * A relation's columns, in the schema's order. Fields 1, 2.
	 *
	 * @param string $name The relation.
	 * @return string[] The column names.
	 */
	public static function columns( $name ) {
		return array_column( wp_list_filter( self::SCHEMA, array( 0 => $name ) ), 1 );
	}

	/**
	 * Builds a sheet of a relation, its columns as the schema lists them.
	 * Fields 1, 2.
	 *
	 * @param string $name    The relation.
	 * @param array  $records The records.
	 * @return array The sheet.
	 */
	public static function sheet( $name, $records ) {
		return array(
			'name'    => $name,
			'header'  => self::columns( $name ),
			'records' => $records,
		);
	}

	/**
	 * The rows of fields: the schema itself. Fields 2.
	 *
	 * @return array[] The rows.
	 */
	public static function fields_rows() {
		return self::SCHEMA;
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
	 * A row of sitemap. Fields 1, 2.
	 *
	 * @param string $loc The book's URL.
	 * @return array The row.
	 */
	public static function sitemap_row( $loc ) {
		return array( $loc );
	}

	/**
	 * A row of posts. Fields 1-3.
	 *
	 * @param WP_Post $post      The post.
	 * @param string  $alternate The URL of its page book.
	 * @return array The row.
	 */
	public static function post_row( $post, $alternate ) {
		return array(
			$post->ID,
			$post->post_author,
			$post->post_date,
			$post->post_title,
			$post->post_parent ? $post->post_parent : '',
			$post->post_type,
			get_permalink( $post ),
			self::lastmod( $post->post_modified_gmt ),
			$alternate,
		);
	}

	/**
	 * A row of terms. Fields 1, 2.
	 *
	 * @param WP_Term $term The term.
	 * @return array The row.
	 */
	public static function terms_row( $term ) {
		return array( $term->term_id, $term->name );
	}

	/**
	 * A row of term_taxonomy. Fields 1, 2.
	 *
	 * @param WP_Term $term The term.
	 * @param string  $loc  Its URL.
	 * @return array The row.
	 */
	public static function term_taxonomy_row( $term, $loc ) {
		return array(
			$term->term_taxonomy_id,
			$term->term_id,
			$term->taxonomy,
			$term->parent ? $term->parent : '',
			$loc,
		);
	}

	/**
	 * A row of users. Fields 1, 2.
	 *
	 * @param WP_User $user The user.
	 * @return array The row.
	 */
	public static function user_row( $user ) {
		return array( $user->ID, $user->display_name, get_author_posts_url( $user->ID ) );
	}

	/**
	 * The rows of post_content: the page as WordPress renders it, one row per
	 * run of phrasing content, named by the element holding it, and with an
	 * empty element outside every element of the post. Fields 4.
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
		foreach ( self::METADATA as $name ) {
			foreach ( iterator_to_array( $document->getElementsByTagName( $name ) ) as $node ) {
				$node->parentNode->removeChild( $node );
			}
		}
		foreach ( $document->childNodes as $wrapper ) {
			if ( $wrapper instanceof DOMElement ) {
				self::walk( $wrapper, $blocks, '' );
			}
		}

		$rows = array();
		foreach ( $blocks as $index => $block ) {
			$rows[] = array( $post->ID, $index + 1, $block[0], $block[1] );
		}
		return $rows;
	}

	/**
	 * Renders a post's content, as WordPress shows it. Fields 4.
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
	 * Collects the rows of an element, in document order: each run of
	 * phrasing content among its child nodes, then the rows of each child
	 * element that is other content. Fields 4.
	 *
	 * @param DOMElement  $element The element.
	 * @param array       $blocks  The blocks so far: element name, then text.
	 * @param string|null $name    The name its runs take; null for its own.
	 */
	private static function walk( $element, &$blocks, $name = null ) {
		$name = null === $name ? strtolower( $element->nodeName ) : $name;
		$run  = '';
		foreach ( self::view( $element ) as $node ) {
			if ( $node instanceof DOMText ) {
				$run .= $node->nodeValue;
			} elseif ( $node instanceof DOMElement && self::is_phrasing( $node ) ) {
				$run .= $node->textContent;
			} elseif ( $node instanceof DOMElement ) {
				self::add( $name, $run, $blocks );
				$run = '';
				self::walk( $node, $blocks );
			}
		}
		self::add( $name, $run, $blocks );
	}

	/**
	 * An element's child nodes, with a, ins, del and map replaced by their
	 * contents, as WHATWG 3.2.5.4 views the DOM. Comment nodes are passed
	 * over by walk, as WHATWG 3.2.5 ignores them. Fields 4.
	 *
	 * @param DOMElement $element The element.
	 * @return DOMNode[] The nodes.
	 */
	private static function view( $element ) {
		$nodes = array();
		foreach ( $element->childNodes as $node ) {
			if ( $node instanceof DOMElement && in_array( strtolower( $node->nodeName ), self::TRANSPARENT, true ) ) {
				$nodes = array_merge( $nodes, self::view( $node ) );
			} else {
				$nodes[] = $node;
			}
		}
		return $nodes;
	}

	/**
	 * Whether an element is phrasing content: one WHATWG 3.2.5.2.5 lists,
	 * area only inside map, selectedcontent only inside a button in select,
	 * or an autonomous custom element. Fields 4.
	 *
	 * @param DOMElement $element The element.
	 * @return bool Whether it is.
	 */
	private static function is_phrasing( $element ) {
		$name = strtolower( $element->nodeName );
		if ( 'area' === $name ) {
			return self::has_ancestor( $element, array( 'map' ) );
		}
		if ( 'selectedcontent' === $name ) {
			return self::has_ancestor( $element, array( 'button', 'select' ) );
		}
		return in_array( $name, self::PHRASING, true ) || false !== strpos( $name, '-' );
	}

	/**
	 * Whether an element is inside a chain of ancestors, nearest first.
	 * Fields 4.
	 *
	 * @param DOMElement $element The element.
	 * @param string[]   $names   The ancestors' names, the nearest first.
	 * @return bool Whether it is.
	 */
	private static function has_ancestor( $element, $names ) {
		$node = $element->parentNode;
		foreach ( $names as $name ) {
			while ( $node instanceof DOMElement && strtolower( $node->nodeName ) !== $name ) {
				$node = $node->parentNode;
			}
			if ( ! $node instanceof DOMElement ) {
				return false;
			}
			$node = $node->parentNode;
		}
		return true;
	}

	/**
	 * Adds a block, unless it holds only white space, which WHATWG 3.2.5.4
	 * does not count as a paragraph. Fields 4.
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
