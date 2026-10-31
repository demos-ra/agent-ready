<?php
/**
 * Discovery: points agents to the books.
 *
 * @package Agent_Ready
 */

defined( 'ABSPATH' ) || exit;

/**
 * Serves llms.txt, writes the link tags and handles an existing llms.txt.
 * Spec: Publish, Discovery.
 */
class Agent_Ready_Discovery {

	/**
	 * The option set when the notice is dismissed.
	 */
	const DISMISSED = 'agent_ready_notice_dismissed';

	/**
	 * Hooks into WordPress.
	 */
	public static function init() {
		add_action( 'wp_head', array( __CLASS__, 'links' ) );
		add_action( 'admin_notices', array( __CLASS__, 'notice' ) );
		add_action( 'admin_post_agent_ready_replace', array( __CLASS__, 'replace' ) );
		add_action( 'admin_post_agent_ready_dismiss', array( __CLASS__, 'dismiss' ) );
	}

	/**
	 * The text of llms.txt: H1 title, blockquote tagline, link list. Discovery 1.
	 *
	 * @return string The Markdown.
	 */
	public static function llms_text() {
		$text    = '# ' . get_bloginfo( 'name' ) . "\n\n";
		$tagline = get_bloginfo( 'description' );
		if ( '' !== $tagline ) {
			$text .= '> ' . $tagline . "\n\n";
		}
		return $text . "## Agent sitemap\n\n- [Agent sitemap](" . Agent_Ready_Route::book_url() . "): every public page of this site as text\n";
	}

	/**
	 * Writes the link tags. Discovery 3, 4.
	 */
	public static function links() {
		if ( ! Agent_Ready_Visibility::is_site_public() ) {
			return;
		}

		printf( '<link rel="describedby" href="%s">' . "\n", esc_url( Agent_Ready_Route::llms_url() ) );

		$post = get_queried_object();
		if ( is_singular() && $post instanceof WP_Post && Agent_Ready_Visibility::is_post_public( $post ) ) {
			printf(
				'<link rel="alternate" type="%s" href="%s">' . "\n",
				esc_attr( Agent_Ready_Response::MTSV_MEDIA ),
				esc_url( Agent_Ready_Route::post_url( $post->ID ) )
			);
		}
	}

	/**
	 * The path of the site's llms.txt.
	 *
	 * @return string The path.
	 */
	private static function path() {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		return get_home_path() . 'llms.txt';
	}

	/**
	 * Shows the notice for an existing llms.txt. Discovery 5, 6.
	 */
	public static function notice() {
		if ( get_option( self::DISMISSED ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$path = self::path();
		if ( ! file_exists( $path ) ) {
			return;
		}

		if ( ! wp_is_writable( dirname( $path ) ) ) {
			printf(
				'<div class="notice notice-warning"><p>%s</p></div>',
				esc_html(
					sprintf(
						/* translators: %s: a folder on the server. */
						__( 'Your site already has an llms.txt, and Agent Ready cannot replace it. To use Agent Ready\'s, make %s writable by the web server, or rename the file yourself.', 'agent-ready' ),
						dirname( $path )
					)
				)
			);
		}

		echo '<div class="notice notice-info"><p>' . esc_html__( 'Your site already has an llms.txt.', 'agent-ready' ) . '</p><p>';
		self::button( 'agent_ready_replace', __( 'Use Agent Ready\'s', 'agent-ready' ), 'button button-primary' );
		echo ' ';
		self::button( 'agent_ready_dismiss', __( 'Dismiss', 'agent-ready' ), 'button' );
		echo '</p></div>';
	}

	/**
	 * Writes a button that posts an action.
	 *
	 * @param string $action  The action.
	 * @param string $label   The button's text.
	 * @param string $classes The button's classes.
	 */
	private static function button( $action, $label, $classes ) {
		printf( '<form method="post" action="%s" style="display:inline">', esc_url( admin_url( 'admin-post.php' ) ) );
		printf( '<input type="hidden" name="action" value="%s">', esc_attr( $action ) );
		wp_nonce_field( $action );
		printf( '<button type="submit" class="%s">%s</button></form>', esc_attr( $classes ), esc_html( $label ) );
	}

	/**
	 * Renames the existing llms.txt to llms.txt.bak. Discovery 5.
	 */
	public static function replace() {
		check_admin_referer( 'agent_ready_replace' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You cannot do this.', 'agent-ready' ) );
		}

		$filesystem = Agent_Ready_Route::filesystem();
		$path       = self::path();
		if ( $filesystem ) {
			$filesystem->move( $path, $path . '.bak' );
		}
		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url() );
		exit;
	}

	/**
	 * Dismisses the notice. Discovery 5.
	 */
	public static function dismiss() {
		check_admin_referer( 'agent_ready_dismiss' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You cannot do this.', 'agent-ready' ) );
		}

		update_option( self::DISMISSED, 1, false );
		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url() );
		exit;
	}

	/**
	 * Restores llms.txt.bak on deactivation. Discovery 5.
	 */
	public static function restore() {
		$filesystem = Agent_Ready_Route::filesystem();
		$path       = self::path();
		if ( $filesystem && $filesystem->exists( $path . '.bak' ) && ! $filesystem->exists( $path ) ) {
			$filesystem->move( $path . '.bak', $path );
		}
	}
}
