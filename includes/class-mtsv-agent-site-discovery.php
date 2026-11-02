<?php
/**
 * Discovery: points agents to the books.
 *
 * @package MTSV_Agent_Site
 */

defined( 'ABSPATH' ) || exit;

/**
 * Serves llms.txt and writes the link tags. Spec: Publish, Discovery.
 */
class MTSV_Agent_Site_Discovery {

	/**
	 * The query variable asking for llms.txt, as core asks for robots.txt.
	 * Discovery 2; the routes sheet's llms row.
	 */
	const QUERY = 'mtsv_agent_site_llms';

	/**
	 * Hooks into WordPress. Discovery 2-4.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_filter( 'query_vars', array( __CLASS__, 'query_vars' ) );
		add_action( 'template_redirect', array( __CLASS__, 'render' ), 9 );
		add_action( 'wp_head', array( __CLASS__, 'links' ) );
	}

	/**
	 * Adds the llms.txt route at the top, only when the site is installed at
	 * the root. Discovery 2.
	 */
	public static function register() {
		if ( self::is_root() ) {
			add_rewrite_rule( '^llms\.txt$', 'index.php?' . self::QUERY . '=1', 'top' );
		}
	}

	/**
	 * Lets WordPress read the query variable. Discovery 2.
	 *
	 * @param string[] $vars The public query variables.
	 * @return string[] The variables.
	 */
	public static function query_vars( $vars ) {
		$vars[] = self::QUERY;
		return $vars;
	}

	/**
	 * Adds the route and flushes the routes on activate. Discovery 2.
	 */
	public static function activate() {
		self::register();
		flush_rewrite_rules();
	}

	/**
	 * Whether the site is installed at the root, as core asks before adding
	 * robots.txt. Discovery 2, 4.
	 *
	 * @return bool Whether it is.
	 */
	public static function is_root() {
		$path = wp_parse_url( home_url(), PHP_URL_PATH );
		return empty( $path ) || '/' === $path;
	}

	/**
	 * The URL of llms.txt. Discovery 2, 4.
	 *
	 * @return string The URL.
	 */
	public static function llms_url() {
		return MTSV_Agent_Site_Route::url( '/llms.txt', array( self::QUERY => 1 ) );
	}

	/**
	 * The detail paragraphs of llms.txt. Discovery 1.
	 */
	const DETAILS = array(
		'The whole site as linked tables: every public post, page, category, tag and author, and each post\'s text, listed from one index below. Read it instead of crawling HTML, an XML sitemap of bare URLs, or a JSON API that repeats every key.',
		'MTSV: TSV with sheets, the floor of structured text. `\t` between fields, `\n` between rows, `\f` before each sheet\'s name line, then its column names, then its rows. Sheets link by ID: join, filter and aggregate like a database. No quotes, braces, escaping or repeated keys: one pass, nearly every token is data.',
	);

	/**
	 * The Optional section's links: name, URL, notes. Discovery 1.
	 */
	const OPTIONAL = array(
		array( 'MTSV', 'https://datatracker.ietf.org/doc/draft-demosra-mtsv/', 'the format\'s specification' ),
		array( 'mtsv', 'https://github.com/demos-ra/mtsv', 'converts MTSV to and from other formats' ),
		array( 'text-spreadsheet', 'https://github.com/demos-ra/text-spreadsheet', 'an MCP server that reads and filters MTSV' ),
	);

	/**
	 * The text of llms.txt: H1, blockquote, detail paragraphs, the agent
	 * site's file list, then the Optional section. Discovery 1.
	 *
	 * @return string The Markdown.
	 */
	public static function llms_text() {
		$text    = '# ' . get_bloginfo( 'name' ) . "\n\n";
		$tagline = get_bloginfo( 'description' );
		if ( '' !== $tagline ) {
			$text .= '> ' . $tagline . "\n\n";
		}
		foreach ( self::DETAILS as $paragraph ) {
			$text .= $paragraph . "\n\n";
		}
		$text .= "## Agent site\n\n- [Agent site](" . MTSV_Agent_Site_Route::index_url() . "): the index; start here\n\n## Optional\n\n";
		foreach ( self::OPTIONAL as $link ) {
			$text .= '- [' . $link[0] . '](' . $link[1] . '): ' . $link[2] . "\n";
		}
		return $text;
	}

	/**
	 * Sends llms.txt, or answers 404 while the site is not public or not
	 * installed at the root. Discovery 2; Response 2, 3.
	 */
	public static function render() {
		if ( ! get_query_var( self::QUERY ) ) {
			return;
		}
		if ( ! MTSV_Agent_Site_Visibility::is_site_public() || ! self::is_root() ) {
			MTSV_Agent_Site_Response::not_found();
			return;
		}
		MTSV_Agent_Site_Response::send( self::llms_text(), MTSV_Agent_Site_Response::LLMS_TYPE );
		exit;
	}

	/**
	 * Writes the link tags. Discovery 3, 4.
	 */
	public static function links() {
		if ( ! MTSV_Agent_Site_Visibility::is_site_public() ) {
			return;
		}

		if ( self::is_root() ) {
			printf( '<link rel="describedby" href="%s">' . "\n", esc_url( self::llms_url() ) );
		}

		$post = get_queried_object();
		if ( is_singular() && $post instanceof WP_Post && MTSV_Agent_Site_Visibility::is_post_public( $post ) ) {
			printf(
				'<link rel="alternate" type="%s" href="%s">' . "\n",
				esc_attr( MTSV_Agent_Site_Response::MTSV_MEDIA ),
				esc_url( MTSV_Agent_Site_Route::post_url( $post->ID ) )
			);
		}
	}
}
