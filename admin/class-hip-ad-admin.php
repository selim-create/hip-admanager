<?php
/**
 * Admin panel
 *
 * @package HIP_Ad_Manager
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * HIP Ad Admin class
 */
class HIP_Ad_Admin {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'admin_post_hip_ad_confirm_import', array( $this, 'confirm_import' ) );
	}

	/**
	 * Add admin menu
	 */
	public function add_admin_menu() {
		// Main menu
		add_menu_page(
			__( 'HIP Ad Manager', 'hip-admanager' ),
			__( 'HIP Ad Manager', 'hip-admanager' ),
			'manage_options',
			'hip-ad-manager',
			array( $this, 'render_dashboard_page' ),
			'dashicons-megaphone',
			30
		);

		// Dashboard submenu
		add_submenu_page(
			'hip-ad-manager',
			__( 'Dashboard', 'hip-admanager' ),
			__( 'Dashboard', 'hip-admanager' ),
			'manage_options',
			'hip-ad-manager',
			array( $this, 'render_dashboard_page' )
		);

		// Ad Slots submenu (link to CPT)
		add_submenu_page(
			'hip-ad-manager',
			__( 'Ad Slots', 'hip-admanager' ),
			__( 'Ad Slots', 'hip-admanager' ),
			'manage_options',
			'edit.php?post_type=' . HIP_Ad_Slot::POST_TYPE
		);

		// Import submenu
		add_submenu_page(
			'hip-ad-manager',
			__( 'Import', 'hip-admanager' ),
			__( 'Import', 'hip-admanager' ),
			'manage_options',
			'hip-ad-import',
			array( $this, 'render_import_page' )
		);

		// Settings submenu
		add_submenu_page(
			'hip-ad-manager',
			__( 'Settings', 'hip-admanager' ),
			__( 'Settings', 'hip-admanager' ),
			'manage_options',
			'hip-ad-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Enqueue admin assets
	 */
	public function enqueue_admin_assets( $hook ) {
		// Only load on our plugin pages
		if ( strpos( $hook, 'hip-ad' ) === false && strpos( $hook, HIP_Ad_Slot::POST_TYPE ) === false ) {
			return;
		}

		wp_enqueue_style(
			'hip-ad-admin',
			HIP_AD_MANAGER_PLUGIN_URL . 'admin/assets/css/admin.css',
			array(),
			HIP_AD_MANAGER_VERSION
		);

		wp_enqueue_script(
			'hip-ad-admin',
			HIP_AD_MANAGER_PLUGIN_URL . 'admin/assets/js/admin.js',
			array( 'jquery' ),
			HIP_AD_MANAGER_VERSION,
			true
		);
	}

	/**
	 * Render dashboard page
	 */
	public function render_dashboard_page() {
		// Get stats
		$total_slots = wp_count_posts( HIP_Ad_Slot::POST_TYPE );
		$settings = get_option( HIP_Ad_Settings::OPTION_NAME, array() );

		include HIP_AD_MANAGER_PLUGIN_DIR . 'admin/views/dashboard.php';
	}

	/**
	 * Render import page
	 */
	public function render_import_page() {
		$step = isset( $_GET['step'] ) ? sanitize_text_field( $_GET['step'] ) : 'upload';
		$preview_data = get_transient( 'hip_ad_import_preview' );

		include HIP_AD_MANAGER_PLUGIN_DIR . 'admin/views/import-page.php';
	}

	/**
	 * Render settings page
	 */
	public function render_settings_page() {
		// Handle form submission
		if ( isset( $_POST['hip_ad_settings_submit'] ) ) {
			check_admin_referer( 'hip_ad_settings_save', 'hip_ad_settings_nonce' );
			
			$settings = array();
			$settings['ads_enabled'] = isset( $_POST['ads_enabled'] ) ? 1 : 0;
			$settings['network_code'] = isset( $_POST['network_code'] ) ? sanitize_text_field( $_POST['network_code'] ) : '';
			$settings['site_name'] = isset( $_POST['site_name'] ) ? sanitize_text_field( $_POST['site_name'] ) : '';
			$settings['enable_lazy_load'] = isset( $_POST['enable_lazy_load'] ) ? 1 : 0;
			$settings['enable_single_request'] = isset( $_POST['enable_single_request'] ) ? 1 : 0;
			$settings['enable_services'] = isset( $_POST['enable_services'] ) ? 1 : 0;
			$settings['debug_mode'] = isset( $_POST['debug_mode'] ) ? 1 : 0;
			
			if ( isset( $_POST['global_targeting'] ) ) {
				$targeting = json_decode( stripslashes( $_POST['global_targeting'] ), true );
				if ( json_last_error() === JSON_ERROR_NONE ) {
					$settings['global_targeting'] = wp_json_encode( $targeting );
				}
			}
			
			if ( isset( $_POST['cache_duration'] ) ) {
				$settings['cache_duration'] = absint( $_POST['cache_duration'] );
			}
			
			if ( isset( $_POST['ads_txt_content'] ) ) {
				update_option( 'hip_ad_ads_txt_content', sanitize_textarea_field( $_POST['ads_txt_content'] ) );
			}
			
			update_option( HIP_Ad_Settings::OPTION_NAME, $settings );
			
			echo '<div class="notice notice-success"><p>' . __( 'Settings saved successfully.', 'hip-admanager' ) . '</p></div>';
		}

		$settings = get_option( HIP_Ad_Settings::OPTION_NAME, array() );
		
		include HIP_AD_MANAGER_PLUGIN_DIR . 'admin/views/settings-page.php';
	}

	/**
	 * Handle import confirmation
	 */
	public function confirm_import() {
		// Check nonce
		if ( ! isset( $_POST['hip_ad_confirm_nonce'] ) || ! wp_verify_nonce( $_POST['hip_ad_confirm_nonce'], 'hip_ad_confirm_import' ) ) {
			wp_die( __( 'Security check failed', 'hip-admanager' ) );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have permission to perform this action', 'hip-admanager' ) );
		}

		// Get preview data
		$preview_data = get_transient( 'hip_ad_import_preview' );
		
		if ( ! $preview_data ) {
			wp_redirect( admin_url( 'admin.php?page=hip-ad-import&error=no_preview' ) );
			exit;
		}

		// Create slots
		$importer = new HIP_Ad_Importer();
		$results = $importer->create_slots( $preview_data );

		// Delete transient
		delete_transient( 'hip_ad_import_preview' );

		// Store results in transient
		set_transient( 'hip_ad_import_results', $results, HOUR_IN_SECONDS );

		wp_redirect( admin_url( 'admin.php?page=hip-ad-import&step=results' ) );
		exit;
	}
}
