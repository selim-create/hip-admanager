<?php
/**
 * GAM Info metabox
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
			<label for="gam_slot_id"><?php esc_html_e( 'Slot ID', 'hip-admanager' ); ?></label>
		</th>
		<td>
			<input type="text" id="gam_slot_id" name="gam_slot_id" value="<?php echo esc_attr( $gam_slot_id ); ?>" class="regular-text" />
			<p class="description"><?php esc_html_e( 'Google Ad Manager Slot ID', 'hip-admanager' ); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for="gam_ad_unit_path"><?php esc_html_e( 'Ad Unit Path', 'hip-admanager' ); ?></label>
		</th>
		<td>
			<input type="text" id="gam_ad_unit_path" name="gam_ad_unit_path" value="<?php echo esc_attr( $gam_ad_unit_path ); ?>" class="large-text" />
			<p class="description"><?php esc_html_e( 'Full ad unit path (e.g., /273585429/KidsGourmet.com.tr/kidsgourmet_300x250_mediumrectangle)', 'hip-admanager' ); ?></p>
		</td>
	</tr>
</table>
