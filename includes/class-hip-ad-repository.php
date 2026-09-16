<?php
/**
 * Persistence and query layer for ad slots.
 *
 * @package HIP_Ad_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HIP_Ad_Repository {

	const CACHE_VERSION_OPTION = 'hip_ad_cache_version';

	public static function get( $post_id ) {
		$post = get_post( absint( $post_id ) );
		if ( ! $post || HIP_Ad_Slot::POST_TYPE !== $post->post_type || 'trash' === $post->post_status ) {
			return null;
		}
		return self::from_post( $post );
	}

	public static function all( $args = array() ) {
		$query_args = array(
			'post_type'      => HIP_Ad_Slot::POST_TYPE,
			'post_status'    => array( 'publish', 'draft', 'private' ),
			'posts_per_page' => -1,
			'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
			'order'          => 'ASC',
			'no_found_rows'  => true,
		);

		$query = new WP_Query( apply_filters( 'hip_ad_slots_query_args', $query_args, $args ) );
		$slots = array_map( array( __CLASS__, 'from_post' ), $query->posts );
		$slots = array_values( array_filter( $slots ) );

		$search = isset( $args['search'] ) ? strtolower( sanitize_text_field( $args['search'] ) ) : '';
		$filters = array(
			'status'          => isset( $args['status'] ) ? sanitize_key( $args['status'] ) : '',
			'device'          => isset( $args['device'] ) ? sanitize_key( $args['device'] ) : '',
			'placement_group' => isset( $args['placement_group'] ) ? sanitize_key( $args['placement_group'] ) : '',
			'placement_key'   => isset( $args['placement_key'] ) ? HIP_Ad_Schema::sanitize_key( $args['placement_key'] ) : '',
			'page_type'       => isset( $args['page_type'] ) ? sanitize_key( $args['page_type'] ) : '',
			'category'        => isset( $args['category'] ) ? sanitize_title( $args['category'] ) : '',
		);

		return array_values( array_filter( $slots, function( $slot ) use ( $search, $filters ) {
			if ( $search ) {
				$haystack = strtolower( implode( ' ', array( $slot['name'], $slot['key'], $slot['ad_unit_path'], $slot['placement_key'], $slot['inventory_id'] ) ) );
				if ( false === strpos( $haystack, $search ) ) {
					return false;
				}
			}
			if ( $filters['status'] && $slot['status'] !== $filters['status'] ) {
				return false;
			}
			if ( $filters['device'] && ! self::device_matches( $slot['device'], $filters['device'] ) ) {
				return false;
			}
			if ( $filters['placement_group'] && $slot['placement_group'] !== $filters['placement_group'] ) {
				return false;
			}
			if ( $filters['placement_key'] && $slot['placement_key'] !== $filters['placement_key'] ) {
				return false;
			}
			if ( $filters['page_type'] && ! in_array( 'all', $slot['page_types'], true ) && ! in_array( $filters['page_type'], $slot['page_types'], true ) ) {
				return false;
			}
			if ( $filters['category'] && ! empty( $slot['categories'] ) && ! in_array( $filters['category'], $slot['categories'], true ) ) {
				return false;
			}
			return true;
		} ) );
	}

	public static function active( $args = array() ) {
		unset( $args['status'] );
		return array_values( array_filter( self::all( $args ), array( __CLASS__, 'is_live' ) ) );
	}

	public static function save( $input, $post_id = 0 ) {
		$existing = $post_id ? self::get( $post_id ) : array();
		$slot = HIP_Ad_Schema::normalize_slot( $input, $existing ? $existing : array() );
		$errors = HIP_Ad_Schema::validate_slot( $slot );
		if ( $errors->has_errors() ) {
			return $errors;
		}

		$duplicate = self::find_duplicate( $slot['key'], $slot['ad_unit_path'], $post_id );
		if ( $duplicate ) {
			return new WP_Error(
				'duplicate_slot',
				sprintf(
					/* translators: %s: existing slot name */
					__( 'Another slot already uses this key or ad unit path: %s', 'hip-admanager' ),
					$duplicate['name']
				),
				array( 'existing_id' => $duplicate['id'] )
			);
		}

		$postarr = array(
			'post_type'   => HIP_Ad_Slot::POST_TYPE,
			'post_title'  => $slot['name'],
			'post_status' => 'publish',
			'menu_order'  => $slot['priority'],
		);
		if ( $post_id ) {
			$postarr['ID'] = absint( $post_id );
			$saved_id = wp_update_post( wp_slash( $postarr ), true );
		} else {
			$saved_id = wp_insert_post( wp_slash( $postarr ), true );
		}
		if ( is_wp_error( $saved_id ) ) {
			return $saved_id;
		}

		$slot['id'] = (int) $saved_id;
		self::write_meta( $saved_id, $slot );
		self::bump_cache_version();
		do_action( 'hip_ad_slot_saved', $saved_id, $slot );
		return self::get( $saved_id );
	}

	public static function trash( $post_id ) {
		$post_id = absint( $post_id );
		$slot = self::get( $post_id );
		if ( ! $slot ) {
			return new WP_Error( 'slot_not_found', __( 'Ad slot not found.', 'hip-admanager' ) );
		}
		$result = wp_trash_post( $post_id );
		if ( ! $result ) {
			return new WP_Error( 'slot_delete_failed', __( 'Could not move the slot to Trash.', 'hip-admanager' ) );
		}
		self::bump_cache_version();
		return true;
	}

	public static function duplicate( $post_id ) {
		$slot = self::get( $post_id );
		if ( ! $slot ) {
			return new WP_Error( 'slot_not_found', __( 'Ad slot not found.', 'hip-admanager' ) );
		}

		$slot['id'] = 0;
		$slot['name'] .= ' ' . __( 'Copy', 'hip-admanager' );
		$slot['key'] = self::unique_key( $slot['key'] . '_copy' );
		$slot['placement_key'] = $slot['key'];
		$slot['inventory_id'] = '';
		$slot['ad_unit_path'] = rtrim( $slot['ad_unit_path'], '/' ) . '_copy';
		$slot['status'] = 'paused';
		$slot['notes'] = trim( $slot['notes'] . "\n" . __( 'Duplicated slot. Review the GAM path before activating.', 'hip-admanager' ) );
		return self::save( $slot );
	}

	public static function find_by_key( $key ) {
		$key = HIP_Ad_Schema::sanitize_key( $key );
		foreach ( self::all() as $slot ) {
			if ( $slot['key'] === $key ) {
				return $slot;
			}
		}
		return null;
	}

	public static function find_by_ad_unit_path( $path ) {
		$path = HIP_Ad_Schema::sanitize_ad_unit_path( $path );
		foreach ( self::all() as $slot ) {
			if ( $slot['ad_unit_path'] === $path ) {
				return $slot;
			}
		}
		return null;
	}

	public static function find_duplicate( $key, $path, $exclude_id = 0 ) {
		$key = HIP_Ad_Schema::sanitize_key( $key );
		$path = HIP_Ad_Schema::sanitize_ad_unit_path( $path );
		foreach ( self::all() as $slot ) {
			if ( $exclude_id && (int) $slot['id'] === (int) $exclude_id ) {
				continue;
			}
			if ( ( $key && $slot['key'] === $key ) || ( $path && $slot['ad_unit_path'] === $path ) ) {
				return $slot;
			}
		}
		return null;
	}

	public static function unique_key( $candidate, $exclude_id = 0 ) {
		$base = HIP_Ad_Schema::sanitize_key( $candidate );
		if ( ! $base ) {
			$base = 'ad_slot';
		}
		$key = $base;
		$counter = 2;
		while ( true ) {
			$found = self::find_by_key( $key );
			if ( ! $found || ( $exclude_id && (int) $found['id'] === (int) $exclude_id ) ) {
				return $key;
			}
			$key = $base . '_' . $counter;
			$counter++;
		}
	}

	public static function diagnostics() {
		$slots = self::all();
		$issues = array();
		$keys = array();
		$paths = array();

		foreach ( $slots as $slot ) {
			$validation = HIP_Ad_Schema::validate_slot( $slot );
			foreach ( $validation->get_error_messages() as $message ) {
				$issues[] = array( 'level' => 'error', 'slot_id' => $slot['id'], 'slot' => $slot['name'], 'message' => $message );
			}
			if ( isset( $keys[ $slot['key'] ] ) ) {
				$issues[] = array( 'level' => 'error', 'slot_id' => $slot['id'], 'slot' => $slot['name'], 'message' => __( 'Duplicate slot key.', 'hip-admanager' ) );
			}
			$keys[ $slot['key'] ] = true;

			if ( $slot['ad_unit_path'] && isset( $paths[ $slot['ad_unit_path'] ] ) ) {
				$issues[] = array( 'level' => 'error', 'slot_id' => $slot['id'], 'slot' => $slot['name'], 'message' => __( 'Duplicate GAM ad unit path.', 'hip-admanager' ) );
			}
			if ( $slot['ad_unit_path'] ) {
				$paths[ $slot['ad_unit_path'] ] = true;
			}

			if ( 'scheduled' === $slot['status'] && ! $slot['schedule']['start'] && ! $slot['schedule']['end'] ) {
				$issues[] = array( 'level' => 'warning', 'slot_id' => $slot['id'], 'slot' => $slot['name'], 'message' => __( 'Scheduled slot has no start or end date.', 'hip-admanager' ) );
			}
			if ( $slot['refresh']['enabled'] && ! $slot['refresh']['require_visible'] ) {
				$issues[] = array( 'level' => 'warning', 'slot_id' => $slot['id'], 'slot' => $slot['name'], 'message' => __( 'Refresh is enabled without a visibility requirement.', 'hip-admanager' ) );
			}
		}
		return $issues;
	}

	public static function stats() {
		$slots = self::all();
		$stats = array(
			'total'     => count( $slots ),
			'active'    => 0,
			'paused'    => 0,
			'scheduled' => 0,
			'groups'    => array(),
		);
		foreach ( $slots as $slot ) {
			if ( isset( $stats[ $slot['status'] ] ) ) {
				$stats[ $slot['status'] ]++;
			}
			if ( ! isset( $stats['groups'][ $slot['placement_group'] ] ) ) {
				$stats['groups'][ $slot['placement_group'] ] = 0;
			}
			$stats['groups'][ $slot['placement_group'] ]++;
		}
		return $stats;
	}

	public static function from_post( $post ) {
		if ( ! $post instanceof WP_Post ) {
			$post = get_post( $post );
		}
		if ( ! $post ) {
			return null;
		}

		$legacy_placement = get_post_meta( $post->ID, 'gam_placement', true );
		$display_rules = self::decode_meta( get_post_meta( $post->ID, 'gam_display_rules', true ), array() );
		$page_types = get_post_meta( $post->ID, '_hip_ad_page_types', true );
		if ( ! $page_types && isset( $display_rules['page_types'] ) ) {
			$page_types = $display_rules['page_types'];
		}
		$categories = get_post_meta( $post->ID, '_hip_ad_categories', true );
		if ( ! $categories && isset( $display_rules['categories'] ) ) {
			$categories = $display_rules['categories'];
		}

		$sizes = self::decode_meta( get_post_meta( $post->ID, 'gam_sizes', true ), array() );
		$min_height = self::decode_meta( get_post_meta( $post->ID, '_hip_ad_min_height', true ), array() );
		if ( empty( $min_height ) ) {
			$computed = HIP_Ad_Schema::compute_min_height( $sizes );
			$min_height = array( 'desktop' => $computed, 'tablet' => $computed, 'mobile' => min( $computed, 280 ) );
		}

		$schedule = self::decode_meta( get_post_meta( $post->ID, '_hip_ad_schedule', true ), array() );
		if ( empty( $schedule ) && isset( $display_rules['schedule'] ) ) {
			$schedule = array(
				'start' => isset( $display_rules['schedule']['start_date'] ) ? $display_rules['schedule']['start_date'] : '',
				'end'   => isset( $display_rules['schedule']['end_date'] ) ? $display_rules['schedule']['end_date'] : '',
			);
		}

		$refresh = array(
			'enabled'           => get_post_meta( $post->ID, '_hip_ad_refresh_enabled', true ),
			'trigger'           => get_post_meta( $post->ID, '_hip_ad_refresh_trigger', true ) ?: 'time',
			'interval'          => get_post_meta( $post->ID, '_hip_ad_refresh_interval', true ) ?: 30,
			'max_refreshes'     => get_post_meta( $post->ID, '_hip_ad_max_refreshes', true ) ?: 0,
			'require_visible'   => '' !== get_post_meta( $post->ID, '_hip_ad_refresh_visible', true ) ? get_post_meta( $post->ID, '_hip_ad_refresh_visible', true ) : true,
			'pause_when_hidden' => '' !== get_post_meta( $post->ID, '_hip_ad_refresh_pause_hidden', true ) ? get_post_meta( $post->ID, '_hip_ad_refresh_pause_hidden', true ) : true,
		);

		$legacy_id = get_post_meta( $post->ID, 'gam_slot_id', true );
		$key = get_post_meta( $post->ID, '_hip_ad_key', true );
		if ( ! $key ) {
			$key = HIP_Ad_Schema::sanitize_key( get_post_meta( $post->ID, '_hip_ad_placement_key', true ) ?: $post->post_title );
		}

		$slot = array(
			'id'              => $post->ID,
			'name'            => $post->post_title,
			'key'             => $key,
			'inventory_id'    => get_post_meta( $post->ID, '_hip_ad_inventory_id', true ) ?: $legacy_id,
			'legacy_slot_id'  => $legacy_id,
			'ad_unit_path'    => get_post_meta( $post->ID, 'gam_ad_unit_path', true ),
			'placement_group' => get_post_meta( $post->ID, '_hip_ad_placement_group', true ) ?: HIP_Ad_Schema::infer_group( $legacy_placement ),
			'placement_key'   => get_post_meta( $post->ID, '_hip_ad_placement_key', true ) ?: ( $legacy_placement ? HIP_Ad_Schema::sanitize_key( $legacy_placement ) : $key ),
			'device'          => get_post_meta( $post->ID, 'gam_device', true ) ?: 'all',
			'status'          => get_post_meta( $post->ID, 'gam_status', true ) ?: 'active',
			'priority'        => get_post_meta( $post->ID, 'gam_priority', true ) ?: ( $post->menu_order ?: 10 ),
			'sizes'           => $sizes,
			'size_mappings'   => self::decode_meta( get_post_meta( $post->ID, 'gam_size_mappings', true ), array() ),
			'targeting'       => self::decode_meta( get_post_meta( $post->ID, 'gam_targeting', true ), array() ),
			'page_types'      => $page_types ?: array( 'all' ),
			'categories'      => $categories ?: array(),
			'lazy_load'       => '' !== get_post_meta( $post->ID, 'gam_lazy_load', true ) ? get_post_meta( $post->ID, 'gam_lazy_load', true ) : true,
			'collapse_empty'  => '' !== get_post_meta( $post->ID, '_hip_ad_collapse_empty', true ) ? get_post_meta( $post->ID, '_hip_ad_collapse_empty', true ) : true,
			'min_height'      => $min_height,
			'schedule'        => $schedule,
			'refresh'         => $refresh,
			'notes'           => get_post_meta( $post->ID, '_hip_ad_notes', true ),
		);

		$slot = HIP_Ad_Schema::normalize_slot( $slot );
		return apply_filters( 'hip_ad_slot_data', $slot, $post->ID );
	}

	public static function is_live( $slot ) {
		if ( ! in_array( $slot['status'], array( 'active', 'scheduled' ), true ) ) {
			return false;
		}

		$now = time();
		$start = $slot['schedule']['start'] ? strtotime( $slot['schedule']['start'] ) : false;
		$end = $slot['schedule']['end'] ? strtotime( $slot['schedule']['end'] ) : false;

		if ( $start && $now < $start ) {
			return false;
		}
		if ( $end && $now > $end ) {
			return false;
		}
		return true;
	}

	public static function api_slot( $slot ) {
		return array(
			'id'                  => (int) $slot['id'],
			'key'                 => $slot['key'],
			'name'                => $slot['name'],
			'slotId'              => $slot['key'],
			'legacySlotId'        => $slot['legacy_slot_id'],
			'inventoryId'         => $slot['inventory_id'],
			'adUnitPath'          => $slot['ad_unit_path'],
			'placement'           => $slot['placement_key'],
			'placementKey'        => $slot['placement_key'],
			'placementGroup'      => $slot['placement_group'],
			'device'              => $slot['device'],
			'priority'            => (int) $slot['priority'],
			'sizes'               => $slot['sizes'],
			'sizeMappings'        => $slot['size_mappings'],
			'targeting'           => $slot['targeting'],
			'pageTypes'           => $slot['page_types'],
			'categories'          => $slot['categories'],
			'lazyLoad'            => (bool) $slot['lazy_load'],
			'collapseEmpty'       => (bool) $slot['collapse_empty'],
			'minHeight'           => max( $slot['min_height'] ),
			'responsiveMinHeight' => $slot['min_height'],
			'schedule'            => $slot['schedule'],
			'refresh'             => $slot['refresh'],
		);
	}

	public static function bump_cache_version() {
		$version = (int) get_option( self::CACHE_VERSION_OPTION, 1 );
		update_option( self::CACHE_VERSION_OPTION, $version + 1, false );
		return $version + 1;
	}

	public static function cache_version() {
		return (int) get_option( self::CACHE_VERSION_OPTION, 1 );
	}

	public static function cache_key( $suffix ) {
		return 'hip_ad_v2_' . self::cache_version() . '_' . md5( (string) $suffix );
	}

	private static function write_meta( $post_id, $slot ) {
		$meta = array(
			'_hip_ad_schema_version'       => HIP_Ad_Schema::VERSION,
			'_hip_ad_key'                  => $slot['key'],
			'_hip_ad_inventory_id'         => $slot['inventory_id'],
			'_hip_ad_placement_group'      => $slot['placement_group'],
			'_hip_ad_placement_key'        => $slot['placement_key'],
			'_hip_ad_page_types'           => $slot['page_types'],
			'_hip_ad_categories'           => $slot['categories'],
			'_hip_ad_min_height'           => wp_json_encode( $slot['min_height'] ),
			'_hip_ad_schedule'             => wp_json_encode( $slot['schedule'] ),
			'_hip_ad_collapse_empty'       => $slot['collapse_empty'] ? '1' : '0',
			'_hip_ad_notes'                => $slot['notes'],
			'_hip_ad_refresh_enabled'      => $slot['refresh']['enabled'] ? '1' : '0',
			'_hip_ad_refresh_trigger'      => $slot['refresh']['trigger'],
			'_hip_ad_refresh_interval'     => $slot['refresh']['interval'],
			'_hip_ad_max_refreshes'        => $slot['refresh']['max_refreshes'],
			'_hip_ad_refresh_visible'      => $slot['refresh']['require_visible'] ? '1' : '0',
			'_hip_ad_refresh_pause_hidden' => $slot['refresh']['pause_when_hidden'] ? '1' : '0',
			'gam_slot_id'                  => $slot['inventory_id'],
			'gam_ad_unit_path'             => $slot['ad_unit_path'],
			'gam_sizes'                    => wp_json_encode( $slot['sizes'] ),
			'gam_size_mappings'            => wp_json_encode( $slot['size_mappings'] ),
			'gam_targeting'                => wp_json_encode( $slot['targeting'] ),
			'gam_placement'                => $slot['placement_key'],
			'gam_device'                   => $slot['device'],
			'gam_lazy_load'                => $slot['lazy_load'] ? '1' : '0',
			'gam_status'                   => $slot['status'],
			'gam_priority'                 => $slot['priority'],
			'gam_display_rules'            => wp_json_encode( array(
				'page_types' => $slot['page_types'],
				'categories' => $slot['categories'],
				'schedule'   => array(
					'start_date' => $slot['schedule']['start'],
					'end_date'   => $slot['schedule']['end'],
				),
			) ),
		);

		foreach ( $meta as $key => $value ) {
			update_post_meta( $post_id, $key, $value );
		}
	}

	private static function device_matches( $configured, $requested ) {
		return 'all' === $configured || 'all' === $requested || $configured === $requested;
	}

	private static function decode_meta( $value, $fallback ) {
		if ( is_array( $value ) ) {
			return $value;
		}
		if ( ! is_string( $value ) || '' === trim( $value ) ) {
			return $fallback;
		}
		$decoded = json_decode( $value, true );
		return JSON_ERROR_NONE === json_last_error() ? $decoded : $fallback;
	}
}
