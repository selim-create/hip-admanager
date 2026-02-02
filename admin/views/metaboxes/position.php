<?php
/**
 * Position Order metabox
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
			<label for="hip_ad_position"><?php esc_html_e( 'Position', 'hip-admanager' ); ?></label>
		</th>
		<td>
			<input type="number" id="hip_ad_position" name="hip_ad_position" value="<?php echo esc_attr( $hip_ad_position ); ?>" min="1" max="100" style="width: 100px;" />
			<p class="description">
				<?php esc_html_e( 'Order position (1-100). Lower numbers appear first.', 'hip-admanager' ); ?>
			</p>
		</td>
	</tr>
</table>
