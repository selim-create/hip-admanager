<?php
/**
 * Targeting metabox
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
			<label for="gam_targeting"><?php esc_html_e( 'Targeting Key-Value Pairs', 'hip-admanager' ); ?></label>
		</th>
		<td>
			<textarea id="gam_targeting" name="gam_targeting" rows="8" class="large-text code"><?php echo esc_textarea( $gam_targeting ); ?></textarea>
			<p class="description">
				<?php esc_html_e( 'JSON object with targeting parameters', 'hip-admanager' ); ?><br />
				<?php esc_html_e( 'Example: {"position": "top", "category": "news"}', 'hip-admanager' ); ?>
			</p>
		</td>
	</tr>
</table>
