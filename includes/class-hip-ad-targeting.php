<?php
/**
 * Targeting rules
 *
 * @package HIP_Ad_Manager
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * HIP Ad Targeting class
 */
class HIP_Ad_Targeting {

	/**
	 * Constructor
	 */
	public function __construct() {
		// Add any targeting-specific hooks here
	}

	/**
	 * Apply targeting rules to slots
	 *
	 * @param array $slots
	 * @param array $context
	 * @return array
	 */
	public function apply_rules( $slots, $context = array() ) {
		$filtered_slots = array();

		foreach ( $slots as $slot ) {
			if ( $this->should_display_slot( $slot, $context ) ) {
				$filtered_slots[] = $slot;
			}
		}

		return $filtered_slots;
	}

	/**
	 * Check if slot should be displayed based on rules
	 *
	 * @param array $slot
	 * @param array $context
	 * @return bool
	 */
	private function should_display_slot( $slot, $context ) {
		// Get display rules
		$display_rules = get_post_meta( $slot->ID, 'gam_display_rules', true );
		
		if ( empty( $display_rules ) ) {
			return true;
		}

		$rules = json_decode( $display_rules, true );
		
		if ( ! is_array( $rules ) ) {
			return true;
		}

		// Check page types
		if ( isset( $rules['page_types'] ) && ! empty( $rules['page_types'] ) && isset( $context['page_type'] ) ) {
			if ( ! in_array( $context['page_type'], $rules['page_types'], true ) ) {
				return false;
			}
		}

		// Check categories
		if ( isset( $rules['categories'] ) && ! empty( $rules['categories'] ) && isset( $context['categories'] ) ) {
			$has_matching_category = false;
			foreach ( $context['categories'] as $cat ) {
				if ( in_array( $cat, $rules['categories'], true ) ) {
					$has_matching_category = true;
					break;
				}
			}
			if ( ! $has_matching_category ) {
				return false;
			}
		}

		// Check schedule
		if ( isset( $rules['schedule'] ) && ! empty( $rules['schedule'] ) ) {
			if ( ! $this->check_schedule( $rules['schedule'] ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Check if current time is within schedule
	 *
	 * @param array $schedule
	 * @return bool
	 */
	private function check_schedule( $schedule ) {
		$current_time = current_time( 'timestamp' );

		if ( isset( $schedule['start_date'] ) && ! empty( $schedule['start_date'] ) ) {
			$start_time = strtotime( $schedule['start_date'] );
			if ( $current_time < $start_time ) {
				return false;
			}
		}

		if ( isset( $schedule['end_date'] ) && ! empty( $schedule['end_date'] ) ) {
			$end_time = strtotime( $schedule['end_date'] );
			if ( $current_time > $end_time ) {
				return false;
			}
		}

		return true;
	}
}
