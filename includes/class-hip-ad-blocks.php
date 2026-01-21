<?php
/**
 * Gutenberg Blocks Registration
 *
 * @package HIP_Ad_Manager
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * HIP Ad Blocks class
 */
class HIP_Ad_Blocks {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_blocks' ) );
	}

	/**
	 * Register Gutenberg blocks
	 */
	public function register_blocks() {
		// Check if build files exist, if not use source files
		$block_path = HIP_AD_MANAGER_PLUGIN_DIR . 'blocks/ad-slot';
		$build_path = $block_path . '/build';
		
		// Register the ad slot block
		if ( file_exists( $build_path . '/index.js' ) ) {
			// Use built version
			register_block_type( $block_path, array(
				'editor_script' => 'hip-ad-slot-editor',
				'editor_style'  => 'hip-ad-slot-editor-style',
				'style'         => 'hip-ad-slot-style',
			) );
			
			wp_register_script(
				'hip-ad-slot-editor',
				HIP_AD_MANAGER_PLUGIN_URL . 'blocks/ad-slot/build/index.js',
				array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n', 'wp-data', 'wp-core-data' ),
				filemtime( $build_path . '/index.js' )
			);
			
			wp_register_style(
				'hip-ad-slot-editor-style',
				HIP_AD_MANAGER_PLUGIN_URL . 'blocks/ad-slot/editor.css',
				array(),
				filemtime( $block_path . '/editor.css' )
			);
			
			wp_register_style(
				'hip-ad-slot-style',
				HIP_AD_MANAGER_PLUGIN_URL . 'blocks/ad-slot/style.css',
				array(),
				filemtime( $block_path . '/style.css' )
			);
		} else {
			// For now, just register the block type with the JSON
			// The block won't work until built, but won't cause errors
			register_block_type( $block_path );
		}
	}
}

