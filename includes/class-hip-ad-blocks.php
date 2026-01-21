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
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
	}

	/**
	 * Register Gutenberg blocks
	 */
	public function register_blocks() {
		// Register the ad slot block
		register_block_type(
			'hip-admanager/ad-slot',
			array(
				'render_callback' => array( $this, 'render_ad_slot_block' ),
				'attributes'      => array(
					'slotId'    => array(
						'type'    => 'string',
						'default' => '',
					),
					'placement' => array(
						'type'    => 'string',
						'default' => 'in-content',
					),
					'alignment' => array(
						'type'    => 'string',
						'default' => 'center',
					),
				),
			)
		);
	}

	/**
	 * Enqueue block editor assets
	 * 
	 * Note: We use inline JavaScript registration instead of a build step to:
	 * 1. Avoid requiring Node.js and npm dependencies
	 * 2. Simplify plugin installation and deployment
	 * 3. Reduce build complexity for WordPress plugin hosting
	 * 4. Maintain compatibility with standard WordPress hosting environments
	 * 
	 * This approach uses WordPress's built-in block editor APIs and is fully
	 * compatible with the Gutenberg editor. For production sites requiring
	 * advanced features or performance optimization, a build step can be added
	 * using the included package.json (run `npm install && npm run build`).
	 * 
	 * Performance consideration: The inline script is ~6KB minified and only
	 * loads in the block editor (admin), not on the frontend. This hook only
	 * fires when the block editor is active.
	 */
	public function enqueue_block_editor_assets() {
		// Enqueue inline script for now (simpler than building)
		wp_add_inline_script(
			'wp-blocks',
			$this->get_block_script(),
			'after'
		);

		// Enqueue editor styles
		wp_enqueue_style(
			'hip-ad-slot-editor-style',
			HIP_AD_MANAGER_PLUGIN_URL . 'blocks/ad-slot/editor.css',
			array(),
			HIP_AD_MANAGER_VERSION
		);
	}

	/**
	 * Enqueue frontend assets
	 */
	public function enqueue_frontend_assets() {
		// Enqueue frontend styles
		wp_enqueue_style(
			'hip-ad-slot-style',
			HIP_AD_MANAGER_PLUGIN_URL . 'blocks/ad-slot/style.css',
			array(),
			HIP_AD_MANAGER_VERSION
		);
	}

	/**
	 * Get block registration script
	 *
	 * @return string
	 */
	private function get_block_script() {
		ob_start();
		?>
(function(blocks, element, blockEditor, components, data, i18n, coreData) {
	var el = element.createElement;
	var __ = i18n.__;
	var registerBlockType = blocks.registerBlockType;
	var InspectorControls = blockEditor.InspectorControls;
	var useBlockProps = blockEditor.useBlockProps;
	var SelectControl = components.SelectControl;
	var PanelBody = components.PanelBody;
	var Notice = components.Notice;
	var useSelect = data.useSelect;

	registerBlockType('hip-admanager/ad-slot', {
		title: __('Ad Slot', 'hip-admanager'),
		icon: 'megaphone',
		category: 'widgets',
		description: __('Insert an ad slot into your content', 'hip-admanager'),
		attributes: {
			slotId: {
				type: 'string',
				default: ''
			},
			placement: {
				type: 'string',
				default: 'in-content'
			},
			alignment: {
				type: 'string',
				default: 'center'
			}
		},
		supports: {
			align: ['wide', 'full', 'center'],
			html: false
		},
		
		edit: function(props) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var slotId = attributes.slotId;
			var placement = attributes.placement;
			var alignment = attributes.alignment;

			var slots = useSelect(function(select) {
				return select('core').getEntityRecords('postType', 'hip_ad_slot', {
					per_page: -1,
					status: 'publish'
				});
			}, []);

			var slotOptions = [
				{ label: __('Select an ad slot', 'hip-admanager'), value: '' }
			];

			if (slots && slots.length > 0) {
				slots.forEach(function(slot) {
					slotOptions.push({
						label: slot.title.rendered || 'Slot #' + slot.id,
						value: slot.id.toString()
					});
				});
			}

			var selectedSlot = slots && slots.find(function(slot) {
				return slot.id.toString() === slotId;
			});

			return el(
				element.Fragment,
				{},
				el(
					InspectorControls,
					{},
					el(
						PanelBody,
						{ title: __('Ad Slot Settings', 'hip-admanager') },
						el(SelectControl, {
							label: __('Select Ad Slot', 'hip-admanager'),
							value: slotId,
							options: slotOptions,
							onChange: function(value) {
								setAttributes({ slotId: value });
							}
						}),
						el(SelectControl, {
							label: __('Placement', 'hip-admanager'),
							value: placement,
							options: [
								{ label: __('In-Content', 'hip-admanager'), value: 'in-content' },
								{ label: __('Above Content', 'hip-admanager'), value: 'above-content' },
								{ label: __('Below Content', 'hip-admanager'), value: 'below-content' }
							],
							onChange: function(value) {
								setAttributes({ placement: value });
							}
						}),
						el(SelectControl, {
							label: __('Alignment', 'hip-admanager'),
							value: alignment,
							options: [
								{ label: __('Left', 'hip-admanager'), value: 'left' },
								{ label: __('Center', 'hip-admanager'), value: 'center' },
								{ label: __('Right', 'hip-admanager'), value: 'right' }
							],
							onChange: function(value) {
								setAttributes({ alignment: value });
							}
						})
					)
				),
				el(
					'div',
					useBlockProps(),
					el(
						'div',
						{
							className: 'hip-ad-placeholder align-' + alignment,
							style: {
								padding: '20px',
								border: '2px dashed #ccc',
								backgroundColor: '#f9f9f9',
								textAlign: alignment,
								minHeight: '100px',
								display: 'flex',
								alignItems: 'center',
								justifyContent: 'center'
							}
						},
						!slotId && el('p', {}, '📢 ' + __('Please select an ad slot from the sidebar', 'hip-admanager')),
						slotId && selectedSlot && el(
							'div',
							{},
							el('p', { style: { margin: '0 0 10px 0', fontSize: '14px', fontWeight: 'bold' } },
								'📢 ' + __('Ad Slot:', 'hip-admanager') + ' ' + selectedSlot.title.rendered
							),
							el('p', { style: { margin: 0, fontSize: '12px', color: '#666' } },
								__('ID:', 'hip-admanager') + ' ' + slotId + ' | ' + __('Placement:', 'hip-admanager') + ' ' + placement
							)
						),
						slotId && !selectedSlot && !slots && el('p', {}, __('Loading...', 'hip-admanager')),
						slotId && !selectedSlot && slots && el(Notice, {
							status: 'warning',
							isDismissible: false
						}, __('Selected ad slot not found', 'hip-admanager'))
					)
				)
			);
		},
		
		save: function(props) {
			var attributes = props.attributes;
			
			return el(
				'div',
				{
					className: 'hip-ad-injection align-' + attributes.alignment,
					'data-hip-ad-slot': attributes.slotId,
					'data-hip-ad-placement': attributes.placement,
					'data-hip-ad-alignment': attributes.alignment
				}
			);
		}
	});
})(
	window.wp.blocks,
	window.wp.element,
	window.wp.blockEditor,
	window.wp.components,
	window.wp.data,
	window.wp.i18n,
	window.wp.coreData
);
		<?php
		return ob_get_clean();
	}

	/**
	 * Render ad slot block
	 *
	 * @param array $attributes Block attributes
	 * @return string
	 */
	public function render_ad_slot_block( $attributes ) {
		$slot_id   = isset( $attributes['slotId'] ) ? $attributes['slotId'] : '';
		$placement = isset( $attributes['placement'] ) ? $attributes['placement'] : 'in-content';
		$alignment = isset( $attributes['alignment'] ) ? $attributes['alignment'] : 'center';

		if ( empty( $slot_id ) ) {
			return '';
		}

		return sprintf(
			'<div class="hip-ad-injection align-%s" data-hip-ad-slot="%s" data-hip-ad-placement="%s" data-hip-ad-alignment="%s"></div>',
			esc_attr( $alignment ),
			esc_attr( $slot_id ),
			esc_attr( $placement ),
			esc_attr( $alignment )
		);
	}
}

