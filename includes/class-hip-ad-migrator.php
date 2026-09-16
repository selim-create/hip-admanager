<?php
/**
 * Non-destructive data migrations.
 *
 * @package HIP_Ad_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HIP_Ad_Migrator {

	const DB_VERSION_OPTION = 'hip_ad_db_version';
	const DB_VERSION = '2.0.0';

	public static function activate() {
		HIP_Ad_Slot::register_post_type();
		self::maybe_migrate( true );
		flush_rewrite_rules();
	}

	public static function maybe_migrate( $force = false ) {
		$current = (string) get_option( self::DB_VERSION_OPTION, '0' );
		if ( ! $force && version_compare( $current, self::DB_VERSION, '>=' ) ) {
			return;
		}

		self::migrate_settings();
		self::migrate_slots();
		update_option( self::DB_VERSION_OPTION, self::DB_VERSION, false );
		HIP_Ad_Repository::bump_cache_version();
	}

	private static function migrate_settings() {
		$legacy = get_option( HIP_Ad_Settings::OPTION_NAME, array() );
		$legacy = is_array( $legacy ) ? $legacy : array();
		if ( empty( $legacy['property_code'] ) && ! empty( $legacy['site_name'] ) ) {
			$legacy['property_code'] = $legacy['site_name'];
		}
		if ( ! isset( $legacy['collapse_empty'] ) ) {
			$legacy['collapse_empty'] = 1;
		}
		if ( ! isset( $legacy['lazy_fetch_margin'] ) ) {
			$legacy['lazy_fetch_margin'] = 500;
		}
		if ( ! isset( $legacy['lazy_render_margin'] ) ) {
			$legacy['lazy_render_margin'] = 200;
		}
		if ( ! isset( $legacy['lazy_mobile_scaling'] ) ) {
			$legacy['lazy_mobile_scaling'] = 2.0;
		}
		$legacy['global_targeting'] = isset( $legacy['global_targeting'] ) ? $legacy['global_targeting'] : array();
		update_option( HIP_Ad_Settings::OPTION_NAME, HIP_Ad_Schema::normalize_settings( $legacy ), false );
	}

	private static function migrate_slots() {
		$posts = get_posts( array(
			'post_type'      => HIP_Ad_Slot::POST_TYPE,
			'post_status'    => array( 'publish', 'draft', 'private' ),
			'posts_per_page' => -1,
			'orderby'        => 'ID',
			'order'          => 'ASC',
		) );

		$seen_keys = array();
		foreach ( $posts as $post ) {
			$slot = HIP_Ad_Repository::from_post( $post );
			if ( ! $slot ) {
				continue;
			}
			$key = $slot['key'];
			if ( ! $key ) {
				$key = 'ad_slot_' . $post->ID;
			}
			$base = $key;
			$counter = 2;
			while ( isset( $seen_keys[ $key ] ) ) {
				$key = $base . '_' . $counter;
				$counter++;
			}
			$seen_keys[ $key ] = true;

			update_post_meta( $post->ID, '_hip_ad_schema_version', HIP_Ad_Schema::VERSION );
			update_post_meta( $post->ID, '_hip_ad_key', $key );
			if ( ! get_post_meta( $post->ID, '_hip_ad_placement_group', true ) ) {
				update_post_meta( $post->ID, '_hip_ad_placement_group', $slot['placement_group'] );
			}
			if ( ! get_post_meta( $post->ID, '_hip_ad_placement_key', true ) ) {
				update_post_meta( $post->ID, '_hip_ad_placement_key', $slot['placement_key'] ?: $key );
			}
			if ( ! get_post_meta( $post->ID, '_hip_ad_inventory_id', true ) && $slot['inventory_id'] ) {
				update_post_meta( $post->ID, '_hip_ad_inventory_id', $slot['inventory_id'] );
			}
			if ( ! get_post_meta( $post->ID, '_hip_ad_min_height', true ) ) {
				update_post_meta( $post->ID, '_hip_ad_min_height', wp_json_encode( $slot['min_height'] ) );
			}
			if ( ! get_post_meta( $post->ID, '_hip_ad_page_types', true ) ) {
				update_post_meta( $post->ID, '_hip_ad_page_types', $slot['page_types'] );
			}
			if ( ! get_post_meta( $post->ID, '_hip_ad_categories', true ) && $slot['categories'] ) {
				update_post_meta( $post->ID, '_hip_ad_categories', $slot['categories'] );
			}
		}
	}
}
