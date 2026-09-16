<?php
/**
 * Main plugin bootstrap.
 *
 * @package HIP_Ad_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HIP_Ad_Manager {

	private static $instance = null;
	public $ad_slot;
	public $rest_api;
	public $settings;
	public $importer;
	public $targeting;
	public $admin;
	public $blocks;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->load_dependencies();
		$this->settings = new HIP_Ad_Settings();
		if ( is_admin() ) {
			$this->admin = new HIP_Ad_Admin();
		}
		add_action( 'init', array( $this, 'init' ), 5 );
		add_action( 'rest_api_init', array( $this, 'init_rest_api' ) );
	}

	private function load_dependencies() {
		require_once HIP_AD_MANAGER_PLUGIN_DIR . 'includes/class-hip-ad-schema.php';
		require_once HIP_AD_MANAGER_PLUGIN_DIR . 'includes/class-hip-ad-slot.php';
		require_once HIP_AD_MANAGER_PLUGIN_DIR . 'includes/class-hip-ad-repository.php';
		require_once HIP_AD_MANAGER_PLUGIN_DIR . 'includes/class-hip-ad-settings.php';
		require_once HIP_AD_MANAGER_PLUGIN_DIR . 'includes/class-hip-ad-migrator.php';
		require_once HIP_AD_MANAGER_PLUGIN_DIR . 'includes/class-hip-ad-importer.php';
		require_once HIP_AD_MANAGER_PLUGIN_DIR . 'includes/class-hip-ad-targeting.php';
		require_once HIP_AD_MANAGER_PLUGIN_DIR . 'includes/class-hip-ad-blocks.php';
		require_once HIP_AD_MANAGER_PLUGIN_DIR . 'includes/class-hip-ad-rest-api.php';
		if ( is_admin() ) {
			require_once HIP_AD_MANAGER_PLUGIN_DIR . 'admin/class-hip-ad-admin.php';
		}
	}

	public function init() {
		$this->ad_slot = new HIP_Ad_Slot();
		$this->targeting = new HIP_Ad_Targeting();
		$this->importer = new HIP_Ad_Importer();
		$this->blocks = new HIP_Ad_Blocks();
		HIP_Ad_Migrator::maybe_migrate();
		load_plugin_textdomain( 'hip-admanager', false, dirname( plugin_basename( HIP_AD_MANAGER_PLUGIN_FILE ) ) . '/languages' );
	}

	public function init_rest_api() {
		$this->rest_api = new HIP_Ad_REST_API();
	}
}
