<?php
/**
 * Settings page
 *
 * @package HIP_Ad_Manager
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$network_code = isset( $settings['network_code'] ) ? $settings['network_code'] : '';
$site_name = isset( $settings['site_name'] ) ? $settings['site_name'] : '';
$enable_lazy_load = isset( $settings['enable_lazy_load'] ) ? $settings['enable_lazy_load'] : 1;
$enable_single_request = isset( $settings['enable_single_request'] ) ? $settings['enable_single_request'] : 1;
$global_targeting = isset( $settings['global_targeting'] ) ? $settings['global_targeting'] : '{}';
?>
<div class="wrap">
	<h1><?php esc_html_e( 'HIP Ad Manager Settings', 'hip-admanager' ); ?></h1>

	<form method="post" action="">
		<?php wp_nonce_field( 'hip_ad_settings_save', 'hip_ad_settings_nonce' ); ?>

		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="network_code"><?php esc_html_e( 'Network Code', 'hip-admanager' ); ?></label>
				</th>
				<td>
					<input type="text" id="network_code" name="network_code" value="<?php echo esc_attr( $network_code ); ?>" class="regular-text" />
					<p class="description"><?php esc_html_e( 'Your Google Ad Manager network code (e.g., 273585429)', 'hip-admanager' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="site_name"><?php esc_html_e( 'Site Name', 'hip-admanager' ); ?></label>
				</th>
				<td>
					<input type="text" id="site_name" name="site_name" value="<?php echo esc_attr( $site_name ); ?>" class="regular-text" />
					<p class="description"><?php esc_html_e( 'Your site name for targeting (e.g., kidsgourmet)', 'hip-admanager' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Lazy Load', 'hip-admanager' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="enable_lazy_load" value="1" <?php checked( $enable_lazy_load, 1 ); ?> />
						<?php esc_html_e( 'Enable lazy loading for ads', 'hip-admanager' ); ?>
					</label>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Single Request Mode', 'hip-admanager' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="enable_single_request" value="1" <?php checked( $enable_single_request, 1 ); ?> />
						<?php esc_html_e( 'Enable single request mode (SRA)', 'hip-admanager' ); ?>
					</label>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="global_targeting"><?php esc_html_e( 'Global Targeting', 'hip-admanager' ); ?></label>
				</th>
				<td>
					<textarea id="global_targeting" name="global_targeting" rows="5" class="large-text code"><?php echo esc_textarea( $global_targeting ); ?></textarea>
					<p class="description"><?php esc_html_e( 'JSON object with global targeting key-value pairs (e.g., {"site": "kidsgourmet"})', 'hip-admanager' ); ?></p>
				</td>
			</tr>
		</table>

		<p class="submit">
			<input type="submit" name="hip_ad_settings_submit" class="button button-primary" value="<?php esc_attr_e( 'Save Settings', 'hip-admanager' ); ?>" />
		</p>
	</form>
</div>
