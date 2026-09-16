<?php
/**
 * Headless REST API.
 *
 * @package HIP_Ad_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HIP_Ad_REST_API {

	const NAMESPACE = 'hip-ads/v1';

	public function __construct() {
		$this->register_routes();
	}

	public function register_routes() {
		register_rest_route( self::NAMESPACE, '/config', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_config' ),
			'permission_callback' => '__return_true',
		) );

		register_rest_route( self::NAMESPACE, '/slots', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_slots' ),
			'permission_callback' => '__return_true',
			'args'                => array(
				'device'          => array( 'sanitize_callback' => 'sanitize_key' ),
				'placement'       => array( 'sanitize_callback' => array( 'HIP_Ad_Schema', 'sanitize_key' ) ),
				'placement_group' => array( 'sanitize_callback' => 'sanitize_key' ),
				'page_type'       => array( 'sanitize_callback' => 'sanitize_key' ),
				'category'        => array( 'sanitize_callback' => 'sanitize_title' ),
				'key'             => array( 'sanitize_callback' => array( 'HIP_Ad_Schema', 'sanitize_key' ) ),
			),
		) );

		register_rest_route( self::NAMESPACE, '/slots/(?P<id>\d+)', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_slot' ),
			'permission_callback' => '__return_true',
			'args'                => array( 'id' => array( 'sanitize_callback' => 'absint' ) ),
		) );

		register_rest_route( self::NAMESPACE, '/health', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_health' ),
			'permission_callback' => '__return_true',
		) );

		register_rest_route( self::NAMESPACE, '/ads-txt', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_ads_txt' ),
			'permission_callback' => '__return_true',
		) );

		register_rest_route( self::NAMESPACE, '/cache/clear', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'clear_cache' ),
			'permission_callback' => function() {
				return current_user_can( 'manage_options' );
			},
		) );
	}

	public function get_config() {
		return $this->cached_response( 'config', function() {
			$config = HIP_Ad_Settings::client_config();
			$slots = array_map( array( 'HIP_Ad_Repository', 'api_slot' ), $this->live_slots() );
			return array_merge( $config, array(
				'network_code'        => $config['networkCode'],
				'site_name'           => $config['propertyCode'],
				'enableSingleRequest' => $config['gpt']['singleRequest'],
				'enableLazyLoad'      => null !== $config['gpt']['lazyLoad'],
				'enableServices'      => true,
				'collapse_empty'      => $config['gpt']['collapseEmpty'],
				'slots'               => $slots,
				'generatedAt'         => gmdate( 'c' ),
				'cacheVersion'        => HIP_Ad_Repository::cache_version(),
			) );
		} );
	}

	public function get_slots( $request ) {
		$filters = array(
			'device'          => $request->get_param( 'device' ),
			'placement_group' => $request->get_param( 'placement_group' ),
			'page_type'       => $request->get_param( 'page_type' ),
			'category'        => $request->get_param( 'category' ),
		);
		$placement = $request->get_param( 'placement' );
		if ( $placement ) {
			if ( array_key_exists( $placement, HIP_Ad_Schema::placement_groups() ) ) {
				$filters['placement_group'] = $placement;
			} else {
				$filters['placement_key'] = $placement;
			}
		}
		$key = $request->get_param( 'key' );
		$cache_key = 'slots|' . wp_json_encode( $filters ) . '|' . $key;

		return $this->cached_response( $cache_key, function() use ( $filters, $key ) {
			$slots = $this->live_slots( array_filter( $filters ) );
			if ( $key ) {
				$slots = array_values( array_filter( $slots, function( $slot ) use ( $key ) {
					return $slot['key'] === $key;
				} ) );
			}
			$config = HIP_Ad_Settings::client_config();
			return array(
				'schemaVersion'       => HIP_Ad_Schema::VERSION,
				'networkCode'         => $config['networkCode'],
				'propertyCode'        => $config['propertyCode'],
				'adsEnabled'          => $config['adsEnabled'],
				'enableSingleRequest' => $config['gpt']['singleRequest'],
				'enableLazyLoad'      => null !== $config['gpt']['lazyLoad'],
				'gpt'                 => $config['gpt'],
				'globalTargeting'     => $config['globalTargeting'],
				'slots'               => array_map( array( 'HIP_Ad_Repository', 'api_slot' ), $slots ),
				'generatedAt'         => gmdate( 'c' ),
				'cacheVersion'        => HIP_Ad_Repository::cache_version(),
			);
		} );
	}

	public function get_slot( $request ) {
		$slot = HIP_Ad_Repository::get( absint( $request['id'] ) );
		if ( ! $slot || ! in_array( $slot['status'], array( 'active', 'scheduled' ), true ) || ! HIP_Ad_Repository::is_live( $slot ) ) {
			return new WP_Error( 'hip_ad_slot_not_found', __( 'Ad slot not found.', 'hip-admanager' ), array( 'status' => 404 ) );
		}
		return $this->response( HIP_Ad_Repository::api_slot( $slot ) );
	}

	public function get_health() {
		$settings = HIP_Ad_Settings::get_all();
		$stats = HIP_Ad_Repository::stats();
		$issues = HIP_Ad_Repository::diagnostics();
		$errors = count( array_filter( $issues, function( $issue ) { return 'error' === $issue['level']; } ) );
		return $this->response( array(
			'ok'            => 0 === $errors && ( ! $settings['ads_enabled'] || ! empty( $settings['network_code'] ) ),
			'schemaVersion' => HIP_Ad_Schema::VERSION,
			'adsEnabled'    => (bool) $settings['ads_enabled'],
			'activeSlots'   => count( $this->live_slots() ),
			'errors'        => $errors,
			'cacheVersion'  => HIP_Ad_Repository::cache_version(),
			'totalSlots'    => (int) $stats['total'],
		) );
	}

	public function get_ads_txt() {
		$content = HIP_Ad_Settings::get_ads_txt();
		return $this->response( array(
			'content'   => $content,
			'lineCount' => '' === $content ? 0 : count( preg_split( '/\r\n|\r|\n/', $content ) ),
		) );
	}

	public function clear_cache() {
		$version = HIP_Ad_Repository::bump_cache_version();
		return $this->response( array(
			'success'      => true,
			'message'      => __( 'Ad configuration cache cleared.', 'hip-admanager' ),
			'cacheVersion' => $version,
		) );
	}

	public function clear_slots_cache() {
		return HIP_Ad_Repository::bump_cache_version();
	}

	private function live_slots( $filters = array() ) {
		$filters = is_array( $filters ) ? $filters : array();
		unset( $filters['status'] );
		$slots = HIP_Ad_Repository::all( $filters );
		return array_values( array_filter( $slots, function( $slot ) {
			return in_array( $slot['status'], array( 'active', 'scheduled' ), true ) && HIP_Ad_Repository::is_live( $slot );
		} ) );
	}

	private function cached_response( $suffix, $callback ) {
		$settings = HIP_Ad_Settings::get_all();
		$ttl = (int) $settings['cache_duration'];
		$key = HIP_Ad_Repository::cache_key( $suffix );
		$data = $ttl > 0 ? get_transient( $key ) : false;
		$cache_status = false === $data ? 'MISS' : 'HIT';
		if ( false === $data ) {
			$data = call_user_func( $callback );
			if ( $ttl > 0 ) {
				set_transient( $key, $data, $ttl );
			}
		}
		if ( $settings['debug_mode'] && is_array( $data ) ) {
			$data['debug'] = array( 'cache' => $cache_status );
		}
		return $this->response( $data, $ttl, $cache_status );
	}

	private function response( $data, $ttl = null, $cache_status = null ) {
		if ( null === $ttl ) {
			$ttl = (int) HIP_Ad_Settings::get( 'cache_duration', 300 );
		}
		$headers = array(
			'Cache-Control'    => $ttl > 0 ? 'public, max-age=' . $ttl . ', stale-while-revalidate=' . max( 60, $ttl ) : 'no-cache, no-store, must-revalidate',
			'ETag'             => '"' . md5( wp_json_encode( $data ) ) . '"',
			'X-HIP-Ads-Schema' => (string) HIP_Ad_Schema::VERSION,
		);
		if ( $cache_status ) {
			$headers['X-HIP-Ads-Cache'] = $cache_status;
		}
		return new WP_REST_Response( $data, 200, $headers );
	}
}
