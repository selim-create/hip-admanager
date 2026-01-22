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
		$debug_mode = isset( $settings['debug_mode'] ) ? (bool) $settings['debug_mode'] : false;

		// Fetch all active slots
		$slots = $this->fetch_slots_from_db( array() );

		$config = array(
			// Keep both camelCase AND snake_case for backward compatibility
			'networkCode'         => isset( $settings['network_code'] ) ? $settings['network_code'] : '',
			'network_code'        => isset( $settings['network_code'] ) ? $settings['network_code'] : '',
			'siteName'            => isset( $settings['site_name'] ) ? $settings['site_name'] : '',
			'site_name'           => isset( $settings['site_name'] ) ? $settings['site_name'] : '',
			'enableLazyLoad'      => isset( $settings['enable_lazy_load'] ) ? (bool) $settings['enable_lazy_load'] : true,
			'enableSingleRequest' => isset( $settings['enable_single_request'] ) ? (bool) $settings['enable_single_request'] : true,
			'single_request'      => isset( $settings['enable_single_request'] ) ? (bool) $settings['enable_single_request'] : true,
			'globalTargeting'     => isset( $settings['global_targeting'] ) ? json_decode( $settings['global_targeting'], true ) : new stdClass(),
			'collapse_empty'      => true,
			'enable_services'     => true,
			'debug_mode'          => $debug_mode,
			'property_code'       => isset( $settings['site_name'] ) ? $settings['site_name'] : 'default',
			
			// Lazy load config in both formats
			'lazyLoadConfig'      => array(
				'enabled'            => isset( $settings['enable_lazy_load'] ) ? (bool) $settings['enable_lazy_load'] : true,
				'strategy'           => 'intersection',
				'fetchMarginPercent' => 200,
				'renderMarginPercent' => 100,
				'mobileScaling'      => 2.0,
				'idleTimeout'        => 200,
			),
			'lazy_load' => array(
				'enabled'        => isset( $settings['enable_lazy_load'] ) ? (bool) $settings['enable_lazy_load'] : true,
				'fetch_margin'   => 500,
				'render_margin'  => 200,
				'mobile_scaling' => 2.0,
			),
			
			// IMPORTANT: Include slots in config response!
			'slots' => $this->format_slots_for_frontend( isset( $slots['slots'] ) ? $slots['slots'] : array() ),
		);

		// Add debug information if debug mode is enabled
		if ( $debug_mode ) {
			$config['debug'] = array(
				'enabled'       => true,
				'timestamp'     => current_time( 'c' ),
				'cacheStatus'   => $this->get_cache_status(),
				'phpVersion'    => PHP_VERSION,
				'wpVersion'     => get_bloginfo( 'version' ),
				'pluginVersion' => HIP_AD_MANAGER_VERSION,
				'slotsCount'    => count( isset( $slots['slots'] ) ? $slots['slots'] : array() ),
			);
		}

		return new WP_REST_Response( $config, 200, array(
			'Cache-Control' => 'public, max-age=3600',
		) );
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
		$debug_mode = isset( $settings['debug_mode'] ) ? (bool) $settings['debug_mode'] : false;

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
			$slot_data = HIP_Ad_Slot::format_slot_data( $post );
			
			// Add debug information if debug mode is enabled
			if ( $debug_mode ) {
				$slot_data['debug'] = $this->get_slot_debug_info( $post, $slot_data );
			}
			
			$slots[] = $slot_data;
		}

		$response = array(
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

		// Add debug information if debug mode is enabled
		if ( $debug_mode ) {
			$response['debug'] = array(
				'enabled'       => true,
				'timestamp'     => current_time( 'c' ),
				'cacheStatus'   => $this->get_cache_status(),
				'phpVersion'    => PHP_VERSION,
				'wpVersion'     => get_bloginfo( 'version' ),
				'pluginVersion' => HIP_AD_MANAGER_VERSION,
			);
		}

		return $response;
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
		$this->clear_slots_cache();
		
		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Cache cleared successfully', 'hip-admanager' ),
			),
			200
		);
	}
	
	/**
	 * Clear all slots cache
	 * 
	 * Note: Uses direct database query because WordPress doesn't provide a way
	 * to delete multiple transients by wildcard. This is necessary to clear all
	 * cached variations of slot queries (different parameters = different cache keys).
	 * 
	 * For large sites with many transients, this could be optimized by:
	 * - Implementing as a background task
	 * - Using object cache flush if available
	 * - Storing cache keys in an option for targeted deletion
	 */
	public function clear_slots_cache() {
		global $wpdb;
		
		// Delete all transients with our prefix
		// Using $wpdb->prepare() for security
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$wpdb->esc_like( '_transient_hip_ad_slots_' ) . '%',
				$wpdb->esc_like( '_transient_timeout_hip_ad_slots_' ) . '%'
			)
		);
	}

	/**
	 * Get cache status
	 * Determines if cache is being used based on transient existence
	 *
	 * @return string 'HIT' or 'MISS'
	 */
	private function get_cache_status() {
		// Use a lightweight check - just see if we have any cached result
		// This avoids counting all transients on every request
		$cache_key = 'hip_ad_cache_check';
		$cached = get_transient( $cache_key );
		
		if ( false !== $cached ) {
			return 'HIT';
		}
		
		// Set a short-lived transient to track cache status
		set_transient( $cache_key, 1, 60 ); // 60 seconds
		return 'MISS';
	}

	/**
	 * Get debug information for a slot
	 *
	 * @param WP_Post $post The slot post object
	 * @param array   $slot_data The formatted slot data
	 * @return array Debug information
	 */
	private function get_slot_debug_info( $post, $slot_data ) {
		$sizes_raw = get_post_meta( $post->ID, 'gam_sizes', true );
		
		// Get all metadata but filter to only GAM-related fields for security
		// This prevents exposing potentially sensitive metadata
		$all_meta = get_post_meta( $post->ID );
		$filtered_meta = array();
		
		// Only include GAM-specific metadata fields
		$allowed_meta_keys = array(
			'gam_slot_id',
			'gam_ad_unit_path',
			'gam_sizes',
			'gam_size_mappings',
			'gam_targeting',
			'gam_placement',
			'gam_device',
			'gam_lazy_load',
			'gam_display_rules',
			'gam_status',
			'gam_priority',
			'_hip_ad_refresh_enabled',
			'_hip_ad_refresh_interval',
			'_hip_ad_max_refreshes',
		);
		
		foreach ( $allowed_meta_keys as $key ) {
			if ( isset( $all_meta[ $key ] ) ) {
				// Get the first value from the meta array
				$filtered_meta[ $key ] = $all_meta[ $key ][0];
			}
		}
		
		return array(
			'postId'      => $post->ID,
			'postStatus'  => $post->post_status,
			'created'     => $post->post_date,
			'modified'    => $post->post_modified,
			'sizesRaw'    => $sizes_raw,
			'filteredMeta' => $filtered_meta,
			'sizeLabel'   => $this->get_size_label( $slot_data['sizes'] ),
			'displayInfo' => sprintf(
				'Slot ID: %s | Sizes: %s | Placement: %s',
				$slot_data['slotId'],
				$this->get_size_label( $slot_data['sizes'] ),
				$slot_data['placement']
			),
		);
	}

	/**
	 * Convert sizes array to readable string
	 *
	 * @param array $sizes Array of size pairs [[width, height], ...]
	 * @return string Formatted size label (e.g., "970x250, 728x90")
	 */
	private function get_size_label( $sizes ) {
		if ( empty( $sizes ) ) {
			return 'No sizes';
		}
		
		$labels = array_map(
			function( $size ) {
				if ( is_array( $size ) && count( $size ) === 2 ) {
					return $size[0] . 'x' . $size[1];
				}
				return 'Invalid';
			},
			$sizes
		);
		
		return implode( ', ', $labels );
	}

	/**
	 * Format slots array for frontend consumption
	 * Ensures both camelCase and snake_case keys for compatibility
	 *
	 * @param array $slots Raw slots from database
	 * @return array Formatted slots
	 */
	private function format_slots_for_frontend( $slots ) {
		if ( empty( $slots ) ) {
			return array();
		}
		
		$formatted = array();
		
		foreach ( $slots as $slot ) {
			// Determine devices array
			$devices = isset( $slot['devices'] ) ? $slot['devices'] : array( 'desktop', 'tablet', 'mobile' );
			if ( isset( $slot['device'] ) && ! isset( $slot['devices'] ) ) {
				$devices = array( $slot['device'] );
			}
			
			// Determine enabled status
			$enabled = isset( $slot['enabled'] ) ? (bool) $slot['enabled'] : true;
			if ( ! isset( $slot['enabled'] ) && isset( $slot['status'] ) ) {
				$enabled = ( $slot['status'] === 'active' );
			}
			
			// Build formatted slot with both naming conventions
			$formatted_slot = array(
				// IDs
				'id'           => isset( $slot['id'] ) ? $slot['id'] : '',
				'slot_id'      => $this->get_dual_field( $slot, 'slot_id', 'slotId' ),
				'slotId'       => $this->get_dual_field( $slot, 'slotId', 'slot_id' ),
				
				// Name and path
				'name'         => isset( $slot['name'] ) ? $slot['name'] : '',
				'ad_unit_path' => $this->get_dual_field( $slot, 'ad_unit_path', 'adUnitPath' ),
				'adUnitPath'   => $this->get_dual_field( $slot, 'adUnitPath', 'ad_unit_path' ),
				
				// Sizes - ensure proper format
				'sizes'        => $this->format_sizes( isset( $slot['sizes'] ) ? $slot['sizes'] : array() ),
				'size_mapping' => $this->get_dual_field( $slot, 'size_mapping', 'sizeMappings', array() ),
				'sizeMappings' => $this->get_dual_field( $slot, 'sizeMappings', 'size_mapping', array() ),
				
				// Placement and device
				'placement'    => isset( $slot['placement'] ) ? $slot['placement'] : 'in-content',
				'devices'      => $devices,
				'device'       => isset( $slot['device'] ) ? $slot['device'] : 'all',
				
				// Targeting
				'targeting'    => isset( $slot['targeting'] ) ? $slot['targeting'] : array(),
				
				// Settings
				'lazy_load'       => $this->get_dual_bool_field( $slot, 'lazy_load', 'lazyLoad', true ),
				'lazyLoad'        => $this->get_dual_bool_field( $slot, 'lazyLoad', 'lazy_load', true ),
				'refresh_interval' => isset( $slot['refresh_interval'] ) ? (int) $slot['refresh_interval'] : 0,
				'min_height'      => $this->get_dual_int_field( $slot, 'min_height', 'minHeight', 0 ),
				'minHeight'       => $this->get_dual_int_field( $slot, 'minHeight', 'min_height', 0 ),
				
				// Status
				'enabled'      => $enabled,
				'status'       => isset( $slot['status'] ) ? $slot['status'] : 'active',
				'priority'     => isset( $slot['priority'] ) ? (int) $slot['priority'] : 10,
			);
			
			$formatted[] = $formatted_slot;
		}
		
		return $formatted;
	}

	/**
	 * Get field value with fallback to alternative naming convention
	 *
	 * @param array  $data Array to search
	 * @param string $primary Primary field name
	 * @param string $fallback Fallback field name
	 * @param mixed  $default Default value if neither exists
	 * @return mixed Field value or default
	 */
	private function get_dual_field( $data, $primary, $fallback, $default = '' ) {
		if ( isset( $data[ $primary ] ) ) {
			return $data[ $primary ];
		}
		if ( isset( $data[ $fallback ] ) ) {
			return $data[ $fallback ];
		}
		return $default;
	}

	/**
	 * Get boolean field value with fallback to alternative naming convention
	 *
	 * @param array  $data Array to search
	 * @param string $primary Primary field name
	 * @param string $fallback Fallback field name
	 * @param bool   $default Default value if neither exists
	 * @return bool Field value as boolean or default
	 */
	private function get_dual_bool_field( $data, $primary, $fallback, $default = false ) {
		if ( isset( $data[ $primary ] ) ) {
			return (bool) $data[ $primary ];
		}
		if ( isset( $data[ $fallback ] ) ) {
			return (bool) $data[ $fallback ];
		}
		return $default;
	}

	/**
	 * Get int field value with fallback to alternative naming convention
	 *
	 * @param array  $data Array to search
	 * @param string $primary Primary field name
	 * @param string $fallback Fallback field name
	 * @param int    $default Default value if neither exists
	 * @return int Field value as integer or default
	 */
	private function get_dual_int_field( $data, $primary, $fallback, $default = 0 ) {
		if ( isset( $data[ $primary ] ) ) {
			return (int) $data[ $primary ];
		}
		if ( isset( $data[ $fallback ] ) ) {
			return (int) $data[ $fallback ];
		}
		return $default;
	}

	/**
	 * Format sizes to ensure consistent structure
	 *
	 * @param mixed $sizes Raw sizes data
	 * @return array Formatted sizes array
	 */
	private function format_sizes( $sizes ) {
		if ( empty( $sizes ) ) {
			return array();
		}
		
		// If already in correct format [[width, height], ...]
		if ( is_array( $sizes ) && isset( $sizes[0] ) && is_array( $sizes[0] ) ) {
			return array_map( array( $this, 'format_single_size' ), $sizes );
		}
		
		// If in object format [{width, height}, ...]
		if ( is_array( $sizes ) && isset( $sizes[0]['width'] ) ) {
			return $sizes;
		}
		
		return array();
	}

	/**
	 * Format a single size to ensure width/height structure
	 *
	 * @param array $size Size data (either [width, height] or {width, height})
	 * @return array Formatted size with width and height keys
	 */
	private function format_single_size( $size ) {
		$width = 0;
		$height = 0;
		
		// Handle array format [width, height]
		if ( isset( $size[0] ) ) {
			$width = (int) $size[0];
		} elseif ( isset( $size['width'] ) ) {
			$width = (int) $size['width'];
		}
		
		// Handle array format [width, height]
		if ( isset( $size[1] ) ) {
			$height = (int) $size[1];
		} elseif ( isset( $size['height'] ) ) {
			$height = (int) $size['height'];
		}
		
		return array(
			'width'  => $width,
			'height' => $height,
		);
	}
}
