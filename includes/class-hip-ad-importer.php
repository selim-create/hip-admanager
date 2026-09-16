<?php
/**
 * Google Ad Manager CSV import service.
 *
 * @package HIP_Ad_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HIP_Ad_Importer {

	public function __construct() {
		// Admin actions are intentionally owned by HIP_Ad_Admin in v2.
	}

	public function parse_csv( $file_path ) {
		if ( ! is_readable( $file_path ) ) {
			return new WP_Error( 'file_not_readable', __( 'The CSV file could not be read.', 'hip-admanager' ) );
		}

		$handle = fopen( $file_path, 'r' );
		if ( false === $handle ) {
			return new WP_Error( 'file_open_failed', __( 'The CSV file could not be opened.', 'hip-admanager' ) );
		}

		$headers = array();
		$rows = array();
		$line = 0;

		while ( ( $data = fgetcsv( $handle, 100000, ',' ) ) !== false ) {
			$line++;

			if ( isset( $data[0] ) ) {
				$data[0] = preg_replace( '/^\xEF\xBB\xBF/', '', $data[0] );
			}

			$non_empty = array_filter(
				$data,
				function( $value ) {
					return '' !== trim( (string) $value );
				}
			);
			if ( empty( $non_empty ) ) {
				continue;
			}

			if ( empty( $headers ) ) {
				$candidate = array_map( array( $this, 'normalize_header' ), $data );
				if ( ! $this->is_header_row( $candidate ) ) {
					continue;
				}
				$headers = $candidate;
				continue;
			}

			if ( 1 === count( $data ) && isset( $data[0] ) && '#' === substr( ltrim( (string) $data[0] ), 0, 1 ) ) {
				continue;
			}

			if ( count( $data ) < count( $headers ) ) {
				$data = array_pad( $data, count( $headers ), '' );
			}
			if ( count( $data ) > count( $headers ) ) {
				$data = array_slice( $data, 0, count( $headers ) );
			}

			$row = array_combine( $headers, $data );
			if ( $row ) {
				$row['_line'] = $line;
				$rows[] = $row;
			}
		}
		fclose( $handle );

		if ( empty( $headers ) ) {
			return new WP_Error( 'missing_header', __( 'No valid Google Ad Manager CSV header row was found.', 'hip-admanager' ) );
		}
		if ( empty( $rows ) ) {
			return new WP_Error( 'empty_csv', __( 'The CSV contains no ad-unit rows.', 'hip-admanager' ) );
		}

		return $rows;
	}

	public function preview( $rows, $network_code = '' ) {
		$network_code = preg_replace( '/\D+/', '', (string) $network_code );
		$result = array(
			'items'  => array(),
			'counts' => array( 'create' => 0, 'update' => 0, 'invalid' => 0 ),
		);
		$seen_paths = array();
		$seen_existing = array();
		$reserved_keys = array();

		foreach ( HIP_Ad_Repository::all() as $existing_slot ) {
			$reserved_keys[ $existing_slot['key'] ] = true;
		}

		foreach ( (array) $rows as $row ) {
			$parsed = $this->row_to_slot( $row, $network_code );
			$item = array(
				'line'        => isset( $row['_line'] ) ? absint( $row['_line'] ) : 0,
				'slot'        => isset( $parsed['slot'] ) ? $parsed['slot'] : array(),
				'action'      => 'invalid',
				'existing_id' => 0,
				'errors'      => isset( $parsed['errors'] ) ? $parsed['errors'] : array(),
				'warnings'    => isset( $parsed['warnings'] ) ? $parsed['warnings'] : array(),
			);

			if ( empty( $item['errors'] ) && ! empty( $item['slot'] ) ) {
				$path = $item['slot']['ad_unit_path'];
				if ( isset( $seen_paths[ $path ] ) ) {
					$item['errors'][] = sprintf(
						/* translators: %d: CSV line number */
						__( 'Duplicate GAM ad-unit path in this CSV; first seen on line %d.', 'hip-admanager' ),
						$seen_paths[ $path ]
					);
				} else {
					$seen_paths[ $path ] = $item['line'];
				}
			}

			if ( empty( $item['errors'] ) && ! empty( $item['slot'] ) ) {
				$existing = HIP_Ad_Repository::find_by_ad_unit_path( $item['slot']['ad_unit_path'] );
				if ( ! $existing ) {
					$existing = HIP_Ad_Repository::find_by_key( $item['slot']['key'] );
				}

				if ( $existing ) {
					if ( isset( $seen_existing[ $existing['id'] ] ) ) {
						$item['errors'][] = sprintf(
							/* translators: %d: CSV line number */
							__( 'Multiple CSV rows resolve to the same existing slot; first seen on line %d.', 'hip-admanager' ),
							$seen_existing[ $existing['id'] ]
						);
					} else {
						$seen_existing[ $existing['id'] ] = $item['line'];
						$item['action'] = 'update';
						$item['existing_id'] = (int) $existing['id'];
						$item['slot'] = $this->merge_import( $existing, $item['slot'] );
						$reserved_keys[ $item['slot']['key'] ] = true;
					}
				} else {
					$item['slot']['key'] = $this->unique_preview_key( $item['slot']['key'], $reserved_keys );
					$item['slot']['placement_key'] = $item['slot']['key'];
					$reserved_keys[ $item['slot']['key'] ] = true;
					$item['action'] = 'create';
				}
			}

			if ( ! empty( $item['errors'] ) ) {
				$item['action'] = 'invalid';
			}

			$result['counts'][ $item['action'] ]++;
			$result['items'][] = $item;
		}

		return $result;
	}

	public function commit( $preview, $update_existing = true ) {
		$result = array(
			'created' => array(),
			'updated' => array(),
			'skipped' => array(),
			'failed'  => array(),
		);

		foreach ( isset( $preview['items'] ) ? $preview['items'] : array() as $item ) {
			if ( 'invalid' === $item['action'] ) {
				$result['skipped'][] = array( 'line' => $item['line'], 'reason' => implode( ' ', $item['errors'] ) );
				continue;
			}
			if ( 'update' === $item['action'] && ! $update_existing ) {
				$result['skipped'][] = array( 'line' => $item['line'], 'reason' => __( 'Existing slot left unchanged.', 'hip-admanager' ) );
				continue;
			}

			$post_id = 'update' === $item['action'] ? absint( $item['existing_id'] ) : 0;
			$saved = HIP_Ad_Repository::save( $item['slot'], $post_id );
			if ( is_wp_error( $saved ) ) {
				$result['failed'][] = array( 'line' => $item['line'], 'reason' => $saved->get_error_message() );
				continue;
			}

			$bucket = $post_id ? 'updated' : 'created';
			$result[ $bucket ][] = array( 'id' => $saved['id'], 'name' => $saved['name'], 'key' => $saved['key'] );
		}

		HIP_Ad_Repository::bump_cache_version();
		return $result;
	}

	private function row_to_slot( $row, $network_code ) {
		$name = $this->pick( $row, array( 'name', 'ad_unit_name', 'adunit_name' ) );
		$code = $this->pick( $row, array( 'code', 'ad_unit_code', 'adunit_code' ) );
		$inventory_id = $this->pick( $row, array( 'id', 'ad_unit_id', 'adunit_id' ) );
		$sizes_raw = $this->pick( $row, array( 'sizes', 'size' ) );
		$errors = array();
		$warnings = array();

		if ( ! $name && ! $code ) {
			$errors[] = __( 'Missing ad-unit name/code.', 'hip-admanager' );
		}
		if ( ! $code ) {
			$errors[] = __( 'Missing GAM ad-unit code.', 'hip-admanager' );
		}

		$descriptor = strtolower( $name . ' ' . $code . ' ' . $sizes_raw );
		$is_out_of_page = false !== strpos( $descriptor, 'out-of-page' );
		$is_video = false !== strpos( $descriptor, 'video' ) || preg_match( '/\d{1,4}\s*[xX]\s*\d{1,4}\s*v(?:\b|;|$)/i', $sizes_raw );

		if ( $is_out_of_page ) {
			$errors[] = __( 'Out-of-page inventory is not supported by the current display-slot runtime and was skipped.', 'hip-admanager' );
		} elseif ( $is_video ) {
			$errors[] = __( 'Video inventory is not supported by the current display-slot runtime and was skipped.', 'hip-admanager' );
		}

		$sizes = ( $is_out_of_page || $is_video ) ? array() : $this->parse_sizes( $sizes_raw );
		if ( ! $is_out_of_page && ! $is_video && empty( $sizes ) ) {
			$errors[] = __( 'No valid display sizes were found.', 'hip-admanager' );
		}

		$path = $this->ad_unit_path( $code, $network_code );
		if ( ! $path ) {
			$errors[] = __( 'A network code is required to build the ad-unit path.', 'hip-admanager' );
		}

		$label = $name ?: basename( str_replace( '\\', '/', $code ) );
		$group = $this->infer_group( $label . ' ' . $code );
		$device = $this->infer_device( $label . ' ' . $code );
		$key = $this->infer_key( $label, $group, $device );
		$mapping = $this->mapping_for( $group, $device, $sizes );

		if ( ! $inventory_id ) {
			$warnings[] = __( 'GAM inventory ID is empty; this is allowed but importing it is recommended.', 'hip-admanager' );
		}

		$slot = HIP_Ad_Schema::normalize_slot(
			array(
				'name'            => $label,
				'key'             => $key,
				'inventory_id'    => $inventory_id,
				'ad_unit_path'    => $path,
				'placement_group' => $group,
				'placement_key'   => $key,
				'device'          => $device,
				'sizes'           => $sizes,
				'size_mappings'   => $mapping,
				'status'          => 'paused',
				'priority'        => 10,
				'lazy_load'       => true,
				'collapse_empty'  => true,
				'min_height'      => array(
					'desktop' => HIP_Ad_Schema::compute_min_height( $sizes ),
					'tablet'  => HIP_Ad_Schema::compute_min_height( $sizes ),
					'mobile'  => min( 280, HIP_Ad_Schema::compute_min_height( $sizes ) ),
				),
			)
		);

		return array( 'slot' => $slot, 'errors' => $errors, 'warnings' => $warnings );
	}

	private function merge_import( $existing, $incoming ) {
		$existing['name'] = $incoming['name'] ?: $existing['name'];
		$existing['inventory_id'] = $incoming['inventory_id'] ?: $existing['inventory_id'];
		$existing['ad_unit_path'] = $incoming['ad_unit_path'];
		$existing['sizes'] = $incoming['sizes'];
		if ( empty( $existing['size_mappings'] ) ) {
			$existing['size_mappings'] = $incoming['size_mappings'];
		}
		return HIP_Ad_Schema::normalize_slot( $existing );
	}

	private function unique_preview_key( $candidate, $reserved ) {
		$base = HIP_Ad_Schema::sanitize_key( $candidate );
		if ( ! $base ) {
			$base = 'ad_slot';
		}
		$key = $base;
		$counter = 2;
		while ( isset( $reserved[ $key ] ) ) {
			$key = $base . '_' . $counter;
			$counter++;
		}
		return $key;
	}

	private function normalize_header( $header ) {
		$header = preg_replace( '/^\xEF\xBB\xBF/', '', (string) $header );
		$header = ltrim( trim( $header ), '#' );
		$header = strtolower( remove_accents( $header ) );
		$header = preg_replace( '/[^a-z0-9]+/', '_', $header );
		return trim( $header, '_' );
	}

	private function is_header_row( $headers ) {
		$headers = array_values( array_filter( (array) $headers, 'strlen' ) );
		$has_code = in_array( 'code', $headers, true ) || in_array( 'ad_unit_code', $headers, true ) || in_array( 'adunit_code', $headers, true );
		$has_name = in_array( 'name', $headers, true ) || in_array( 'ad_unit_name', $headers, true ) || in_array( 'adunit_name', $headers, true );
		$has_sizes = in_array( 'sizes', $headers, true ) || in_array( 'size', $headers, true );
		return $has_code && $has_name && $has_sizes;
	}

	private function pick( $row, $keys ) {
		foreach ( $keys as $key ) {
			if ( isset( $row[ $key ] ) && '' !== trim( (string) $row[ $key ] ) ) {
				return trim( (string) $row[ $key ] );
			}
		}
		return '';
	}

	private function parse_sizes( $value ) {
		preg_match_all( '/(\d{1,4})\s*[xX]\s*(\d{1,4})(?!\s*v)/i', (string) $value, $matches, PREG_SET_ORDER );
		$sizes = array();
		foreach ( $matches as $match ) {
			$sizes[] = array( (int) $match[1], (int) $match[2] );
		}
		return HIP_Ad_Schema::normalize_sizes( $sizes );
	}

	private function ad_unit_path( $code, $network_code ) {
		$code = trim( (string) $code );
		if ( '' === $code ) {
			return '';
		}
		if ( '/' === substr( $code, 0, 1 ) ) {
			return HIP_Ad_Schema::sanitize_ad_unit_path( $code );
		}
		if ( ! $network_code ) {
			return '';
		}
		return HIP_Ad_Schema::sanitize_ad_unit_path( '/' . $network_code . '/' . ltrim( $code, '/' ) );
	}

	private function infer_group( $text ) {
		$text = strtolower( remove_accents( (string) $text ) );
		if ( preg_match( '/in[-_ ]?banner/', $text ) ) {
			return 'content';
		}
		if ( preg_match( '/masthead|leaderboard|billboard|header|970x|728x90/', $text ) ) {
			return 'header';
		}
		if ( preg_match( '/sidebar|skyscraper|halfpage|160x600|120x600|300x600/', $text ) ) {
			return 'sidebar';
		}
		if ( preg_match( '/footer/', $text ) ) {
			return 'footer';
		}
		if ( preg_match( '/sticky|interstitial|overlay|anchor/', $text ) ) {
			return 'overlay';
		}
		return 'content';
	}

	private function infer_device( $text ) {
		$text = strtolower( remove_accents( (string) $text ) );
		if ( false !== strpos( $text, 'mobile' ) || false !== strpos( $text, 'mobil' ) ) {
			return 'mobile';
		}
		if ( false !== strpos( $text, 'tablet' ) ) {
			return 'tablet';
		}
		if ( false !== strpos( $text, 'desktop' ) || false !== strpos( $text, 'masaustu' ) ) {
			return 'desktop';
		}
		return 'all';
	}

	private function infer_key( $name, $group, $device ) {
		$key = HIP_Ad_Schema::sanitize_key( $name );
		if ( strlen( $key ) > 70 ) {
			$key = substr( $key, 0, 70 );
		}
		if ( ! $key ) {
			$key = $group . '_' . $device . '_slot';
		}
		return $key;
	}

	private function mapping_for( $group, $device, $sizes ) {
		$presets = HIP_Ad_Schema::size_presets();
		if ( 'mobile' === $device || 'overlay' === $group ) {
			$preset = isset( $presets['mobile_sticky'] ) ? $presets['mobile_sticky'] : array();
		} elseif ( 'header' === $group ) {
			$preset = $presets['leaderboard'];
		} elseif ( 'sidebar' === $group ) {
			$preset = $presets['skyscraper'];
		} elseif ( 'content' === $group ) {
			$preset = $presets['mpu'];
		} else {
			$preset = array( array( 'viewport' => array( 0, 0 ), 'sizes' => $sizes ) );
		}
		return $this->filter_mapping_sizes( $preset, $sizes );
	}

	private function filter_mapping_sizes( $mappings, $declared_sizes ) {
		$allowed = array();
		foreach ( HIP_Ad_Schema::normalize_sizes( $declared_sizes ) as $size ) {
			$allowed[ $size[0] . 'x' . $size[1] ] = true;
		}

		$result = array();
		foreach ( (array) $mappings as $mapping ) {
			$mapped = isset( $mapping['sizes'] ) ? HIP_Ad_Schema::normalize_sizes( $mapping['sizes'] ) : array();
			if ( empty( $mapped ) ) {
				$result[] = array( 'viewport' => $mapping['viewport'], 'sizes' => array() );
				continue;
			}
			$mapped = array_values(
				array_filter(
					$mapped,
					function( $size ) use ( $allowed ) {
						return isset( $allowed[ $size[0] . 'x' . $size[1] ] );
					}
				)
			);
			if ( ! empty( $mapped ) ) {
				$result[] = array( 'viewport' => $mapping['viewport'], 'sizes' => $mapped );
			}
		}

		if ( empty( $result ) && ! empty( $declared_sizes ) ) {
			$result[] = array( 'viewport' => array( 0, 0 ), 'sizes' => HIP_Ad_Schema::normalize_sizes( $declared_sizes ) );
		}

		return $result;
	}
}
