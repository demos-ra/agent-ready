<?php
/**
 * Writer: sheets as MTSV text, split into books at the size limits.
 *
 * A sheet is an array with the keys name, header and records.
 *
 * @package Agent_Sitemap
 */

defined( 'ABSPATH' ) || exit;

/**
 * Writes MTSV. Spec: Convert, Writer.
 */
class Agent_Sitemap_Writer {

	/**
	 * The most URLs one book holds. Writer 4.
	 */
	const MAX_URLS = 50000;

	/**
	 * The most bytes one book holds, less room for its sitemap sheet. Writer 4.
	 */
	const MAX_BYTES = 51380224;

	/**
	 * Replaces each character a field cannot hold with a space. Writer 2.
	 *
	 * @param mixed $value The value.
	 * @return string The field.
	 */
	public static function field( $value ) {
		return preg_replace( '/[\t\n\f\r]/', ' ', (string) $value );
	}

	/**
	 * Writes one record. Writer 1.
	 *
	 * @param array $fields The values.
	 * @return string The record, ending with a line break.
	 */
	public static function record( $fields ) {
		return implode( "\t", array_map( array( __CLASS__, 'field' ), $fields ) ) . "\n";
	}

	/**
	 * Writes a book, with a form feed line before every sheet. Writer 1.
	 *
	 * @param array $sheets The sheets, in order.
	 * @return string The MTSV text.
	 */
	public static function write( $sheets ) {
		$text = '';
		foreach ( $sheets as $sheet ) {
			$text .= "\f" . self::field( $sheet['name'] ) . "\n";
			if ( null === $sheet['header'] ) {
				continue;
			}
			$text .= self::record( $sheet['header'] );
			foreach ( $sheet['records'] as $record ) {
				$text .= self::record( $record );
			}
		}
		return $text;
	}

	/**
	 * Splits the main book's sheets into books at the limits, in ID order.
	 *
	 * Taxonomies and users stay in the first book. A post's rows stay in one
	 * book. Writer 4.
	 *
	 * @param array $sheets The sheets, keyed by name.
	 * @return array[] The books, each its sheets keyed by name.
	 */
	public static function split( $sheets ) {
		$pairs = array();
		foreach ( $sheets['term_relationships']['records'] as $record ) {
			$pairs[ $record[0] ][] = $record;
		}

		$empty = $sheets;
		foreach ( array_keys( $empty ) as $name ) {
			$empty[ $name ]['records'] = array();
		}

		$book               = $empty;
		$book['taxonomies'] = $sheets['taxonomies'];
		$book['users']      = $sheets['users'];
		$urls               = count( $sheets['taxonomies']['records'] ) + count( $sheets['users']['records'] );
		$bytes              = strlen( self::write( $book ) );
		$books              = array();
		$has_posts          = false;

		foreach ( $sheets['posts']['records'] as $record ) {
			$rows = isset( $pairs[ $record[0] ] ) ? $pairs[ $record[0] ] : array();
			$size = strlen( self::record( $record ) );
			foreach ( $rows as $row ) {
				$size += strlen( self::record( $row ) );
			}

			if ( $has_posts && ( $urls + 1 > self::MAX_URLS || $bytes + $size > self::MAX_BYTES ) ) {
				$books[]   = $book;
				$book      = $empty;
				$urls      = 0;
				$bytes     = strlen( self::write( $book ) );
				$has_posts = false;
			}

			$book['posts']['records'][] = $record;
			foreach ( $rows as $row ) {
				$book['term_relationships']['records'][] = $row;
			}
			++$urls;
			$bytes    += $size;
			$has_posts = true;
		}

		$books[] = $book;
		return $books;
	}
}
