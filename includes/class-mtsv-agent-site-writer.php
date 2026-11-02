<?php
/**
 * Writer: sheets as MTSV text.
 *
 * A sheet is an array with the keys name, header and records.
 *
 * @package MTSV_Agent_Site
 */

defined( 'ABSPATH' ) || exit;

/**
 * Writes MTSV. Spec: Convert, Writer.
 *
 * Writer 3 is checked by writing mtsv's conforming files with write().
 */
class MTSV_Agent_Site_Writer {

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
}
