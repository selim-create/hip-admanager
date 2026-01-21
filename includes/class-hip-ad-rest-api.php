<?php
/**
 * REST API endpoints
 *
 * @package HIP_Ad_Manager
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * HIP Ad REST API class
 */
class HIP_Ad_REST_API {

	/**
	 * Namespace
	 */
	const NAMESPACE = 'hip-ads/v1';

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->register_routes();
	}

	/**
	 * Register REST API routes
	 */
	public function register_routes() {
		// Get config
		register_rest_route(
			self::NAMESPACE,
			'/config',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_config' ),
				'permission_callback' => '__return_true',
			)
		);

		// Get all slots
		register_rest_route(
			self::NAMESPACE,
			'/slots',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_slots' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'page_type' => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'device'    => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'category'  => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'placement' => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		// Get single slot
		register_rest_route(
			self::NAMESPACE,
			'/slots/(?P<id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_slot' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'id' => array(
						'validate_callback' => function( $param ) {
							return is_numeric( $param );
						},
					),
				),
			)
		);

		// Track impression/click
		register_rest_route(
			self::NAMESPACE,
			'/track',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'track' ),
				'permission_callback' => '__return_true',
			)
		);

		// ads.txt endpoint
		register_rest_route(
			self::NAMESPACE,
			'/ads-txt',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_ads_txt' ),
				'permission_callback' => '__return_true',
			)
		);

		// Clear cache endpoint
		register_rest_route(
			self::NAMESPACE,
			'/cache/clear',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'clear_cache' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);
	}

	/**
	 * Get config endpoint
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function get_config( $request ) {
		$settings = get_option( 'hip_ad_manager_settings', array() );

		$config = array(
			'networkCode'         => isset( $settings['network_code'] ) ? $settings['network_code'] : '',
			'siteName'            => isset( $settings['site_name'] ) ? $settings['site_name'] : '',
			'enableLazyLoad'      => isset( $settings['enable_lazy_load'] ) ? (bool) $settings['enable_lazy_load'] : true,
			'enableSingleRequest' => isset( $settings['enable_single_request'] ) ? (bool) $settings['enable_single_request'] : true,
			'globalTargeting'     => isset( $settings['global_targeting'] ) ? json_decode( $settings['global_targeting'], true ) : new stdClass(),
			'lazyLoadConfig'      => array(
				'enabled'            => isset( $settings['enable_lazy_load'] ) ? (bool) $settings['enable_lazy_load'] : true,
				'strategy'           => 'intersection',
				'fetchMarginPercent' => 200,
				'renderMarginPercent' => 100,
				'mobileScaling'      => 2.0,
				'idleTimeout'        => 200,
			),
		);

		return new WP_REST_Response( $config, 200 );
	}

	/**
	 * Get slots endpoint
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function get_slots( $request ) {
		$params = $request->get_params();
		
		// Check cache first
		$slots_data = $this->get_cached_slots( $params );
		
		if ( $slots_data !== false ) {
			return new WP_REST_Response( $slots_data, 200, array(
				'Cache-Control' => 'public, max-age=3600',
				'X-Cache'       => 'HIT',
			) );
		}
		
		// Fetch from database
		$slots_data = $this->fetch_slots_from_db( $params );
		
		// Cache the result
		$this->cache_slots( $params, $slots_data );
		
		return new WP_REST_Response( $slots_data, 200, array(
			'Cache-Control' => 'public, max-age=3600',
			'X-Cache'       => 'MISS',
			'ETag'          => md5( wp_json_encode( $slots_data ) ),
		) );
	}
	
	/**
	 * Get cached slots
	 *
	 * @param array $params
	 * @return array|false
	 */
	private function get_cached_slots( $params ) {
		$cache_key = 'hip_ad_slots_' . md5( wp_json_encode( $params ) );
		
		return get_transient( $cache_key );
	}
	
	/**
	 * Cache slots data
	 *
	 * @param array $params
	 * @param array $data
	 */
	private function cache_slots( $params, $data ) {
		$cache_key  = 'hip_ad_slots_' . md5( wp_json_encode( $params ) );
		$cache_time = apply_filters( 'hip_ad_cache_duration', HOUR_IN_SECONDS );
		
		set_transient( $cache_key, $data, $cache_time );
	}
	
	/**
	 * Fetch slots from database
	 *
	 * @param array $params
	 * @return array
	 */
	private function fetch_slots_from_db( $params ) {
		$settings = get_option( 'hip_ad_manager_settings', array() );

		// Build query args
		$query_args = array(
			'post_type'      => HIP_Ad_Slot::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => array(
				array(
					'key'     => 'gam_status',
					'value'   => 'active',
					'compare' => '=',
				),
			),
		);

		// Filter by device
		if ( ! empty( $params['device'] ) ) {
			$query_args['meta_query'][] = array(
				'relation' => 'OR',
				array(
					'key'     => 'gam_device',
					'value'   => $params['device'],
					'compare' => '=',
				),
				array(
					'key'     => 'gam_device',
					'value'   => 'all',
					'compare' => '=',
				),
			);
		}

		// Filter by placement
		if ( ! empty( $params['placement'] ) ) {
			$query_args['meta_query'][] = array(
				'key'     => 'gam_placement',
				'value'   => $params['placement'],
				'compare' => '=',
			);
		}

		$query = new WP_Query( $query_args );
		$slots = array();

		foreach ( $query->posts as $post ) {
			$slots[] = HIP_Ad_Slot::format_slot_data( $post );
		}

		return array(
			'networkCode'         => isset( $settings['network_code'] ) ? $settings['network_code'] : '',
			'enableLazyLoad'      => isset( $settings['enable_lazy_load'] ) ? (bool) $settings['enable_lazy_load'] : true,
			'enableSingleRequest' => isset( $settings['enable_single_request'] ) ? (bool) $settings['enable_single_request'] : true,
			'globalTargeting'     => array(
				'site' => isset( $settings['site_name'] ) ? $settings['site_name'] : '',
				'env'  => wp_get_environment_type(),
			),
			'dynamicTargetingKeys' => array( 'category', 'tags', 'author', 'postType', 'customKey' ),
			'slots'               => $slots,
		);
	}

	/**
	 * Get single slot endpoint
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_slot( $request ) {
		$id = $request->get_param( 'id' );
		$post = get_post( $id );

		if ( ! $post || HIP_Ad_Slot::POST_TYPE !== $post->post_type ) {
			return new WP_Error( 'not_found', __( 'Ad slot not found', 'hip-admanager' ), array( 'status' => 404 ) );
		}

		$slot = HIP_Ad_Slot::format_slot_data( $post );

		return new WP_REST_Response( $slot, 200 );
	}

	/**
	 * Track endpoint (optional)
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function track( $request ) {
		// Optional: Implement tracking logic here
		// For now, just return success
		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Tracked successfully', 'hip-admanager' ),
			),
			200
		);
	}
	
	/**
	 * Get ads.txt content
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function get_ads_txt( $request ) {
		$content = get_option( 'hip_ad_ads_txt_content', '' );
		
		return new WP_REST_Response( $content, 200, array(
			'Content-Type' => 'text/plain; charset=utf-8',
		) );
	}
	
	/**
	 * Clear cache endpoint
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function clear_cache( $request ) {
		global $wpdb;
		
		// Delete all transients with our prefix
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_hip_ad_slots_%' OR option_name LIKE '_transient_timeout_hip_ad_slots_%'" );
		
		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Cache cleared successfully', 'hip-admanager' ),
			),
			200
		);
	}
}
