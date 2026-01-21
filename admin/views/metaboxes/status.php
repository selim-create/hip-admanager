<?php
/**
 * Status metabox
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
			<label for="gam_status"><?php esc_html_e( 'Status', 'hip-admanager' ); ?></label>
		</th>
		<td>
			<select id="gam_status" name="gam_status" style="width: 100%;">
				<option value="active" <?php selected( $gam_status, 'active' ); ?>><?php esc_html_e( 'Active', 'hip-admanager' ); ?></option>
				<option value="paused" <?php selected( $gam_status, 'paused' ); ?>><?php esc_html_e( 'Paused', 'hip-admanager' ); ?></option>
				<option value="scheduled" <?php selected( $gam_status, 'scheduled' ); ?>><?php esc_html_e( 'Scheduled', 'hip-admanager' ); ?></option>
			</select>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for="gam_priority"><?php esc_html_e( 'Priority', 'hip-admanager' ); ?></label>
		</th>
		<td>
			<input type="number" id="gam_priority" name="gam_priority" value="<?php echo esc_attr( $gam_priority ); ?>" min="1" max="100" style="width: 100%;" />
			<p class="description"><?php esc_html_e( 'Lower numbers = higher priority (1-100)', 'hip-admanager' ); ?></p>
		</td>
	</tr>
</table>
