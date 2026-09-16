<?php
/**
 * Internal ad-slot post type.
 *
 * The post type remains the persistence layer for backwards compatibility,
 * but all management happens in the HIP Ads v2 admin screens.
 *
 * @package HIP_Ad_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HIP_Ad_Slot {

	const POST_TYPE = 'hip_ad_slot';

	public function __construct() {
		self::register_post_type();
		$this->register_meta();
		add_action( 'save_post_' . self::POST_TYPE, array( $this, 'invalidate_cache' ), 20, 3 );
		add_action( 'before_delete_post', array( $this, 'invalidate_deleted_slot' ), 10, 2 );
	}

	public static function register_post_type() {
		$labels = array(
			'name'          => __( 'Ad Slots', 'hip-admanager' ),
			'singular_name' => __( 'Ad Slot', 'hip-admanager' ),
		);
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'             => $labels,
				'public'             => false,
				'publicly_queryable' => false,
				'show_ui'            => false,
				'show_in_menu'       => false,
				'show_in_rest'       => true,
				'rest_base'          => 'ad-slots',
				'query_var'          => false,
				'rewrite'            => false,
				'has_archive'        => false,
				'supports'           => array( 'title' ),
				'capability_type'    => 'post',
				'map_meta_cap'       => true,
			)
		);
	}

	private function register_meta() {
		$meta = array(
			'_hip_ad_key'             => 'string',
			'_hip_ad_inventory_id'    => 'string',
			'_hip_ad_placement_group' => 'string',
			'_hip_ad_placement_key'   => 'string',
			'gam_ad_unit_path'        => 'string',
			'gam_status'              => 'string',
			'gam_device'              => 'string',
		);
		foreach ( $meta as $key => $type ) {
			register_post_meta(
				self::POST_TYPE,
				$key,
				array(
					'type'              => $type,
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => 'sanitize_text_field',
					'auth_callback'     => function() {
						return current_user_can( 'edit_posts' );
					},
				)
			);
		}
	}

	public function invalidate_cache( $post_id, $post, $update ) {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}
		HIP_Ad_Repository::bump_cache_version();
	}

	public function invalidate_deleted_slot( $post_id, $post ) {
		if ( $post instanceof WP_Post && self::POST_TYPE === $post->post_type ) {
			HIP_Ad_Repository::bump_cache_version();
		}
	}
}
