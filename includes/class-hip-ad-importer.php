<?php
/**
 * CSV Import functionality
 *
 * @package HIP_Ad_Manager
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * HIP Ad Importer class
 */
class HIP_Ad_Importer {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_post_hip_ad_import_csv', array( $this, 'handle_import' ) );
	}

	/**
	 * Parse CSV file
	 *
	 * @param string $file_path
	 * @return array|WP_Error
	 */
	public function parse_csv( $file_path ) {
		if ( ! file_exists( $file_path ) ) {
			return new WP_Error( 'file_not_found', __( 'CSV file not found', 'hip-admanager' ) );
		}

		$rows = array();
		$header = array();

		if ( ( $handle = fopen( $file_path, 'r' ) ) !== false ) {
			$line_number = 0;
			while ( ( $data = fgetcsv( $handle, 10000, ',' ) ) !== false ) {
				$line_number++;
				
				// Skip comment lines
				if ( isset( $data[0] ) && strpos( $data[0], '#' ) === 0 ) {
					// This is the header line
					$header = array_map( function( $h ) {
						return trim( str_replace( '#', '', $h ) );
					}, $data );
					continue;
				}

				// Skip empty lines
				if ( empty( array_filter( $data ) ) ) {
					continue;
				}

				// Combine header with data
				if ( ! empty( $header ) ) {
					$rows[] = array_combine( $header, $data );
				}
			}
			fclose( $handle );
		}

		return $rows;
	}

	/**
	 * Process CSV rows and create preview data
	 *
	 * @param array $rows
	 * @return array
	 */
	public function process_rows( $rows ) {
		$settings = get_option( HIP_Ad_Settings::OPTION_NAME, array() );
		$network_code = isset( $settings['network_code'] ) ? $settings['network_code'] : '';
		
		$processed = array();

		foreach ( $rows as $row ) {
			$slot_data = $this->parse_row( $row, $network_code );
			if ( $slot_data ) {
				$processed[] = $slot_data;
			}
		}

		return $processed;
	}

	/**
	 * Parse single CSV row
	 *
	 * @param array  $row
	 * @param string $network_code
	 * @return array|null
	 */
	private function parse_row( $row, $network_code ) {
		// Required fields
		if ( empty( $row['ID'] ) || empty( $row['Name'] ) || empty( $row['Code'] ) ) {
			return null;
		}

		$name = trim( $row['Name'] );
		$code = trim( $row['Code'] );
		$sizes_str = isset( $row['Sizes:'] ) ? trim( $row['Sizes:'] ) : '';
		
		// Parse sizes
		$sizes = $this->parse_sizes( $sizes_str );
		
		// Determine placement from name
		$placement = $this->determine_placement( $name );
		
		// Determine device from name
		$device = $this->determine_device( $name );
		
		// Build ad unit path
		$ad_unit_path = '/' . $network_code . '/' . $code;
		
		// Get appropriate size mapping
		$size_mappings = $this->get_size_mappings_for_placement( $placement, $sizes );

		return array(
			'gam_slot_id'      => trim( $row['ID'] ),
			'name'             => $name,
			'gam_ad_unit_path' => $ad_unit_path,
			'gam_sizes'        => $sizes,
			'gam_size_mappings' => $size_mappings,
			'gam_placement'    => $placement,
			'gam_device'       => $device,
			'gam_lazy_load'    => true,
			'gam_status'       => 'active',
			'gam_priority'     => 10,
			'gam_targeting'    => array(),
		);
	}

	/**
	 * Parse sizes string into array
	 *
	 * @param string $sizes_str
	 * @return array
	 */
	private function parse_sizes( $sizes_str ) {
		if ( empty( $sizes_str ) ) {
			return array();
		}

		$sizes = array();
		$size_parts = explode( ';', $sizes_str );

		foreach ( $size_parts as $size ) {
			$size = trim( $size );
			if ( preg_match( '/^(\d+)x(\d+)$/', $size, $matches ) ) {
				$sizes[] = array( (int) $matches[1], (int) $matches[2] );
			}
		}

		return $sizes;
	}

	/**
	 * Determine placement type from ad name
	 *
	 * @param string $name
	 * @return string
	 */
	private function determine_placement( $name ) {
		$name_lower = strtolower( $name );

		$placement_map = array(
			'masthead'        => 'header',
			'leaderboard'     => 'header',
			'billboard'       => 'header',
			'mobilesticky'    => 'mobile-sticky',
			'mobile_sticky'   => 'mobile-sticky',
			'sticky'          => 'mobile-sticky',
			'interstitial'    => 'interstitial',
			'wideskyscraper'  => 'sidebar',
			'skyscraper'      => 'sidebar',
			'halfpage'        => 'sidebar',
			'mediumrectangle' => 'in-content',
			'rectangle'       => 'in-content',
			'mpu'             => 'in-content',
			'footer'          => 'footer',
		);

		foreach ( $placement_map as $keyword => $placement ) {
			if ( strpos( $name_lower, $keyword ) !== false ) {
				return $placement;
			}
		}

		return 'in-content'; // Default
	}

	/**
	 * Determine device type from ad name
	 *
	 * @param string $name
	 * @return string
	 */
	private function determine_device( $name ) {
		$name_lower = strtolower( $name );

		if ( strpos( $name_lower, 'mobile' ) !== false ) {
			return 'mobile';
		} elseif ( strpos( $name_lower, 'tablet' ) !== false ) {
			return 'tablet';
		} elseif ( strpos( $name_lower, 'desktop' ) !== false ) {
			return 'desktop';
		}

		return 'all'; // Default
	}

	/**
	 * Get size mappings for placement
	 *
	 * @param string $placement
	 * @param array  $sizes
	 * @return array
	 */
	private function get_size_mappings_for_placement( $placement, $sizes ) {
		$default_mappings = HIP_Ad_Settings::get_default_size_mappings();

		// Map placement to default mapping type
		$mapping_type_map = array(
			'header'        => 'leaderboard',
			'sidebar'       => 'skyscraper',
			'in-content'    => 'mpu',
			'mobile-sticky' => 'mobile_sticky',
		);

		$mapping_type = isset( $mapping_type_map[ $placement ] ) ? $mapping_type_map[ $placement ] : 'mpu';

		if ( isset( $default_mappings[ $mapping_type ] ) ) {
			return $default_mappings[ $mapping_type ];
		}

		// Fallback: create basic responsive mapping
		return array(
			array(
				'viewport' => array( 768, 0 ),
				'sizes'    => $sizes,
			),
			array(
				'viewport' => array( 0, 0 ),
				'sizes'    => ! empty( $sizes ) ? array( $sizes[0] ) : array(),
			),
		);
	}

	/**
	 * Create ad slots from processed data
	 *
	 * @param array $slots_data
	 * @return array Results
	 */
	public function create_slots( $slots_data ) {
		$results = array(
			'success' => array(),
			'failed'  => array(),
		);

		foreach ( $slots_data as $slot_data ) {
			$post_id = wp_insert_post(
				array(
					'post_title'  => $slot_data['name'],
					'post_type'   => HIP_Ad_Slot::POST_TYPE,
					'post_status' => 'publish',
				)
			);

			if ( is_wp_error( $post_id ) ) {
				$results['failed'][] = array(
					'name'  => $slot_data['name'],
					'error' => $post_id->get_error_message(),
				);
				continue;
			}

			// Save meta data
			update_post_meta( $post_id, 'gam_slot_id', $slot_data['gam_slot_id'] );
			update_post_meta( $post_id, 'gam_ad_unit_path', $slot_data['gam_ad_unit_path'] );
			update_post_meta( $post_id, 'gam_sizes', wp_json_encode( $slot_data['gam_sizes'] ) );
			update_post_meta( $post_id, 'gam_size_mappings', wp_json_encode( $slot_data['gam_size_mappings'] ) );
			update_post_meta( $post_id, 'gam_placement', $slot_data['gam_placement'] );
			update_post_meta( $post_id, 'gam_device', $slot_data['gam_device'] );
			update_post_meta( $post_id, 'gam_lazy_load', $slot_data['gam_lazy_load'] ? '1' : '0' );
			update_post_meta( $post_id, 'gam_status', $slot_data['gam_status'] );
			update_post_meta( $post_id, 'gam_priority', $slot_data['gam_priority'] );
			update_post_meta( $post_id, 'gam_targeting', wp_json_encode( $slot_data['gam_targeting'] ) );

			$results['success'][] = array(
				'id'   => $post_id,
				'name' => $slot_data['name'],
			);
		}

		return $results;
	}

	/**
	 * Handle CSV import form submission
	 */
	public function handle_import() {
		// Check nonce
		if ( ! isset( $_POST['hip_ad_import_nonce'] ) || ! wp_verify_nonce( $_POST['hip_ad_import_nonce'], 'hip_ad_import_csv' ) ) {
			wp_die( __( 'Security check failed', 'hip-admanager' ) );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have permission to perform this action', 'hip-admanager' ) );
		}

		// Check if file was uploaded
		if ( ! isset( $_FILES['csv_file'] ) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK ) {
			wp_redirect( admin_url( 'admin.php?page=hip-ad-import&error=upload_failed' ) );
			exit;
		}

		// Validate file type
		$file_type = wp_check_filetype( $_FILES['csv_file']['name'] );
		if ( 'csv' !== $file_type['ext'] && 'text/csv' !== $_FILES['csv_file']['type'] ) {
			wp_redirect( admin_url( 'admin.php?page=hip-ad-import&error=invalid_file_type' ) );
			exit;
		}

		// Parse CSV
		$rows = $this->parse_csv( $_FILES['csv_file']['tmp_name'] );
		if ( is_wp_error( $rows ) ) {
			wp_redirect( admin_url( 'admin.php?page=hip-ad-import&error=parse_failed' ) );
			exit;
		}

		// Process rows
		$processed = $this->process_rows( $rows );

		// Store in transient for preview
		set_transient( 'hip_ad_import_preview', $processed, HOUR_IN_SECONDS );

		wp_redirect( admin_url( 'admin.php?page=hip-ad-import&step=preview' ) );
		exit;
	}
}
