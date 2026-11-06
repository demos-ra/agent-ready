<?php
/**
 * Route: answers the URLs of the books, converting on request.
 *
 * @package MTSV_Agent_Site
 */

defined( 'ABSPATH' ) || exit;

/**
 * Routes requests, as core routes its sitemaps. Spec: Publish, Route.
 */
class MTSV_Agent_Site_Route {

	/**
	 * The prefix of the routes sheet's paths. Route 4.
	 */
	const SLUG = 'agent-site';

	/**
	 * The query variable naming what is asked for: index, a provider's name
	 * or post. Route 4.
	 */
	const QUERY = 'mtsv_agent_site';

	/**
	 * The query variable giving a provider's subtype. Route 4.
	 */
	const SUBTYPE = 'mtsv_agent_site_subtype';

	/**
	 * The query variable giving a page or a post's ID. Route 4.
	 */
	const NUMBER = 'mtsv_agent_site_number';

	/**
	 * Hooks into WordPress. Route 1, 3.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_action( 'init', array( __CLASS__, 'flush_when_missing' ), 11 );
		add_filter( 'query_vars', array( __CLASS__, 'query_vars' ) );
		add_action( 'template_redirect', array( __CLASS__, 'render' ), 9 );
	}

	/**
	 * Adds the routes the routes sheet lists, at the top, as core adds its
	 * sitemap routes. Route 1, 4.
	 */
	public static function register() {
		$slug      = preg_quote( self::SLUG, '/' );
		$providers = implode( '|', MTSV_Agent_Site_Providers::NAMES );

		add_rewrite_rule( self::index_rule(), 'index.php?' . self::QUERY . '=index', 'top' );
		add_rewrite_rule(
			'^' . $slug . '/(' . $providers . ')-([a-z\d_-]+?)-(\d+?)\.mtsv$',
			'index.php?' . self::QUERY . '=$matches[1]&' . self::SUBTYPE . '=$matches[2]&' . self::NUMBER . '=$matches[3]',
			'top'
		);
		add_rewrite_rule(
			'^' . $slug . '/(' . $providers . ')-(\d+?)\.mtsv$',
			'index.php?' . self::QUERY . '=$matches[1]&' . self::NUMBER . '=$matches[2]',
			'top'
		);
		add_rewrite_rule( '^' . $slug . '/post-(\d+)\.mtsv$', 'index.php?' . self::QUERY . '=post&' . self::NUMBER . '=$matches[1]', 'top' );
	}

	/**
	 * The index route's rule, as WordPress saves it. Route 1, 4.
	 *
	 * @return string The rule.
	 */
	private static function index_rule() {
		return '^' . preg_quote( self::SLUG, '/' ) . '/index\.mtsv$';
	}

	/**
	 * Flushes the routes when WordPress's saved rules lack them, as after an
	 * update, after every route is added. Plain permalinks save no rules.
	 * Route 1.
	 */
	public static function flush_when_missing() {
		global $wp_rewrite;

		if ( ! $wp_rewrite->using_permalinks() ) {
			return;
		}
		$rules = get_option( 'rewrite_rules' );
		if ( ! is_array( $rules ) || ! isset( $rules[ self::index_rule() ] ) ) {
			flush_rewrite_rules();
		}
	}

	/**
	 * Lets WordPress read the query variables. Route 4.
	 *
	 * @param string[] $vars The public query variables.
	 * @return string[] The variables.
	 */
	public static function query_vars( $vars ) {
		$vars[] = self::QUERY;
		$vars[] = self::SUBTYPE;
		$vars[] = self::NUMBER;
		return $vars;
	}

	/**
	 * Adds the routes and flushes them. Route 1.
	 */
	public static function activate() {
		self::register();
		flush_rewrite_rules();
	}

	/**
	 * Flushes the routes. Route 1.
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}

	/**
	 * Gives a URL, as a query string under plain permalinks. Route 2.
	 *
	 * @param string $path   The path, under permalinks.
	 * @param array  $params The query variables, under plain permalinks.
	 * @return string The URL.
	 */
	public static function url( $path, $params ) {
		global $wp_rewrite;

		if ( $wp_rewrite->using_permalinks() ) {
			return home_url( $path );
		}
		return add_query_arg( array_filter( $params ), home_url( '/' ) );
	}

	/**
	 * The URL of the index book. Route 2, 4.
	 *
	 * @return string The URL.
	 */
	public static function index_url() {
		return self::url( '/' . self::SLUG . '/index.mtsv', array( self::QUERY => 'index' ) );
	}

	/**
	 * The URL of a provider's book, named as core names its sitemaps.
	 * Route 2, 4.
	 *
	 * @param string $name    The provider's name.
	 * @param string $subtype The subtype; empty for users.
	 * @param int    $page    The page.
	 * @return string The URL.
	 */
	public static function book_url( $name, $subtype, $page ) {
		$params = array(
			self::QUERY   => $name,
			self::SUBTYPE => $subtype,
			self::NUMBER  => $page,
		);
		return self::url( '/' . self::SLUG . '/' . implode( '-', array_filter( $params ) ) . '.mtsv', $params );
	}

	/**
	 * The URL of a post's page book. Route 2, 4.
	 *
	 * @param int $post_id The post's ID.
	 * @return string The URL.
	 */
	public static function post_url( $post_id ) {
		return self::url(
			'/' . self::SLUG . '/post-' . $post_id . '.mtsv',
			array(
				self::QUERY  => 'post',
				self::NUMBER => $post_id,
			)
		);
	}

	/**
	 * Converts and sends what is asked for, or answers 404 for a book with no
	 * rows, a post that is not public, a request while the site is not
	 * public, or an unknown route. Route 3; Response 2.
	 */
	public static function render() {
		$query = get_query_var( self::QUERY );
		if ( ! $query ) {
			return;
		}

		$query   = sanitize_text_field( $query );
		$subtype = sanitize_text_field( get_query_var( self::SUBTYPE ) );
		$number  = absint( get_query_var( self::NUMBER ) );
		$text    = null;

		if ( MTSV_Agent_Site_Visibility::is_site_public() ) {
			if ( 'index' === $query ) {
				$text = MTSV_Agent_Site_Writer::write( MTSV_Agent_Site_Relations::index( array( __CLASS__, 'book_url' ) ) );
			} elseif ( in_array( $query, MTSV_Agent_Site_Providers::NAMES, true ) ) {
				$sheets = MTSV_Agent_Site_Relations::book( $query, $subtype, max( 1, $number ), array( __CLASS__, 'post_url' ) );
				$text   = $sheets ? MTSV_Agent_Site_Writer::write( $sheets ) : null;
			} elseif ( 'post' === $query ) {
				$post = $number ? get_post( $number ) : null;
				$text = $post && MTSV_Agent_Site_Visibility::is_post_public( $post ) ? MTSV_Agent_Site_Writer::write( MTSV_Agent_Site_Relations::page( $post ) ) : null;
			}
		}

		if ( null === $text ) {
			MTSV_Agent_Site_Response::not_found();
			return;
		}
		MTSV_Agent_Site_Discovery::link_header();
		MTSV_Agent_Site_Response::send( $text, MTSV_Agent_Site_Response::MTSV_TYPE );
		exit;
	}
}
