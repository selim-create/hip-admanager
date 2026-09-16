<?php
/**
 * Context matching helpers for normalized slots.
 *
 * @package HIP_Ad_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HIP_Ad_Targeting {

	public function apply_rules( $slots, $context = array() ) {
		return array_values( array_filter( (array) $slots, function( $slot ) use ( $context ) {
			if ( $slot instanceof WP_Post ) {
				$slot = HIP_Ad_Repository::from_post( $slot );
			}
			return is_array( $slot ) && $this->matches( $slot, $context );
		} ) );
	}

	public function matches( $slot, $context = array() ) {
		$slot = HIP_Ad_Schema::normalize_slot( $slot );
		if ( ! HIP_Ad_Repository::is_live( $slot ) ) {
			return false;
		}

		if ( ! empty( $context['device'] ) && 'all' !== $slot['device'] && $slot['device'] !== sanitize_key( $context['device'] ) ) {
			return false;
		}
		if ( ! empty( $context['page_type'] ) && ! in_array( 'all', $slot['page_types'], true ) && ! in_array( sanitize_key( $context['page_type'] ), $slot['page_types'], true ) ) {
			return false;
		}
		if ( ! empty( $slot['categories'] ) && ! empty( $context['categories'] ) ) {
			$categories = array_map( 'sanitize_title', (array) $context['categories'] );
			if ( ! array_intersect( $slot['categories'], $categories ) ) {
				return false;
			}
		}
		return true;
	}
}
