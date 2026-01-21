<?php
/**
 * Main plugin class
 *
 * @package HIP_Ad_Manager
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main HIP Ad Manager class
 */
class HIP_Ad_Manager {

	/**
	 * Single instance
	 *
	 * @var HIP_Ad_Manager
	 */
	private static $instance = null;

	/**
	 * Ad Slot instance
	 *
	 * @var HIP_Ad_Slot
	 */
	public $ad_slot;

	/**
	 * REST API instance
	 *
	 * @var HIP_Ad_REST_API
	 */
	public $rest_api;

	/**
	 * Settings instance
	 *
	 * @var HIP_Ad_Settings
	 */
	public $settings;

	/**
	 * Importer instance
	 *
	 * @var HIP_Ad_Importer
	 */
	public $importer;

	/**
	 * Targeting instance
	 *
	 * @var HIP_Ad_Targeting
	 */
	public $targeting;

	/**
	 * Admin instance
	 *
	 * @var HIP_Ad_Admin
	 */
	public $admin;

	/**
	 * Blocks instance
	 *
	 * @var HIP_Ad_Blocks
	 */
	public $blocks;

	/**
	 * Get singleton instance
	 *
	 * @return HIP_Ad_Manager
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->load_dependencies();
		$this->init_hooks();
	}

	/**
	 * Load dependencies
	 */
	private function load_dependencies() {
		require_once HIP_AD_MANAGER_PLUGIN_DIR . 'includes/class-hip-ad-slot.php';
		require_once HIP_AD_MANAGER_PLUGIN_DIR . 'includes/class-hip-ad-rest-api.php';
		require_once HIP_AD_MANAGER_PLUGIN_DIR . 'includes/class-hip-ad-settings.php';
		require_once HIP_AD_MANAGER_PLUGIN_DIR . 'includes/class-hip-ad-importer.php';
		require_once HIP_AD_MANAGER_PLUGIN_DIR . 'includes/class-hip-ad-targeting.php';
		require_once HIP_AD_MANAGER_PLUGIN_DIR . 'includes/class-hip-ad-blocks.php';

		if ( is_admin() ) {
			require_once HIP_AD_MANAGER_PLUGIN_DIR . 'admin/class-hip-ad-admin.php';
		}
	}

	/**
	 * Initialize hooks
	 */
	private function init_hooks() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'rest_api_init', array( $this, 'init_rest_api' ) );
		
		// Clear cache when slot is saved
		add_action( 'save_post_' . HIP_Ad_Slot::POST_TYPE, array( $this, 'clear_slots_cache' ), 10, 2 );
	}

	/**
	 * Initialize plugin components
	 */
	public function init() {
		// Initialize settings first
		$this->settings = new HIP_Ad_Settings();
		
		// Initialize ad slot CPT
		$this->ad_slot = new HIP_Ad_Slot();
		
		// Initialize targeting
		$this->targeting = new HIP_Ad_Targeting();
		
		// Initialize importer
		$this->importer = new HIP_Ad_Importer();
		
		// Initialize blocks
		$this->blocks = new HIP_Ad_Blocks();
		
		// Initialize admin panel
		if ( is_admin() ) {
			$this->admin = new HIP_Ad_Admin();
		}
		
		// Load text domain
		load_plugin_textdomain( 'hip-admanager', false, dirname( plugin_basename( HIP_AD_MANAGER_PLUGIN_FILE ) ) . '/languages' );
	}

	/**
	 * Initialize REST API
	 */
	public function init_rest_api() {
		$this->rest_api = new HIP_Ad_REST_API();
	}
	
	/**
	 * Clear slots cache when a slot is saved
	 *
	 * @param int     $post_id
	 * @param WP_Post $post
	 */
	public function clear_slots_cache( $post_id, $post ) {
		// Call the REST API's cache clearing method
		if ( $this->rest_api ) {
			$this->rest_api->clear_slots_cache();
		}
	}
}
