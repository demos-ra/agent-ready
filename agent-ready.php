<?php
/**
 * Plugin Name: Agent Ready – llms.txt, Agent Sitemap & Plain-Text Pages for AI Agents
 * Plugin URI:  https://github.com/demos-ra/agent-ready
 * Description: Make your site agent-ready: an llms.txt, a plain-text site index, and a clean text copy of every post. Plug and play.
 * Version:     1.0.0
 * Requires at least: 5.5
 * Requires PHP: 7.4
 * Author:      Demos Ra
 * Author URI:  https://github.com/demos-ra
 * License:     GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: agent-ready
 *
 * @package Agent_Ready
 */

defined( 'ABSPATH' ) || exit;

define( 'AGENT_READY_SITEMAP_SLUG', 'agent-sitemap' );

require_once __DIR__ . '/includes/class-agent-ready-writer.php';
require_once __DIR__ . '/includes/class-agent-ready-visibility.php';
require_once __DIR__ . '/includes/class-agent-ready-providers.php';
require_once __DIR__ . '/includes/class-agent-ready-fields.php';
require_once __DIR__ . '/includes/class-agent-ready-relations.php';
require_once __DIR__ . '/includes/class-agent-ready-response.php';
require_once __DIR__ . '/includes/class-agent-ready-route.php';
require_once __DIR__ . '/includes/class-agent-ready-discovery.php';

Agent_Ready_Route::init();
Agent_Ready_Discovery::init();

register_activation_hook( __FILE__, array( 'Agent_Ready_Route', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Agent_Ready_Route', 'deactivate' ) );
register_deactivation_hook( __FILE__, array( 'Agent_Ready_Discovery', 'restore' ) );
