<?php
/**
 * Route: stores the main book and answers the URLs of every book.
 *
 * @package Agent_Sitemap
 */

defined( 'ABSPATH' ) || exit;

/**
 * Routes requests. Spec: Publish, Route.
 */
class Agent_Sitemap_Route {

	/**
	 * The query variable naming what is asked for.
	 */
	const QUERY = 'agent_sitemap';

	/**
	 * The query variable giving a book's number or a post's ID.
	 */
	const NUMBER = 'agent_sitemap_number';

	/**
	 * The option listing the stored books: loc, lastmod.
	 */
	const BOOKS = 'agent_sitemap_books';

	/**
	 * The event that rebuilds the main book.
	 */
	const REBUILD = 'agent_sitemap_rebuild';

	/**
	 * Hooks into WordPress.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_filter( 'query_vars', array( __CLASS__, 'query_vars' ) );
		add_action( 'template_redirect', array( __CLASS__, 'render' ), 9 );
		add_action( self::REBUILD, array( __CLASS__, 'rebuild' ) );

		foreach ( array( 'save_post', 'deleted_post', 'created_term', 'edited_term', 'delete_term', 'profile_update', 'deleted_user' ) as $change ) {
			add_action( $change, array( __CLASS__, 'schedule' ) );
		}
	}

	/**
	 * Adds the routes. Route 1.
	 */
	public static function register() {
		$slug = preg_quote( AGENT_SITEMAP_SLUG, '/' );

		add_rewrite_rule( '^' . $slug . '\.mtsv$', 'index.php?' . self::QUERY . '=book&' . self::NUMBER . '=1', 'top' );
		add_rewrite_rule( '^' . $slug . '-(\d+)\.mtsv$', 'index.php?' . self::QUERY . '=book&' . self::NUMBER . '=$matches[1]', 'top' );
		add_rewrite_rule( '^' . $slug . '-post-(\d+)\.mtsv$', 'index.php?' . self::QUERY . '=post&' . self::NUMBER . '=$matches[1]', 'top' );
		add_rewrite_rule( '^llms\.txt$', 'index.php?' . self::QUERY . '=llms', 'top' );
	}

	/**
	 * Lets WordPress read the query variables.
	 *
	 * @param string[] $vars The public query variables.
	 * @return string[] The variables.
	 */
	public static function query_vars( $vars ) {
		$vars[] = self::QUERY;
		$vars[] = self::NUMBER;
		return $vars;
	}

