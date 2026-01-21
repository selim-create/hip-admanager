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
$cache_duration = isset( $settings['cache_duration'] ) ? $settings['cache_duration'] : 3600;
$debug_mode = isset( $settings['debug_mode'] ) ? $settings['debug_mode'] : 0;
$ads_txt_content = get_option( 'hip_ad_ads_txt_content', '' );
?>
<div class="wrap">
	<h1><?php esc_html_e( 'HIP Ad Manager Settings', 'hip-admanager' ); ?></h1>

	<form method="post" action="">
		<?php wp_nonce_field( 'hip_ad_settings_save', 'hip_ad_settings_nonce' ); ?>

		<h2><?php esc_html_e( 'General Settings', 'hip-admanager' ); ?></h2>
		
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

			<tr>
				<th scope="row"><?php esc_html_e( 'Debug Mode', 'hip-admanager' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="debug_mode" value="1" <?php checked( $debug_mode, 1 ); ?> />
						<?php esc_html_e( 'Enable Debug Mode', 'hip-admanager' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'When enabled, API responses include debug information and frontend can display placeholder boxes with slot details.', 'hip-admanager' ); ?></p>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Cache Settings', 'hip-admanager' ); ?></h2>
		
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="cache_duration"><?php esc_html_e( 'Cache Duration', 'hip-admanager' ); ?></label>
				</th>
				<td>
					<input type="number" id="cache_duration" name="cache_duration" value="<?php echo esc_attr( $cache_duration ); ?>" min="0" step="60" class="small-text" />
					<?php esc_html_e( 'seconds', 'hip-admanager' ); ?>
					<p class="description"><?php esc_html_e( 'How long to cache API responses (default: 3600 seconds / 1 hour). Set to 0 to disable caching.', 'hip-admanager' ); ?></p>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'ads.txt Management', 'hip-admanager' ); ?></h2>
		
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="ads_txt_content"><?php esc_html_e( 'ads.txt Content', 'hip-admanager' ); ?></label>
				</th>
				<td>
					<textarea id="ads_txt_content" name="ads_txt_content" rows="10" class="large-text code"><?php echo esc_textarea( $ads_txt_content ); ?></textarea>
					<p class="description">
						<?php esc_html_e( 'Enter your ads.txt content here. For Google Ad Manager, typically:', 'hip-admanager' ); ?><br>
						<code>google.com, pub-XXXXXXXXXXXXXXXX, DIRECT, f08c47fec0942fa0</code><br>
						<?php
						printf(
							/* translators: %s: REST API endpoint URL */
							esc_html__( 'Available at: %s', 'hip-admanager' ),
							'<code>' . esc_html( rest_url( 'hip-ads/v1/ads-txt' ) ) . '</code>'
						);
						?>
					</p>
				</td>
			</tr>
		</table>

		<p class="submit">
			<input type="submit" name="hip_ad_settings_submit" class="button button-primary" value="<?php esc_attr_e( 'Save Settings', 'hip-admanager' ); ?>" />
		</p>
	</form>
	
	<hr>
	
	<h2><?php esc_html_e( 'Cache Management', 'hip-admanager' ); ?></h2>
	<p><?php esc_html_e( 'Clear the cache if you made changes to ad slots and want them to appear immediately in the API.', 'hip-admanager' ); ?></p>
	<p>
		<button type="button" id="hip-ad-clear-cache" class="button">
			<?php esc_html_e( 'Clear Cache Now', 'hip-admanager' ); ?>
		</button>
		<span id="hip-ad-cache-status"></span>
	</p>
	
	<script>
	// Clear cache via REST API with proper WordPress nonce authentication
	// Note: Nonce is generated server-side during page render. While it may become
	// stale if the page is cached or open for extended periods (>24 hours), this is
	// acceptable for an admin-only action. WordPress will return a 403 error if the
	// nonce expires, prompting the user to refresh the page.
	document.getElementById('hip-ad-clear-cache').addEventListener('click', function() {
		const button = this;
		const status = document.getElementById('hip-ad-cache-status');
		
		button.disabled = true;
		button.textContent = '<?php esc_html_e( 'Clearing...', 'hip-admanager' ); ?>';
		status.textContent = '';
		
		fetch('<?php echo esc_url( rest_url( 'hip-ads/v1/cache/clear' ) ); ?>', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': '<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>'
			},
			credentials: 'same-origin' // Include cookies for authentication
		})
		.then(response => response.json())
		.then(data => {
			button.disabled = false;
			button.textContent = '<?php esc_html_e( 'Clear Cache Now', 'hip-admanager' ); ?>';
			
			if (data.success) {
				status.textContent = '✓ ' + data.message;
				status.style.color = 'green';
			} else {
				status.textContent = '✗ Error clearing cache';
				status.style.color = 'red';
			}
			
			setTimeout(() => {
				status.textContent = '';
			}, 3000);
		})
		.catch(error => {
			button.disabled = false;
			button.textContent = '<?php esc_html_e( 'Clear Cache Now', 'hip-admanager' ); ?>';
			status.textContent = '✗ Error: ' + error.message;
			status.style.color = 'red';
		});
	});
	</script>
</div>
