<?php
/**
 * Plugin Name: MTSV Agent Site – Your Whole Site as a Spreadsheet for AI Agents
 * Plugin URI:  https://github.com/demos-ra/mtsv-agent-site
 * Description: Lets AI agents read your whole site: every public post, page, category and author as linked spreadsheets, listed in llms.txt. No setup.
 * Version:     1.0.0
 * Requires at least: 5.7
 * Requires PHP: 7.4
 * Author:      Demos Ra
 * Author URI:  https://github.com/demos-ra
 * License:     GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: mtsv-agent-site
 *
 * @package MTSV_Agent_Site
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/includes/class-mtsv-agent-site-providers.php';
require_once __DIR__ . '/includes/class-mtsv-agent-site-visibility.php';
require_once __DIR__ . '/includes/class-mtsv-agent-site-fields.php';
require_once __DIR__ . '/includes/class-mtsv-agent-site-relations.php';
require_once __DIR__ . '/includes/class-mtsv-agent-site-writer.php';
require_once __DIR__ . '/includes/class-mtsv-agent-site-response.php';
require_once __DIR__ . '/includes/class-mtsv-agent-site-route.php';
require_once __DIR__ . '/includes/class-mtsv-agent-site-discovery.php';

MTSV_Agent_Site_Route::init();
MTSV_Agent_Site_Discovery::init();

register_activation_hook( __FILE__, array( 'MTSV_Agent_Site_Route', 'activate' ) );
register_activation_hook( __FILE__, array( 'MTSV_Agent_Site_Discovery', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'MTSV_Agent_Site_Route', 'deactivate' ) );
