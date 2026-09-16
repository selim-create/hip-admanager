<?php
/**
 * Canonical data schema and validation helpers.
 *
 * @package HIP_Ad_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HIP_Ad_Schema {

	const VERSION = 2;

	public static function placement_groups() {
		return array(
			'header'  => __( 'Header', 'hip-admanager' ),
			'content' => __( 'Content', 'hip-admanager' ),
			'sidebar' => __( 'Sidebar', 'hip-admanager' ),
			'footer'  => __( 'Footer', 'hip-admanager' ),
			'overlay' => __( 'Overlay / Sticky', 'hip-admanager' ),
			'other'   => __( 'Other', 'hip-admanager' ),
		);
	}

	public static function devices() {
		return array(
			'all'     => __( 'All devices', 'hip-admanager' ),
			'desktop' => __( 'Desktop', 'hip-admanager' ),
			'tablet'  => __( 'Tablet', 'hip-admanager' ),
			'mobile'  => __( 'Mobile', 'hip-admanager' ),
		);
	}

	public static function page_types() {
		return array(
			'all'      => __( 'All pages', 'hip-admanager' ),
			'home'     => __( 'Homepage', 'hip-admanager' ),
			'article'  => __( 'Article', 'hip-admanager' ),
			'category' => __( 'Category / archive', 'hip-admanager' ),
			'search'   => __( 'Search', 'hip-admanager' ),
			'page'     => __( 'Static page', 'hip-admanager' ),
		);
	}

	public static function statuses() {
		return array(
			'active'    => __( 'Active', 'hip-admanager' ),
			'paused'    => __( 'Paused', 'hip-admanager' ),
			'scheduled' => __( 'Scheduled', 'hip-admanager' ),
		);
	}

	public static function refresh_triggers() {
		return array(
			'time'        => __( 'Time based', 'hip-admanager' ),
			'event'       => __( 'Event based', 'hip-admanager' ),
			'user_action' => __( 'User action', 'hip-admanager' ),
		);
	}

	public static function slot_defaults() {
		return array(
			'id'                  => 0,
			'name'                => '',
			'key'                 => '',
			'inventory_id'        => '',
			'ad_unit_path'        => '',
			'placement_group'     => 'content',
			'placement_key'       => '',
			'device'              => 'all',
			'status'              => 'active',
			'priority'            => 10,
			'sizes'               => array(),
			'size_mappings'       => array(),
			'targeting'           => array(),
			'page_types'          => array( 'all' ),
			'categories'          => array(),
			'lazy_load'           => true,
			'collapse_empty'      => true,
			'min_height'          => array(
				'desktop' => 0,
				'tablet'  => 0,
				'mobile'  => 0,
			),
			'schedule'            => array(
				'start' => '',
				'end'   => '',
			),
			'refresh'             => array(
				'enabled'          => false,
				'trigger'          => 'time',
				'interval'         => 30,
				'max_refreshes'    => 0,
				'require_visible'  => true,
				'pause_when_hidden'=> true,
			),
			'notes'               => '',
			'legacy_slot_id'      => '',
		);
	}

	public static function settings_defaults() {
		return array(
			'ads_enabled'              => 1,
			'network_code'             => '',
			'property_code'            => sanitize_title( wp_parse_url( home_url(), PHP_URL_HOST ) ),
			'enable_single_request'    => 1,
			'enable_lazy_load'         => 1,
			'collapse_empty'           => 1,
			'lazy_fetch_margin'        => 500,
			'lazy_render_margin'       => 200,
			'lazy_mobile_scaling'      => 2.0,
			'global_targeting'         => array(),
			'cache_duration'           => 300,
			'debug_mode'               => 0,
		);
	}

	public static function size_presets() {
		return array(
			'leaderboard' => array(
				array( 'viewport' => array( 1024, 0 ), 'sizes' => array( array( 970, 250 ), array( 970, 90 ), array( 728, 90 ) ) ),
				array( 'viewport' => array( 768, 0 ), 'sizes' => array( array( 728, 90 ) ) ),
				array( 'viewport' => array( 0, 0 ), 'sizes' => array( array( 320, 100 ), array( 320, 50 ) ) ),
			),
			'mpu' => array(
				array( 'viewport' => array( 768, 0 ), 'sizes' => array( array( 336, 280 ), array( 300, 250 ), array( 300, 600 ) ) ),
				array( 'viewport' => array( 0, 0 ), 'sizes' => array( array( 300, 250 ) ) ),
			),
			'skyscraper' => array(
				array( 'viewport' => array( 1024, 0 ), 'sizes' => array( array( 160, 600 ), array( 120, 600 ) ) ),
				array( 'viewport' => array( 0, 0 ), 'sizes' => array() ),
			),
			'mobile_sticky' => array(
				array( 'viewport' => array( 0, 0 ), 'sizes' => array( array( 320, 100 ), array( 320, 50 ) ) ),
			),
		);
	}

	public static function normalize_slot( $input, $existing = array() ) {
		$slot = wp_parse_args( is_array( $input ) ? $input : array(), wp_parse_args( $existing, self::slot_defaults() ) );

		$slot['id'] = absint( $slot['id'] );
		$slot['name'] = sanitize_text_field( $slot['name'] );
		$slot['key'] = self::sanitize_key( $slot['key'] ? $slot['key'] : $slot['name'] );
		$slot['inventory_id'] = sanitize_text_field( $slot['inventory_id'] );
		$slot['legacy_slot_id'] = sanitize_text_field( $slot['legacy_slot_id'] );
		$slot['ad_unit_path'] = self::sanitize_ad_unit_path( $slot['ad_unit_path'] );
		$slot['placement_group'] = self::enum( $slot['placement_group'], array_keys( self::placement_groups() ), 'content' );
		$slot['placement_key'] = self::sanitize_key( $slot['placement_key'] ? $slot['placement_key'] : $slot['key'] );
		$slot['device'] = self::enum( $slot['device'], array_keys( self::devices() ), 'all' );
		$slot['status'] = self::enum( $slot['status'], array_keys( self::statuses() ), 'active' );
		$slot['priority'] = max( 1, min( 100, absint( $slot['priority'] ) ) );
		$slot['sizes'] = self::normalize_sizes( $slot['sizes'] );
		$slot['size_mappings'] = self::normalize_mappings( $slot['size_mappings'] );
		$slot['targeting'] = self::normalize_targeting( $slot['targeting'] );
		$slot['page_types'] = self::normalize_page_types( $slot['page_types'] );
		$slot['categories'] = self::normalize_string_list( $slot['categories'] );
		$slot['lazy_load'] = self::truthy( $slot['lazy_load'] );
		$slot['collapse_empty'] = self::truthy( $slot['collapse_empty'] );
		$slot['min_height'] = self::normalize_min_height( $slot['min_height'] );
		$slot['schedule'] = self::normalize_schedule( $slot['schedule'] );
		$slot['refresh'] = self::normalize_refresh( $slot['refresh'] );
		$slot['notes'] = sanitize_textarea_field( $slot['notes'] );

		return $slot;
	}

	public static function validate_slot( $slot ) {
		$errors = new WP_Error();
		$slot = self::normalize_slot( $slot );

		if ( '' === $slot['name'] ) {
			$errors->add( 'name_required', __( 'Slot name is required.', 'hip-admanager' ) );
		}
		if ( '' === $slot['key'] ) {
			$errors->add( 'key_required', __( 'A stable slot key is required.', 'hip-admanager' ) );
		}
		if ( '' === $slot['ad_unit_path'] || '/' === $slot['ad_unit_path'] ) {
			$errors->add( 'path_required', __( 'Google Ad Manager ad unit path is required.', 'hip-admanager' ) );
		}
		if ( empty( $slot['sizes'] ) ) {
			$errors->add( 'sizes_required', __( 'Add at least one valid ad size.', 'hip-admanager' ) );
		}
		if ( $slot['refresh']['enabled'] && 'user_action' !== $slot['refresh']['trigger'] && $slot['refresh']['interval'] < 30 ) {
			$errors->add( 'refresh_interval', __( 'Time/event based refresh cannot be lower than 30 seconds.', 'hip-admanager' ) );
		}
		if ( $slot['schedule']['start'] && $slot['schedule']['end'] && strtotime( $slot['schedule']['start'] ) > strtotime( $slot['schedule']['end'] ) ) {
			$errors->add( 'schedule_order', __( 'Schedule end must be after schedule start.', 'hip-admanager' ) );
		}

		return $errors;
	}

	public static function normalize_settings( $input, $existing = array() ) {
		$settings = wp_parse_args( is_array( $input ) ? $input : array(), wp_parse_args( $existing, self::settings_defaults() ) );
		$settings['ads_enabled'] = self::truthy( $settings['ads_enabled'] ) ? 1 : 0;
		$settings['network_code'] = preg_replace( '/\D+/', '', (string) $settings['network_code'] );
		$settings['property_code'] = self::sanitize_key( $settings['property_code'] );
		$settings['enable_single_request'] = self::truthy( $settings['enable_single_request'] ) ? 1 : 0;
		$settings['enable_lazy_load'] = self::truthy( $settings['enable_lazy_load'] ) ? 1 : 0;
		$settings['collapse_empty'] = self::truthy( $settings['collapse_empty'] ) ? 1 : 0;
		$settings['lazy_fetch_margin'] = max( 0, min( 2000, absint( $settings['lazy_fetch_margin'] ) ) );
		$settings['lazy_render_margin'] = max( 0, min( 2000, absint( $settings['lazy_render_margin'] ) ) );
		$settings['lazy_mobile_scaling'] = max( 0.1, min( 5, (float) $settings['lazy_mobile_scaling'] ) );
		$settings['global_targeting'] = self::normalize_targeting( $settings['global_targeting'] );
		$settings['cache_duration'] = max( 0, min( DAY_IN_SECONDS, absint( $settings['cache_duration'] ) ) );
		$settings['debug_mode'] = self::truthy( $settings['debug_mode'] ) ? 1 : 0;
		return $settings;
	}

	public static function validate_settings( $settings ) {
		$errors = new WP_Error();
		$settings = self::normalize_settings( $settings );
		if ( $settings['ads_enabled'] && '' === $settings['network_code'] ) {
			$errors->add( 'network_required', __( 'Network code is required while ads are enabled.', 'hip-admanager' ) );
		}
		if ( '' === $settings['property_code'] ) {
			$errors->add( 'property_required', __( 'Property code is required.', 'hip-admanager' ) );
		}
		return $errors;
	}

	public static function normalize_sizes( $value ) {
		$value = self::decode_jsonish( $value, array() );
		if ( ! is_array( $value ) ) {
			return array();
		}
		$sizes = array();
		foreach ( $value as $size ) {
			if ( is_string( $size ) && preg_match( '/^(\d{1,4})\s*[xX]\s*(\d{1,4})$/', trim( $size ), $matches ) ) {
				$size = array( $matches[1], $matches[2] );
			}
			if ( ! is_array( $size ) || count( $size ) < 2 ) {
				continue;
			}
			$width = absint( $size[0] );
			$height = absint( $size[1] );
			if ( $width < 1 || $height < 1 || $width > 5000 || $height > 5000 ) {
				continue;
			}
			$sizes[ $width . 'x' . $height ] = array( $width, $height );
		}
		return array_values( $sizes );
	}

	public static function normalize_mappings( $value ) {
		$value = self::decode_jsonish( $value, array() );
		if ( ! is_array( $value ) ) {
			return array();
		}
		$mappings = array();
		foreach ( $value as $mapping ) {
			if ( ! is_array( $mapping ) ) {
				continue;
			}
			$viewport = isset( $mapping['viewport'] ) && is_array( $mapping['viewport'] ) ? $mapping['viewport'] : array( 0, 0 );
			$viewport = array( max( 0, absint( isset( $viewport[0] ) ? $viewport[0] : 0 ) ), max( 0, absint( isset( $viewport[1] ) ? $viewport[1] : 0 ) ) );
			$sizes = self::normalize_sizes( isset( $mapping['sizes'] ) ? $mapping['sizes'] : array() );
			$mappings[] = array( 'viewport' => $viewport, 'sizes' => $sizes );
		}
		usort( $mappings, function( $a, $b ) {
			return $b['viewport'][0] - $a['viewport'][0];
		} );
		return $mappings;
	}

	public static function normalize_targeting( $value ) {
		$value = self::decode_jsonish( $value, array() );
		if ( ! is_array( $value ) ) {
			return array();
		}
		$targeting = array();
		foreach ( $value as $key => $item ) {
			$key = preg_replace( '/[^A-Za-z0-9_.-]/', '', (string) $key );
			if ( '' === $key ) {
				continue;
			}
			if ( is_array( $item ) ) {
				$values = array_values( array_unique( array_filter( array_map( 'sanitize_text_field', $item ), 'strlen' ) ) );
				if ( $values ) {
					$targeting[ $key ] = $values;
				}
			} else {
				$item = sanitize_text_field( (string) $item );
				if ( '' !== $item ) {
					$targeting[ $key ] = $item;
				}
			}
		}
		return $targeting;
	}

	public static function normalize_page_types( $value ) {
		$value = self::normalize_string_list( $value );
		$allowed = array_keys( self::page_types() );
		$value = array_values( array_intersect( $value, $allowed ) );
		if ( empty( $value ) || in_array( 'all', $value, true ) ) {
			return array( 'all' );
		}
		return $value;
	}

	public static function normalize_string_list( $value ) {
		$value = self::decode_jsonish( $value, $value );
		if ( is_string( $value ) ) {
			$value = preg_split( '/[,\r\n]+/', $value );
		}
		if ( ! is_array( $value ) ) {
			return array();
		}
		$items = array();
		foreach ( $value as $item ) {
			$item = sanitize_title( (string) $item );
			if ( '' !== $item ) {
				$items[] = $item;
			}
		}
		return array_values( array_unique( $items ) );
	}

	public static function normalize_min_height( $value ) {
		$value = self::decode_jsonish( $value, array() );
		$value = is_array( $value ) ? $value : array();
		return array(
			'desktop' => min( 3000, absint( isset( $value['desktop'] ) ? $value['desktop'] : 0 ) ),
			'tablet'  => min( 3000, absint( isset( $value['tablet'] ) ? $value['tablet'] : 0 ) ),
			'mobile'  => min( 3000, absint( isset( $value['mobile'] ) ? $value['mobile'] : 0 ) ),
		);
	}

	public static function normalize_schedule( $value ) {
		$value = self::decode_jsonish( $value, array() );
		$value = is_array( $value ) ? $value : array();
		return array(
			'start' => self::sanitize_datetime( isset( $value['start'] ) ? $value['start'] : '' ),
			'end'   => self::sanitize_datetime( isset( $value['end'] ) ? $value['end'] : '' ),
		);
	}

	public static function normalize_refresh( $value ) {
		$value = self::decode_jsonish( $value, array() );
		$value = is_array( $value ) ? $value : array();
		$enabled = self::truthy( isset( $value['enabled'] ) ? $value['enabled'] : false );
		$trigger = self::enum( isset( $value['trigger'] ) ? $value['trigger'] : 'time', array_keys( self::refresh_triggers() ), 'time' );
		$interval = absint( isset( $value['interval'] ) ? $value['interval'] : 30 );
		if ( $enabled && 'user_action' !== $trigger ) {
			$interval = max( 30, $interval );
		}
		return array(
			'enabled'           => $enabled,
			'trigger'           => $trigger,
			'interval'          => $interval,
			'max_refreshes'     => min( 100, absint( isset( $value['max_refreshes'] ) ? $value['max_refreshes'] : 0 ) ),
			'require_visible'   => self::truthy( isset( $value['require_visible'] ) ? $value['require_visible'] : true ),
			'pause_when_hidden' => self::truthy( isset( $value['pause_when_hidden'] ) ? $value['pause_when_hidden'] : true ),
		);
	}

	public static function infer_group( $legacy_placement ) {
		$value = sanitize_key( str_replace( '_', '-', (string) $legacy_placement ) );
		if ( in_array( $value, array( 'header', 'masthead', 'leaderboard', 'billboard' ), true ) ) {
			return 'header';
		}
		if ( in_array( $value, array( 'sidebar', 'skyscraper', 'halfpage' ), true ) ) {
			return 'sidebar';
		}
		if ( in_array( $value, array( 'footer' ), true ) ) {
			return 'footer';
		}
		if ( in_array( $value, array( 'mobile-sticky', 'interstitial', 'overlay', 'sticky' ), true ) ) {
			return 'overlay';
		}
		return 'content';
	}

	public static function compute_min_height( $sizes ) {
		$sizes = self::normalize_sizes( $sizes );
		$max = 0;
		foreach ( $sizes as $size ) {
			$max = max( $max, $size[1] );
		}
		return $max;
	}

	public static function sanitize_key( $value ) {
		$value = strtolower( (string) $value );
		$value = preg_replace( '/[^a-z0-9_-]+/', '_', remove_accents( $value ) );
		return trim( preg_replace( '/_+/', '_', $value ), '_-' );
	}

	public static function sanitize_ad_unit_path( $value ) {
		$value = trim( sanitize_text_field( (string) $value ) );
		if ( '' === $value ) {
			return '';
		}
		$value = '/' . ltrim( $value, '/' );
		return preg_replace( '#/+#', '/', $value );
	}

	private static function sanitize_datetime( $value ) {
		$value = trim( sanitize_text_field( (string) $value ) );
		if ( '' === $value ) {
			return '';
		}
		$timestamp = strtotime( $value );
		return $timestamp ? gmdate( 'Y-m-d\TH:i:s\Z', $timestamp ) : '';
	}

	private static function enum( $value, $allowed, $default ) {
		$value = sanitize_key( (string) $value );
		return in_array( $value, $allowed, true ) ? $value : $default;
	}

	private static function truthy( $value ) {
		if ( is_bool( $value ) ) {
			return $value;
		}
		return in_array( strtolower( (string) $value ), array( '1', 'true', 'yes', 'on' ), true );
	}

	private static function decode_jsonish( $value, $fallback ) {
		if ( ! is_string( $value ) ) {
			return $value;
		}
		$trimmed = trim( $value );
		if ( '' === $trimmed || ( '{' !== substr( $trimmed, 0, 1 ) && '[' !== substr( $trimmed, 0, 1 ) ) ) {
			return $value;
		}
		$decoded = json_decode( wp_unslash( $trimmed ), true );
		return JSON_ERROR_NONE === json_last_error() ? $decoded : $fallback;
	}
}