	/**
	 * Adds the routes, flushes them and builds the main book. Route 1, 3.
	 */
	public static function activate() {
		self::register();
		flush_rewrite_rules();
		self::rebuild();
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
	 * @param string $query  What is asked for.
	 * @param int    $number The book's number or the post's ID.
	 * @return string The URL.
	 */
	private static function url( $path, $query, $number = 0 ) {
		global $wp_rewrite;

		if ( $wp_rewrite->using_permalinks() ) {
			return home_url( $path );
		}
		return add_query_arg(
			array_filter(
				array(
					self::QUERY  => $query,
					self::NUMBER => $number,
				)
			),
			home_url( '/' )
		);
	}

	/**
	 * The URL of a book of the main book.
	 *
	 * @param int $number The book's number.
	 * @return string The URL.
	 */
	public static function book_url( $number = 1 ) {
		$suffix = 1 === $number ? '' : '-' . $number;
		return self::url( '/' . AGENT_SITEMAP_SLUG . $suffix . '.mtsv', 'book', $number );
	}

	/**
	 * The URL of a post's page book.
	 *
	 * @param int $post_id The post's ID.
	 * @return string The URL.
	 */
	public static function post_url( $post_id ) {
		return self::url( '/' . AGENT_SITEMAP_SLUG . '-post-' . $post_id . '.mtsv', 'post', $post_id );
	}

	/**
	 * The URL of llms.txt.
	 *
	 * @return string The URL.
	 */
	public static function llms_url() {
		return self::url( '/llms.txt', 'llms' );
	}

	/**
	 * Schedules one rebuild, so a burst of changes rebuilds once. Route 3.
	 */
	public static function schedule() {
		if ( ! wp_next_scheduled( self::REBUILD ) ) {
			wp_schedule_single_event( time() + MINUTE_IN_SECONDS, self::REBUILD );
		}
	}

	/**
	 * Opens WordPress's file system.
	 *
	 * @return WP_Filesystem_Base|false The file system, or false.
	 */
	public static function filesystem() {
		global $wp_filesystem;

		require_once ABSPATH . 'wp-admin/includes/file.php';
		return WP_Filesystem() ? $wp_filesystem : false;
	}

	/**
	 * The folder of the stored books.
	 *
	 * @return string The path, with a trailing slash.
	 */
	private static function folder() {
		$uploads = wp_upload_dir();
		return trailingslashit( $uploads['basedir'] ) . AGENT_SITEMAP_SLUG . '/';
	}

	/**
	 * Stores the main book's books, and lists them. Route 3.
	 */
	public static function rebuild() {
		$filesystem = self::filesystem();
		if ( ! $filesystem ) {
			return;
		}

		$folder = self::folder();
		wp_mkdir_p( $folder );

		$books   = Agent_Sitemap_Writer::split( Agent_Sitemap_Relations::main( array( __CLASS__, 'post_url' ) ) );
		$sitemap = array();
		foreach ( $books as $index => $book ) {
			$modified  = self::newest( $book['posts']['records'] );
			$sitemap[] = Agent_Sitemap_Fields::sitemap_row( self::book_url( $index + 1 ), Agent_Sitemap_Fields::lastmod( gmdate( 'Y-m-d H:i:s', $modified ) ) );
		}

		foreach ( $books as $index => $book ) {
			$book['sitemap']['records'] = $sitemap;
			$filesystem->put_contents( $folder . ( $index + 1 ) . '.mtsv', Agent_Sitemap_Writer::write( $book ) );
		}

		$number = count( $books ) + 1;
		while ( $filesystem->exists( $folder . $number . '.mtsv' ) ) {
			$filesystem->delete( $folder . $number . '.mtsv' );
			++$number;
		}

		update_option( self::BOOKS, $sitemap, false );
	}

	/**
	 * The newest lastmod of a book's posts. Response 2.
	 *
	 * @param array[] $records The rows of posts.
	 * @return int The Unix timestamp; now, if there are none.
	 */
	private static function newest( $records ) {
		if ( empty( $records ) ) {
			return time();
		}
		$lastmod = array_search( 'lastmod', Agent_Sitemap_Fields::RELATIONS['posts'], true );
		return max( array_map( 'strtotime', array_column( $records, $lastmod ) ) );
	}

	/**
	 * Answers a request for a book or llms.txt. Visibility 2, Route 3.
	 */
	public static function render() {
		$query = get_query_var( self::QUERY );
		if ( ! $query ) {
			return;
		}

		$number = absint( get_query_var( self::NUMBER ) );
		$done   = false;
		if ( Agent_Sitemap_Visibility::is_site_public() ) {
			if ( 'book' === $query ) {
				$done = self::send_book( $number );
			} elseif ( 'post' === $query ) {
				$done = self::send_post( $number );
			} elseif ( 'llms' === $query ) {
				Agent_Sitemap_Response::send( Agent_Sitemap_Discovery::llms_text(), Agent_Sitemap_Response::LLMS_TYPE, 0 );
				$done = true;
			}
		}

		if ( $done ) {
			exit;
		}
		Agent_Sitemap_Response::not_found();
	}

	/**
	 * Sends a stored book.
	 *
	 * @param int $number The book's number.
	 * @return bool Whether it was sent.
	 */
	private static function send_book( $number ) {
		$books      = get_option( self::BOOKS, array() );
		$filesystem = self::filesystem();
		$path       = self::folder() . $number . '.mtsv';
		if ( ! $number || ! isset( $books[ $number - 1 ] ) || ! $filesystem || ! $filesystem->exists( $path ) ) {
			return false;
		}

		Agent_Sitemap_Response::send( $filesystem->get_contents( $path ), Agent_Sitemap_Response::MTSV_TYPE, strtotime( $books[ $number - 1 ][1] ) );
		return true;
	}

	/**
	 * Renders and sends a post's page book. Route 3.
	 *
	 * @param int $post_id The post's ID.
	 * @return bool Whether it was sent.
	 */
	private static function send_post( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post || ! Agent_Sitemap_Visibility::is_post_public( $post ) ) {
			return false;
		}

		$sitemap = get_option( self::BOOKS, array() );
		$text    = Agent_Sitemap_Writer::write( Agent_Sitemap_Relations::page( $post, $sitemap ) );
		Agent_Sitemap_Response::send( $text, Agent_Sitemap_Response::MTSV_TYPE, strtotime( $post->post_modified_gmt ) );
		return true;
	}
}
