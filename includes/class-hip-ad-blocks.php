<?php
/**
 * Gutenberg ad-slot marker block.
 *
 * @package HIP_Ad_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HIP_Ad_Blocks {

	public function __construct() {
		if ( did_action( 'init' ) ) {
			$this->register_blocks();
		} else {
			add_action( 'init', array( $this, 'register_blocks' ) );
		}
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
	}

	public function register_blocks() {
		if ( WP_Block_Type_Registry::get_instance()->is_registered( 'hip-admanager/ad-slot' ) ) {
			return;
		}
		register_block_type( 'hip-admanager/ad-slot', array(
			'render_callback' => array( $this, 'render_ad_slot_block' ),
			'attributes'      => array(
				'slotKey'   => array( 'type' => 'string', 'default' => '' ),
				'slotId'    => array( 'type' => 'string', 'default' => '' ),
				'placement' => array( 'type' => 'string', 'default' => 'content' ),
				'alignment' => array( 'type' => 'string', 'default' => 'center' ),
			),
		) );
	}

	public function enqueue_block_editor_assets() {
		wp_register_script(
			'hip-ad-block-editor-v2',
			false,
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-api-fetch', 'wp-i18n' ),
			HIP_AD_MANAGER_VERSION,
			true
		);
		wp_enqueue_script( 'hip-ad-block-editor-v2' );
		wp_add_inline_script( 'hip-ad-block-editor-v2', $this->editor_script() );
		wp_enqueue_style( 'hip-ad-slot-editor-style', HIP_AD_MANAGER_PLUGIN_URL . 'blocks/ad-slot/editor.css', array(), HIP_AD_MANAGER_VERSION );
	}

	private function editor_script() {
		$path = '/hip-ads/v1/slots';
		return '(function(wp){\n' .
			'const el=wp.element.createElement; const __=wp.i18n.__; const {registerBlockType}=wp.blocks; const {InspectorControls,useBlockProps}=wp.blockEditor; const {PanelBody,SelectControl,Notice}=wp.components;\n' .
			'registerBlockType("hip-admanager/ad-slot",{title:__("Ad Slot","hip-admanager"),icon:"megaphone",category:"widgets",description:__("Place a HIP Ads slot marker in content.","hip-admanager"),attributes:{slotKey:{type:"string",default:""},slotId:{type:"string",default:""},placement:{type:"string",default:"content"},alignment:{type:"string",default:"center"}},supports:{html:false},edit:function(props){const a=props.attributes,set=props.setAttributes; const [slots,setSlots]=wp.element.useState([]); const [error,setError]=wp.element.useState(false); wp.element.useEffect(function(){wp.apiFetch({path:"' . esc_js( $path ) . '"}).then(function(data){setSlots(data&&data.slots?data.slots:[]);}).catch(function(){setError(true);});},[]); const options=[{label:__("Select ad slot","hip-admanager"),value:""}].concat(slots.map(function(s){return {label:s.name+" · "+s.key,value:s.key};})); const selected=slots.find(function(s){return s.key===a.slotKey;}); const blockProps=useBlockProps({className:"hip-ad-block-editor"}); return el(wp.element.Fragment,{},el(InspectorControls,{},el(PanelBody,{title:__("Ad slot","hip-admanager")},el(SelectControl,{label:__("Slot","hip-admanager"),value:a.slotKey,options:options,onChange:function(v){const found=slots.find(function(s){return s.key===v;});set({slotKey:v,slotId:found?String(found.id):"",placement:found?found.placementGroup:"content"});}}),el(SelectControl,{label:__("Alignment","hip-admanager"),value:a.alignment,options:[{label:__("Left","hip-admanager"),value:"left"},{label:__("Center","hip-admanager"),value:"center"},{label:__("Right","hip-admanager"),value:"right"}],onChange:function(v){set({alignment:v});}}))),el("div",blockProps,error?el(Notice,{status:"error",isDismissible:false},__("HIP Ads API could not be loaded.","hip-admanager")):el("div",{className:"hip-ad-placeholder"},a.slotKey?el(wp.element.Fragment,{},el("strong",{},selected?selected.name:a.slotKey),el("small",{},a.slotKey)):el("span",{},__("Choose an ad slot from block settings.","hip-admanager")))));},save:function(){return null;}});\n' .
		'})(window.wp);';
	}

	public function render_ad_slot_block( $attributes ) {
		$key = isset( $attributes['slotKey'] ) ? HIP_Ad_Schema::sanitize_key( $attributes['slotKey'] ) : '';
		$legacy_id = isset( $attributes['slotId'] ) ? absint( $attributes['slotId'] ) : 0;
		if ( ! $key && $legacy_id ) {
			$legacy = HIP_Ad_Repository::get( $legacy_id );
			$key = $legacy ? $legacy['key'] : '';
		}
		if ( ! $key ) {
			return '';
		}
		$alignment = isset( $attributes['alignment'] ) ? sanitize_key( $attributes['alignment'] ) : 'center';
		$placement = isset( $attributes['placement'] ) ? HIP_Ad_Schema::sanitize_key( $attributes['placement'] ) : 'content';
		return sprintf(
			'<div class="hip-ad-injection align-%1$s" data-hip-ad-slot-key="%2$s" data-hip-ad-slot="%2$s" data-hip-ad-placement="%3$s"></div>',
			esc_attr( $alignment ),
			esc_attr( $key ),
			esc_attr( $placement )
		);
	}
}
