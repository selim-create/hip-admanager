<?php
/**
 * Plugin Name: HIP Ad Manager
 * Plugin URI: https://github.com/selim-create/hip-admanager
 * Description: Headless-first Google Ad Manager inventory control plane for WordPress.
 * Version: 2.0.0
 * Author: HIP
 * Author URI: https://github.com/selim-create
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: hip-admanager
 * Domain Path: /languages
 * Requires at least: 6.2
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'HIP_AD_MANAGER_VERSION', '2.0.0' );
define( 'HIP_AD_MANAGER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'HIP_AD_MANAGER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'HIP_AD_MANAGER_PLUGIN_FILE', __FILE__ );

require_once HIP_AD_MANAGER_PLUGIN_DIR . 'includes/class-hip-ad-manager.php';

function hip_ad_manager_init() {
	HIP_Ad_Manager::get_instance();
}
add_action( 'plugins_loaded', 'hip_ad_manager_init' );

function hip_ad_manager_activate() {
	require_once HIP_AD_MANAGER_PLUGIN_DIR . 'includes/class-hip-ad-schema.php';
	require_once HIP_AD_MANAGER_PLUGIN_DIR . 'includes/class-hip-ad-slot.php';
	require_once HIP_AD_MANAGER_PLUGIN_DIR . 'includes/class-hip-ad-repository.php';
	require_once HIP_AD_MANAGER_PLUGIN_DIR . 'includes/class-hip-ad-settings.php';
	require_once HIP_AD_MANAGER_PLUGIN_DIR . 'includes/class-hip-ad-migrator.php';

	if ( false === get_option( HIP_Ad_Settings::OPTION_NAME, false ) ) {
		$defaults = HIP_Ad_Schema::settings_defaults();
		$defaults['ads_enabled'] = 0;
		update_option( HIP_Ad_Settings::OPTION_NAME, $defaults, false );
	}

	HIP_Ad_Migrator::activate();
}
register_activation_hook( __FILE__, 'hip_ad_manager_activate' );

function hip_ad_manager_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'hip_ad_manager_deactivate' );
