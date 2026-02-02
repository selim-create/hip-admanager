<?php
/**
 * Page Types metabox
 *
 * @package HIP_Ad_Manager
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div style="padding: 10px 0;">
	<?php
	$page_types_options = array(
		'home'     => __( 'Homepage', 'hip-admanager' ),
		'post'     => __( 'Single Post', 'hip-admanager' ),
		'page'     => __( 'Single Page', 'hip-admanager' ),
		'category' => __( 'Category Archive', 'hip-admanager' ),
		'tag'      => __( 'Tag Archive', 'hip-admanager' ),
		'search'   => __( 'Search Results', 'hip-admanager' ),
		'archive'  => __( 'Other Archives', 'hip-admanager' ),
		'all'      => __( 'All Pages', 'hip-admanager' ),
	);
	
	foreach ( $page_types_options as $value => $label ) :
		$checked = in_array( $value, $hip_ad_page_types, true );
		?>
		<label style="display: block; margin-bottom: 8px;">
			<input type="checkbox" name="hip_ad_page_types[]" value="<?php echo esc_attr( $value ); ?>" <?php checked( $checked ); ?> />
			<?php echo esc_html( $label ); ?>
		</label>
	<?php endforeach; ?>
	<p class="description" style="margin-top: 10px;">
		<?php esc_html_e( 'Select the page types where this ad should be displayed', 'hip-admanager' ); ?>
	</p>
</div>
