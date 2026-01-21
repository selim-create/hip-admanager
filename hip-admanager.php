<?php
/**
 * Plugin Name: HIP Ad Manager
 * Plugin URI: https://github.com/selim-create/hip-admanager
 * Description: Google Ad Manager integration for headless WordPress projects
 * Version: 1.0.0
 * Author: HIP
 * Author URI: https://github.com/selim-create
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: hip-admanager
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants
define( 'HIP_AD_MANAGER_VERSION', '1.0.0' );
define( 'HIP_AD_MANAGER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'HIP_AD_MANAGER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'HIP_AD_MANAGER_PLUGIN_FILE', __FILE__ );

// Autoloader
require_once HIP_AD_MANAGER_PLUGIN_DIR . 'includes/class-hip-ad-manager.php';

/**
 * Initialize the plugin
 */
function hip_ad_manager_init() {
	HIP_Ad_Manager::get_instance();
}
add_action( 'plugins_loaded', 'hip_ad_manager_init' );

/**
 * Activation hook
 */
function hip_ad_manager_activate() {
	// Trigger CPT registration
	require_once HIP_AD_MANAGER_PLUGIN_DIR . 'includes/class-hip-ad-slot.php';
	HIP_Ad_Slot::register_post_type();
	
	// Flush rewrite rules
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'hip_ad_manager_activate' );

/**
 * Deactivation hook
 */
function hip_ad_manager_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'hip_ad_manager_deactivate' );
