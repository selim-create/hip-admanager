<?php
/**
 * Refresh Settings Metabox
 *
 * @package HIP_Ad_Manager
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="hip-ad-metabox">
	<p>
		<label>
			<input type="checkbox" name="hip_ad_refresh_enabled" value="1" <?php checked( $refresh_enabled, '1' ); ?> />
			<?php esc_html_e( 'Enable automatic ad refresh', 'hip-admanager' ); ?>
		</label>
	</p>
	
	<p>
		<label for="hip_ad_refresh_interval">
			<?php esc_html_e( 'Refresh Interval (seconds)', 'hip-admanager' ); ?>
		</label>
		<input type="number" id="hip_ad_refresh_interval" name="hip_ad_refresh_interval" value="<?php echo esc_attr( $refresh_interval ); ?>" min="10" step="1" class="small-text" />
		<span class="description"><?php esc_html_e( 'Minimum: 10 seconds', 'hip-admanager' ); ?></span>
	</p>
	
	<p>
		<label for="hip_ad_max_refreshes">
			<?php esc_html_e( 'Maximum Refreshes', 'hip-admanager' ); ?>
		</label>
		<input type="number" id="hip_ad_max_refreshes" name="hip_ad_max_refreshes" value="<?php echo esc_attr( $max_refreshes ); ?>" min="1" step="1" class="small-text" />
		<span class="description"><?php esc_html_e( 'Maximum number of times to refresh this ad', 'hip-admanager' ); ?></span>
	</p>
	
	<p class="description">
		<?php esc_html_e( 'Ads will only refresh when visible in viewport and will pause when tab is hidden.', 'hip-admanager' ); ?>
	</p>
</div>
