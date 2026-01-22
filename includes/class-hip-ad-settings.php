<?php
/**
 * Settings management
 *
 * @package HIP_Ad_Manager
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * HIP Ad Settings class
 */
class HIP_Ad_Settings {

	/**
	 * Option name
	 */
	const OPTION_NAME = 'hip_ad_manager_settings';

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Register settings
	 */
	public function register_settings() {
		register_setting(
			'hip_ad_manager_settings_group',
			self::OPTION_NAME,
			array(
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
			)
		);
	}

	/**
	 * Sanitize settings
	 *
	 * @param array $input
	 * @return array
	 */
	public function sanitize_settings( $input ) {
		$sanitized = array();

		if ( isset( $input['network_code'] ) ) {
			$sanitized['network_code'] = sanitize_text_field( $input['network_code'] );
		}

		if ( isset( $input['site_name'] ) ) {
			$sanitized['site_name'] = sanitize_text_field( $input['site_name'] );
		}

		$sanitized['enable_lazy_load'] = isset( $input['enable_lazy_load'] ) ? 1 : 0;
		$sanitized['enable_single_request'] = isset( $input['enable_single_request'] ) ? 1 : 0;
		$sanitized['debug_mode'] = isset( $input['debug_mode'] ) ? 1 : 0;

		if ( isset( $input['global_targeting'] ) ) {
			$targeting = json_decode( stripslashes( $input['global_targeting'] ), true );
			if ( json_last_error() === JSON_ERROR_NONE ) {
				$sanitized['global_targeting'] = wp_json_encode( $targeting );
			}
		}

		if ( isset( $input['default_size_mappings'] ) ) {
			$size_mappings = json_decode( stripslashes( $input['default_size_mappings'] ), true );
			if ( json_last_error() === JSON_ERROR_NONE ) {
				$sanitized['default_size_mappings'] = wp_json_encode( $size_mappings );
			}
		}
		
		// Save cache duration
		if ( isset( $input['cache_duration'] ) ) {
			$sanitized['cache_duration'] = absint( $input['cache_duration'] );
		}
		
		// Save ads.txt separately (not in main settings array)
		if ( isset( $input['ads_txt_content'] ) ) {
			update_option( 'hip_ad_ads_txt_content', sanitize_textarea_field( $input['ads_txt_content'] ) );
		}

		return $sanitized;
	}

	/**
	 * Get setting value
	 *
	 * @param string $key
	 * @param mixed  $default
	 * @return mixed
	 */
	public static function get( $key, $default = '' ) {
		$settings = get_option( self::OPTION_NAME, array() );
		return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
	}

	/**
	 * Get all settings
	 *
	 * @return array
	 */
	public static function get_all() {
		return get_option( self::OPTION_NAME, array() );
	}

	/**
	 * Get default size mappings
	 *
	 * @return array
	 */
	public static function get_default_size_mappings() {
		return array(
			'leaderboard'    => array(
				array(
					'viewport' => array( 1024, 0 ),
					'sizes'    => array( array( 970, 250 ), array( 970, 90 ), array( 728, 90 ) ),
				),
				array(
					'viewport' => array( 768, 0 ),
					'sizes'    => array( array( 728, 90 ) ),
				),
				array(
					'viewport' => array( 0, 0 ),
					'sizes'    => array( array( 320, 100 ), array( 320, 50 ) ),
				),
			),
			'mpu'            => array(
				array(
					'viewport' => array( 768, 0 ),
					'sizes'    => array( array( 300, 600 ), array( 300, 250 ), array( 336, 280 ) ),
				),
				array(
					'viewport' => array( 0, 0 ),
					'sizes'    => array( array( 300, 250 ) ),
				),
			),
			'skyscraper'     => array(
				array(
					'viewport' => array( 1024, 0 ),
					'sizes'    => array( array( 160, 600 ), array( 120, 600 ) ),
				),
				array(
					'viewport' => array( 0, 0 ),
					'sizes'    => array(),
				),
			),
			'mobile_sticky'  => array(
				array(
					'viewport' => array( 0, 0 ),
					'sizes'    => array( array( 320, 50 ), array( 320, 100 ) ),
				),
			),
		);
	}
}
