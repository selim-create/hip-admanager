<?php
/**
 * Sizes metabox
 *
 * @package HIP_Ad_Manager
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<table class="form-table">
	<tr>
		<th scope="row">
			<label for="gam_sizes"><?php esc_html_e( 'Ad Sizes', 'hip-admanager' ); ?></label>
		</th>
		<td>
			<textarea id="gam_sizes" name="gam_sizes" rows="5" class="large-text code"><?php echo esc_textarea( $gam_sizes ); ?></textarea>
			<p class="description"><?php esc_html_e( 'JSON array of sizes (e.g., [[300, 250], [336, 280]])', 'hip-admanager' ); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for="gam_size_mappings"><?php esc_html_e( 'Size Mappings', 'hip-admanager' ); ?></label>
		</th>
		<td>
			<textarea id="gam_size_mappings" name="gam_size_mappings" rows="10" class="large-text code"><?php echo esc_textarea( $gam_size_mappings ); ?></textarea>
			<p class="description">
				<?php esc_html_e( 'JSON array of responsive size mappings', 'hip-admanager' ); ?><br />
				<?php esc_html_e( 'Example: [{"viewport": [1024, 0], "sizes": [[300, 250], [336, 280]]}, {"viewport": [0, 0], "sizes": [[300, 250]]}]', 'hip-admanager' ); ?>
			</p>
		</td>
	</tr>
</table>
