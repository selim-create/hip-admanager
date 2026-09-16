<?php
/**
 * Settings service.
 *
 * @package HIP_Ad_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HIP_Ad_Settings {

	const OPTION_NAME = 'hip_ad_manager_settings';
	const ADS_TXT_OPTION = 'hip_ad_ads_txt_content';

	public function __construct() {
		// v2 intentionally owns all writes through this service and the custom admin controller.
	}

	public static function update( $input ) {
		$current = self::get_all();
		$settings = HIP_Ad_Schema::normalize_settings( $input, $current );
		$errors = HIP_Ad_Schema::validate_settings( $settings );
		if ( $errors->has_errors() ) {
			return $errors;
		}
		update_option( self::OPTION_NAME, $settings, false );
		HIP_Ad_Repository::bump_cache_version();
		do_action( 'hip_ad_settings_saved', $settings );
		return $settings;
	}

	public static function get( $key, $default = null ) {
		$settings = self::get_all();
		if ( array_key_exists( $key, $settings ) ) {
			return $settings[ $key ];
		}
		if ( null !== $default ) {
			return $default;
		}
		$defaults = HIP_Ad_Schema::settings_defaults();
		return array_key_exists( $key, $defaults ) ? $defaults[ $key ] : '';
	}

	public static function get_all() {
		$stored = get_option( self::OPTION_NAME, array() );
		$stored = is_array( $stored ) ? $stored : array();
		if ( empty( $stored['property_code'] ) && ! empty( $stored['site_name'] ) ) {
			$stored['property_code'] = $stored['site_name'];
		}
		return HIP_Ad_Schema::normalize_settings( $stored );
	}

	public static function get_default_size_mappings() {
		return HIP_Ad_Schema::size_presets();
	}

	public static function get_ads_txt() {
		return (string) get_option( self::ADS_TXT_OPTION, '' );
	}

	public static function update_ads_txt( $content ) {
		$content = self::sanitize_ads_txt( $content );
		update_option( self::ADS_TXT_OPTION, $content, false );
		HIP_Ad_Repository::bump_cache_version();
		return $content;
	}

	public static function sanitize_ads_txt( $content ) {
		$content = str_replace( array( "\r\n", "\r" ), "\n", wp_unslash( (string) $content ) );
		$lines = explode( "\n", $content );
		$clean = array();
		foreach ( $lines as $line ) {
			$line = trim( wp_strip_all_tags( $line ) );
			if ( '' === $line ) {
				$clean[] = '';
				continue;
			}
			if ( '#' === substr( $line, 0, 1 ) ) {
				$clean[] = '# ' . trim( substr( $line, 1 ) );
				continue;
			}
			$parts = array_map( 'trim', explode( ',', $line ) );
			$parts = array_map( 'sanitize_text_field', $parts );
			$clean[] = implode( ', ', $parts );
		}
		return trim( preg_replace( "/\n{3,}/", "\n\n", implode( "\n", $clean ) ) );
	}

	public static function client_config() {
		$settings = self::get_all();
		return array(
			'schemaVersion' => HIP_Ad_Schema::VERSION,
			'adsEnabled'    => (bool) $settings['ads_enabled'],
			'networkCode'   => $settings['network_code'],
			'propertyCode'  => $settings['property_code'],
			'siteName'      => $settings['property_code'],
			'gpt'           => array(
				'singleRequest' => (bool) $settings['enable_single_request'],
				'collapseEmpty' => (bool) $settings['collapse_empty'],
				'lazyLoad'      => $settings['enable_lazy_load'] ? array(
					'fetchMarginPercent'  => (int) $settings['lazy_fetch_margin'],
					'renderMarginPercent' => (int) $settings['lazy_render_margin'],
					'mobileScaling'       => (float) $settings['lazy_mobile_scaling'],
				) : null,
			),
			'globalTargeting' => $settings['global_targeting'],
			'cacheTtl'        => (int) $settings['cache_duration'],
			'debug'           => (bool) $settings['debug_mode'],
		);
	}
}
