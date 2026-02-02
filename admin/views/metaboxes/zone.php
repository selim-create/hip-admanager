<?php
/**
 * Zone metabox
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
			<label for="hip_ad_zone"><?php esc_html_e( 'Zone', 'hip-admanager' ); ?></label>
		</th>
		<td>
			<select id="hip_ad_zone" name="hip_ad_zone" style="width: 100%;">
				<option value="" <?php selected( $hip_ad_zone, '' ); ?>><?php esc_html_e( '-- Select Zone --', 'hip-admanager' ); ?></option>
				<option value="top" <?php selected( $hip_ad_zone, 'top' ); ?>><?php esc_html_e( 'Top', 'hip-admanager' ); ?></option>
				<option value="middle" <?php selected( $hip_ad_zone, 'middle' ); ?>><?php esc_html_e( 'Middle', 'hip-admanager' ); ?></option>
				<option value="bottom" <?php selected( $hip_ad_zone, 'bottom' ); ?>><?php esc_html_e( 'Bottom', 'hip-admanager' ); ?></option>
				<option value="sticky" <?php selected( $hip_ad_zone, 'sticky' ); ?>><?php esc_html_e( 'Sticky', 'hip-admanager' ); ?></option>
			</select>
			<p class="description">
				<?php esc_html_e( 'Optional: Specify the zone for additional control over ad placement', 'hip-admanager' ); ?>
			</p>
		</td>
	</tr>
</table>
