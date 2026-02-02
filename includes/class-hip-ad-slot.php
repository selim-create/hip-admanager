<?php
/**
 * Ad Slot Custom Post Type
 *
 * @package HIP_Ad_Manager
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * HIP Ad Slot class
 */
class HIP_Ad_Slot {

	/**
	 * Post type name
	 */
	const POST_TYPE = 'hip_ad_slot';

	/**
	 * Constructor
	 */
	public function __construct() {
		// Register CPT directly (called during init hook from HIP_Ad_Manager::init)
		self::register_post_type();
		
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( $this, 'save_meta_boxes' ), 10, 2 );
		
		// Admin columns
		add_filter( 'manage_hip_ad_slot_posts_columns', array( $this, 'add_custom_columns' ) );
		add_action( 'manage_hip_ad_slot_posts_custom_column', array( $this, 'render_custom_columns' ), 10, 2 );
		add_filter( 'manage_edit-hip_ad_slot_sortable_columns', array( $this, 'sortable_columns' ) );
		
		// Admin filters
		add_action( 'restrict_manage_posts', array( $this, 'add_admin_filters' ) );
		add_filter( 'parse_query', array( $this, 'filter_query' ) );
	}

	/**
	 * Register custom post type
	 */
	public static function register_post_type() {
		$labels = array(
			'name'                  => __( 'Ad Slots', 'hip-admanager' ),
			'singular_name'         => __( 'Ad Slot', 'hip-admanager' ),
			'menu_name'             => __( 'Ad Slots', 'hip-admanager' ),
			'add_new'               => __( 'Add New', 'hip-admanager' ),
			'add_new_item'          => __( 'Add New Ad Slot', 'hip-admanager' ),
			'edit_item'             => __( 'Edit Ad Slot', 'hip-admanager' ),
			'new_item'              => __( 'New Ad Slot', 'hip-admanager' ),
			'view_item'             => __( 'View Ad Slot', 'hip-admanager' ),
			'search_items'          => __( 'Search Ad Slots', 'hip-admanager' ),
			'not_found'             => __( 'No ad slots found', 'hip-admanager' ),
			'not_found_in_trash'    => __( 'No ad slots found in trash', 'hip-admanager' ),
		);

		$args = array(
			'labels'              => $labels,
			'public'              => false,
			'publicly_queryable'  => false,
			'show_ui'             => true,
			'show_in_menu'        => false,
			'query_var'           => true,
			'rewrite'             => false,
			'capability_type'     => 'post',
			'has_archive'         => false,
			'hierarchical'        => false,
			'menu_position'       => null,
			'show_in_rest'        => true,
			'rest_base'           => 'ad-slots',
			'supports'            => array( 'title' ),
		);

		register_post_type( self::POST_TYPE, $args );
	}

	/**
	 * Add meta boxes
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'hip_ad_gam_info',
			__( 'GAM Information', 'hip-admanager' ),
			array( $this, 'render_gam_info_metabox' ),
			self::POST_TYPE,
			'normal',
			'high'
		);

		add_meta_box(
			'hip_ad_sizes',
			__( 'Ad Sizes', 'hip-admanager' ),
			array( $this, 'render_sizes_metabox' ),
			self::POST_TYPE,
			'normal',
			'high'
		);

		add_meta_box(
			'hip_ad_targeting',
			__( 'Targeting', 'hip-admanager' ),
			array( $this, 'render_targeting_metabox' ),
			self::POST_TYPE,
			'normal',
			'default'
		);
		
		add_meta_box(
			'hip_ad_refresh',
			__( 'Refresh Settings', 'hip-admanager' ),
			array( $this, 'render_refresh_metabox' ),
			self::POST_TYPE,
			'normal',
			'default'
		);

		add_meta_box(
			'hip_ad_display_rules',
			__( 'Display Rules', 'hip-admanager' ),
			array( $this, 'render_display_rules_metabox' ),
			self::POST_TYPE,
			'side',
			'default'
		);

		add_meta_box(
			'hip_ad_status',
			__( 'Status & Priority', 'hip-admanager' ),
			array( $this, 'render_status_metabox' ),
			self::POST_TYPE,
			'side',
			'default'
		);
	}

	/**
	 * Render GAM Info metabox
	 */
	public function render_gam_info_metabox( $post ) {
		wp_nonce_field( 'hip_ad_slot_meta', 'hip_ad_slot_nonce' );
		
		$gam_slot_id = get_post_meta( $post->ID, 'gam_slot_id', true );
		$gam_ad_unit_path = get_post_meta( $post->ID, 'gam_ad_unit_path', true );
		
		include HIP_AD_MANAGER_PLUGIN_DIR . 'admin/views/metaboxes/gam-info.php';
	}

	/**
	 * Render Sizes metabox
	 */
	public function render_sizes_metabox( $post ) {
		$gam_sizes = get_post_meta( $post->ID, 'gam_sizes', true );
		$gam_size_mappings = get_post_meta( $post->ID, 'gam_size_mappings', true );
		
		if ( empty( $gam_sizes ) ) {
			$gam_sizes = '[]';
		}
		if ( empty( $gam_size_mappings ) ) {
			$gam_size_mappings = '[]';
		}
		
		include HIP_AD_MANAGER_PLUGIN_DIR . 'admin/views/metaboxes/sizes.php';
	}

	/**
	 * Render Targeting metabox
	 */
	public function render_targeting_metabox( $post ) {
		$gam_targeting = get_post_meta( $post->ID, 'gam_targeting', true );
		
		if ( empty( $gam_targeting ) ) {
			$gam_targeting = '{}';
		}
		
		include HIP_AD_MANAGER_PLUGIN_DIR . 'admin/views/metaboxes/targeting.php';
	}
	
	/**
	 * Render Refresh metabox
	 */
	public function render_refresh_metabox( $post ) {
		$refresh_enabled = get_post_meta( $post->ID, '_hip_ad_refresh_enabled', true );
		$refresh_interval = get_post_meta( $post->ID, '_hip_ad_refresh_interval', true );
		$max_refreshes = get_post_meta( $post->ID, '_hip_ad_max_refreshes', true );
		
		if ( empty( $refresh_interval ) ) {
			$refresh_interval = '30';
		}
		if ( empty( $max_refreshes ) ) {
			$max_refreshes = '10';
		}
		
		include HIP_AD_MANAGER_PLUGIN_DIR . 'admin/views/metaboxes/refresh.php';
	}

	/**
	 * Render Display Rules metabox
	 */
	public function render_display_rules_metabox( $post ) {
		$gam_placement = get_post_meta( $post->ID, 'gam_placement', true );
		$gam_device = get_post_meta( $post->ID, 'gam_device', true );
		$gam_lazy_load = get_post_meta( $post->ID, 'gam_lazy_load', true );
		$gam_display_rules = get_post_meta( $post->ID, 'gam_display_rules', true );
		
		if ( empty( $gam_device ) ) {
			$gam_device = 'all';
		}
		if ( empty( $gam_display_rules ) ) {
			$gam_display_rules = '{}';
		}
		
		include HIP_AD_MANAGER_PLUGIN_DIR . 'admin/views/metaboxes/display-rules.php';
	}

	/**
	 * Render Status metabox
	 */
	public function render_status_metabox( $post ) {
		$gam_status = get_post_meta( $post->ID, 'gam_status', true );
		$gam_priority = get_post_meta( $post->ID, 'gam_priority', true );
		
		if ( empty( $gam_status ) ) {
			$gam_status = 'active';
		}
		if ( empty( $gam_priority ) ) {
			$gam_priority = '10';
		}
		
		include HIP_AD_MANAGER_PLUGIN_DIR . 'admin/views/metaboxes/status.php';
	}

	/**
	 * Save meta boxes
	 */
	public function save_meta_boxes( $post_id, $post ) {
		// Check nonce
		if ( ! isset( $_POST['hip_ad_slot_nonce'] ) || ! wp_verify_nonce( $_POST['hip_ad_slot_nonce'], 'hip_ad_slot_meta' ) ) {
			return;
		}

		// Check autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check permissions
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Save GAM info
		if ( isset( $_POST['gam_slot_id'] ) ) {
			update_post_meta( $post_id, 'gam_slot_id', sanitize_text_field( $_POST['gam_slot_id'] ) );
		}
		if ( isset( $_POST['gam_ad_unit_path'] ) ) {
			update_post_meta( $post_id, 'gam_ad_unit_path', sanitize_text_field( $_POST['gam_ad_unit_path'] ) );
		}

		// Save sizes (JSON)
		if ( isset( $_POST['gam_sizes'] ) ) {
			$sizes = json_decode( stripslashes( $_POST['gam_sizes'] ), true );
			if ( json_last_error() === JSON_ERROR_NONE ) {
				update_post_meta( $post_id, 'gam_sizes', wp_json_encode( $sizes ) );
			}
		}

		// Save size mappings (JSON)
		if ( isset( $_POST['gam_size_mappings'] ) ) {
			$size_mappings = json_decode( stripslashes( $_POST['gam_size_mappings'] ), true );
			if ( json_last_error() === JSON_ERROR_NONE ) {
				update_post_meta( $post_id, 'gam_size_mappings', wp_json_encode( $size_mappings ) );
			}
		}

		// Save targeting (JSON)
		if ( isset( $_POST['gam_targeting'] ) ) {
			$targeting = json_decode( stripslashes( $_POST['gam_targeting'] ), true );
			if ( json_last_error() === JSON_ERROR_NONE ) {
				update_post_meta( $post_id, 'gam_targeting', wp_json_encode( $targeting ) );
			}
		}

		// Save placement
		if ( isset( $_POST['gam_placement'] ) ) {
			update_post_meta( $post_id, 'gam_placement', sanitize_text_field( $_POST['gam_placement'] ) );
		}

		// Save device
		if ( isset( $_POST['gam_device'] ) ) {
			update_post_meta( $post_id, 'gam_device', sanitize_text_field( $_POST['gam_device'] ) );
		}

		// Save lazy load
		update_post_meta( $post_id, 'gam_lazy_load', isset( $_POST['gam_lazy_load'] ) ? '1' : '0' );

		// Save display rules (JSON)
		if ( isset( $_POST['gam_display_rules'] ) ) {
			$display_rules = json_decode( stripslashes( $_POST['gam_display_rules'] ), true );
			if ( json_last_error() === JSON_ERROR_NONE ) {
				update_post_meta( $post_id, 'gam_display_rules', wp_json_encode( $display_rules ) );
			}
		}

		// Save status
		if ( isset( $_POST['gam_status'] ) ) {
			update_post_meta( $post_id, 'gam_status', sanitize_text_field( $_POST['gam_status'] ) );
		}

		// Save priority
		if ( isset( $_POST['gam_priority'] ) ) {
			update_post_meta( $post_id, 'gam_priority', absint( $_POST['gam_priority'] ) );
		}
		
		// Save refresh settings
		update_post_meta( $post_id, '_hip_ad_refresh_enabled', isset( $_POST['hip_ad_refresh_enabled'] ) ? '1' : '0' );
		
		if ( isset( $_POST['hip_ad_refresh_interval'] ) ) {
			update_post_meta( $post_id, '_hip_ad_refresh_interval', absint( $_POST['hip_ad_refresh_interval'] ) );
		}
		
		if ( isset( $_POST['hip_ad_max_refreshes'] ) ) {
			update_post_meta( $post_id, '_hip_ad_max_refreshes', absint( $_POST['hip_ad_max_refreshes'] ) );
		}
		
		// Clear REST API cache when slot is saved
		if ( class_exists( 'HIP_Ad_Manager' ) ) {
			$manager = HIP_Ad_Manager::get_instance();
			if ( isset( $manager->rest_api ) && method_exists( $manager->rest_api, 'clear_slots_cache' ) ) {
				$manager->rest_api->clear_slots_cache();
			}
		}
	}

	/**
	 * Get all active ad slots
	 *
	 * @param array $args Query arguments
	 * @return array
	 */
	public static function get_active_slots( $args = array() ) {
		$defaults = array(
			'post_type'      => self::POST_TYPE,
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

		$args = wp_parse_args( $args, $defaults );
		$query = new WP_Query( $args );

		return $query->posts;
	}

	/**
	 * Format slot data for API response
	 *
	 * @param WP_Post $post
	 * @return array
	 */
	public static function format_slot_data( $post ) {
		$sizes = get_post_meta( $post->ID, 'gam_sizes', true );
		$size_mappings = get_post_meta( $post->ID, 'gam_size_mappings', true );
		$targeting = get_post_meta( $post->ID, 'gam_targeting', true );
		
		// Decode and normalize sizes
		$sizes_array = ! empty( $sizes ) ? json_decode( $sizes, true ) : array();
		$sizes_array = self::normalize_sizes( $sizes_array );
		
		// Decode and normalize size mappings
		$size_mappings_array = ! empty( $size_mappings ) ? json_decode( $size_mappings, true ) : array();
		$size_mappings_array = self::normalize_size_mappings( $size_mappings_array );

		return array(
			'id'                  => $post->ID,
			'name'                => $post->post_title,
			'slotId'              => get_post_meta( $post->ID, 'gam_slot_id', true ),
			'adUnitPath'          => get_post_meta( $post->ID, 'gam_ad_unit_path', true ),
			'sizes'               => $sizes_array,
			'sizeMappings'        => $size_mappings_array,
			'targeting'           => ! empty( $targeting ) ? json_decode( $targeting, true ) : new stdClass(),
			'lazyLoad'            => (bool) get_post_meta( $post->ID, 'gam_lazy_load', true ),
			'placement'           => get_post_meta( $post->ID, 'gam_placement', true ),
			'device'              => get_post_meta( $post->ID, 'gam_device', true ),
			'priority'            => (int) get_post_meta( $post->ID, 'gam_priority', true ),
			'minHeight'           => self::calculate_min_height( $sizes_array ),
			'responsiveMinHeight' => self::calculate_responsive_min_height( $size_mappings_array ),
			'placeholder'         => array(
				'enabled'         => true,
				'backgroundColor' => '#f0f0f0',
				'showLabel'       => true,
				'labelText'       => __( 'Advertisement', 'hip-admanager' ),
			),
			'refresh'             => array(
				'enabled'          => get_post_meta( $post->ID, '_hip_ad_refresh_enabled', true ) === '1',
				'interval'         => (int) get_post_meta( $post->ID, '_hip_ad_refresh_interval', true ) ?: 30,
				'maxRefreshes'     => (int) get_post_meta( $post->ID, '_hip_ad_max_refreshes', true ) ?: 10,
				'refreshOnVisible' => true,
				'pauseOnHidden'    => true,
			),
			'lazyLoadConfig'      => array(
				'enabled'            => (bool) get_post_meta( $post->ID, 'gam_lazy_load', true ),
				'strategy'           => 'intersection',
				'fetchMarginPercent' => 200,
				'renderMarginPercent' => 100,
				'mobileScaling'      => 2.0,
				'idleTimeout'        => 200,
			),
		);
	}
	
	/**
	 * Normalize sizes to consistent [[width, height], ...] array format
	 * Handles both [width, height] array format and {width: x, height: y} object format
	 *
	 * @param array $sizes Sizes array to normalize
	 * @return array Normalized sizes array
	 */
	private static function normalize_sizes( $sizes ) {
		if ( empty( $sizes ) || ! is_array( $sizes ) ) {
			return array();
		}
		
		$normalized = array();
		foreach ( $sizes as $size ) {
			if ( is_array( $size ) ) {
				// Array format: [width, height]
				if ( isset( $size[0] ) && isset( $size[1] ) ) {
					$normalized[] = array( (int) $size[0], (int) $size[1] );
				}
				// Object format: ['width' => x, 'height' => y]
				elseif ( isset( $size['width'] ) && isset( $size['height'] ) ) {
					$normalized[] = array( (int) $size['width'], (int) $size['height'] );
				}
			}
		}
		
		return $normalized;
	}
	
	/**
	 * Normalize size mappings to consistent format
	 * Ensures viewport is [width, height] and sizes are [[width, height], ...]
	 *
	 * @param array $mappings Size mappings array to normalize
	 * @return array Normalized size mappings array
	 */
	private static function normalize_size_mappings( $mappings ) {
		if ( empty( $mappings ) || ! is_array( $mappings ) ) {
			return array();
		}
		
		$normalized = array();
		foreach ( $mappings as $mapping ) {
			if ( ! is_array( $mapping ) ) {
				continue;
			}
			
			// Normalize viewport
			$viewport = array( 0, 0 );
			if ( isset( $mapping['viewport'] ) && is_array( $mapping['viewport'] ) ) {
				$viewport = array(
					(int) ( $mapping['viewport'][0] ?? 0 ),
					(int) ( $mapping['viewport'][1] ?? 0 )
				);
			}
			
			// Normalize sizes within mapping
			$sizes = array();
			if ( isset( $mapping['sizes'] ) ) {
				if ( $mapping['sizes'] === 'fluid' ) {
					$sizes = 'fluid';
				} else {
					$sizes = self::normalize_sizes( $mapping['sizes'] );
				}
			}
			
			$normalized[] = array(
				'viewport' => $viewport,
				'sizes'    => $sizes,
			);
		}
		
		return $normalized;
	}
	
	/**
	 * Calculate minimum height from sizes array
	 * 
	 * Note: Expects size format as [width, height] arrays, e.g., [728, 90]
	 *
	 * @param array $sizes Array of [width, height] pairs
	 * @return int Maximum height found in sizes
	 */
	private static function calculate_min_height( $sizes ) {
		if ( empty( $sizes ) || ! is_array( $sizes ) ) {
			return 0;
		}
		
		$max_height = 0;
		foreach ( $sizes as $size ) {
			// Size format: [width, height]
			if ( is_array( $size ) && isset( $size[1] ) && is_numeric( $size[1] ) ) {
				$max_height = max( $max_height, (int) $size[1] );
			}
		}
		
		return $max_height;
	}
	
	/**
	 * Calculate responsive minimum heights from size mappings
	 *
	 * @param array $size_mappings
	 * @return array
	 */
	private static function calculate_responsive_min_height( $size_mappings ) {
		$responsive_heights = array(
			'desktop' => 250,
			'tablet'  => 90,
			'mobile'  => 100,
		);
		
		// Allow customization of default heights
		$responsive_heights = apply_filters( 'hip_ad_default_responsive_heights', $responsive_heights );
		
		if ( empty( $size_mappings ) || ! is_array( $size_mappings ) ) {
			return $responsive_heights;
		}
		
		foreach ( $size_mappings as $mapping ) {
			if ( ! is_array( $mapping ) || ! isset( $mapping['viewport'] ) || ! isset( $mapping['sizes'] ) ) {
				continue;
			}
			
			$viewport_width = isset( $mapping['viewport'][0] ) ? (int) $mapping['viewport'][0] : 0;
			$max_height = 0;
			
			if ( ! is_array( $mapping['sizes'] ) ) {
				continue;
			}
			
			foreach ( $mapping['sizes'] as $size ) {
				if ( is_array( $size ) && isset( $size[1] ) && is_numeric( $size[1] ) ) {
					$max_height = max( $max_height, (int) $size[1] );
				}
			}
			
			// Map viewport to device type
			if ( $viewport_width >= 1024 ) {
				$responsive_heights['desktop'] = max( $responsive_heights['desktop'], $max_height );
			} elseif ( $viewport_width >= 768 ) {
				$responsive_heights['tablet'] = max( $responsive_heights['tablet'], $max_height );
			} else {
				$responsive_heights['mobile'] = max( $responsive_heights['mobile'], $max_height );
			}
		}
		
		return $responsive_heights;
	}
	
	/**
	 * Add custom columns to Ad Slots list
	 */
	public function add_custom_columns( $columns ) {
		$new_columns = array();
		foreach ( $columns as $key => $value ) {
			$new_columns[ $key ] = $value;
			if ( 'title' === $key ) {
				$new_columns['ad_sizes'] = __( 'Sizes', 'hip-admanager' );
				$new_columns['ad_device'] = __( 'Device', 'hip-admanager' );
				$new_columns['ad_placement'] = __( 'Placement', 'hip-admanager' );
				$new_columns['ad_status'] = __( 'Status', 'hip-admanager' );
			}
		}
		return $new_columns;
	}
	
	/**
	 * Render custom column content
	 */
	public function render_custom_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'ad_sizes':
				$sizes = get_post_meta( $post_id, 'gam_sizes', true );
				if ( empty( $sizes ) ) {
					$sizes = get_post_meta( $post_id, '_hip_ad_sizes', true );
				}
				if ( ! empty( $sizes ) ) {
					$sizes_array = is_string( $sizes ) ? json_decode( $sizes, true ) : $sizes;
					if ( is_array( $sizes_array ) ) {
						$formatted = array();
						foreach ( $sizes_array as $size ) {
							if ( is_array( $size ) && count( $size ) >= 2 ) {
								$formatted[] = $size[0] . 'x' . $size[1];
							}
						}
						echo esc_html( implode( ', ', $formatted ) );
					} else {
						echo '—';
					}
				} else {
					echo '—';
				}
				break;

			case 'ad_device':
				$device = get_post_meta( $post_id, 'gam_device', true );
				if ( empty( $device ) ) {
					$device = 'all';
				}
				$device_labels = array(
					'all' => __( 'All', 'hip-admanager' ),
					'mobile' => __( 'Mobile', 'hip-admanager' ),
					'desktop' => __( 'Desktop', 'hip-admanager' ),
					'tablet' => __( 'Tablet', 'hip-admanager' ),
				);
				echo esc_html( isset( $device_labels[ $device ] ) ? $device_labels[ $device ] : $device );
				break;

			case 'ad_placement':
				$placement = get_post_meta( $post_id, 'gam_placement', true );
				if ( empty( $placement ) ) {
					$placement = '—';
				}
				$placement_labels = array(
					'header' => __( 'Header', 'hip-admanager' ),
					'sidebar' => __( 'Sidebar', 'hip-admanager' ),
					'in-content' => __( 'In-Content', 'hip-admanager' ),
					'footer' => __( 'Footer', 'hip-admanager' ),
					'mobile-sticky' => __( 'Mobile Sticky', 'hip-admanager' ),
					'interstitial' => __( 'Interstitial', 'hip-admanager' ),
				);
				echo esc_html( isset( $placement_labels[ $placement ] ) ? $placement_labels[ $placement ] : $placement );
				break;

			case 'ad_status':
				$status = get_post_meta( $post_id, 'gam_status', true );
				if ( empty( $status ) || 'active' === $status ) {
					echo '<span style="color: #00a32a;">● ' . esc_html__( 'Active', 'hip-admanager' ) . '</span>';
				} else {
					echo '<span style="color: #787c82;">● ' . esc_html__( 'Paused', 'hip-admanager' ) . '</span>';
				}
				break;
		}
	}
	
	/**
	 * Make columns sortable
	 */
	public function sortable_columns( $columns ) {
		$columns['ad_device'] = 'ad_device';
		$columns['ad_placement'] = 'ad_placement';
		$columns['ad_status'] = 'ad_status';
		return $columns;
	}
	
	/**
	 * Add dropdown filters
	 */
	public function add_admin_filters( $post_type ) {
		if ( self::POST_TYPE !== $post_type ) {
			return;
		}

		// Device filter
		$current_device = isset( $_GET['filter_device'] ) ? sanitize_text_field( $_GET['filter_device'] ) : '';
		?>
		<select name="filter_device">
			<option value=""><?php esc_html_e( 'All Devices', 'hip-admanager' ); ?></option>
			<option value="all" <?php selected( $current_device, 'all' ); ?>><?php esc_html_e( 'All (Universal)', 'hip-admanager' ); ?></option>
			<option value="mobile" <?php selected( $current_device, 'mobile' ); ?>><?php esc_html_e( 'Mobile', 'hip-admanager' ); ?></option>
			<option value="desktop" <?php selected( $current_device, 'desktop' ); ?>><?php esc_html_e( 'Desktop', 'hip-admanager' ); ?></option>
			<option value="tablet" <?php selected( $current_device, 'tablet' ); ?>><?php esc_html_e( 'Tablet', 'hip-admanager' ); ?></option>
		</select>
		<?php

		// Placement filter
		$current_placement = isset( $_GET['filter_placement'] ) ? sanitize_text_field( $_GET['filter_placement'] ) : '';
		$placements = array(
			'header' => __( 'Header', 'hip-admanager' ),
			'sidebar' => __( 'Sidebar', 'hip-admanager' ),
			'in-content' => __( 'In-Content', 'hip-admanager' ),
			'footer' => __( 'Footer', 'hip-admanager' ),
			'mobile-sticky' => __( 'Mobile Sticky', 'hip-admanager' ),
			'interstitial' => __( 'Interstitial', 'hip-admanager' ),
		);
		?>
		<select name="filter_placement">
			<option value=""><?php esc_html_e( 'All Placements', 'hip-admanager' ); ?></option>
			<?php foreach ( $placements as $value => $label ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current_placement, $value ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}
	
	/**
	 * Filter query based on selected filters
	 */
	public function filter_query( $query ) {
		global $pagenow;

		if ( ! is_admin() || 'edit.php' !== $pagenow || ! $query->is_main_query() ) {
			return;
		}

		if ( self::POST_TYPE !== $query->get( 'post_type' ) ) {
			return;
		}

		$meta_query = array();

		// Filter by device
		if ( ! empty( $_GET['filter_device'] ) ) {
			$meta_query[] = array(
				'key' => 'gam_device',
				'value' => sanitize_text_field( $_GET['filter_device'] ),
				'compare' => '=',
			);
		}

		// Filter by placement
		if ( ! empty( $_GET['filter_placement'] ) ) {
			$meta_query[] = array(
				'key' => 'gam_placement',
				'value' => sanitize_text_field( $_GET['filter_placement'] ),
				'compare' => '=',
			);
		}

		if ( ! empty( $meta_query ) ) {
			$meta_query['relation'] = 'AND';
			$query->set( 'meta_query', $meta_query );
		}
	}
}
