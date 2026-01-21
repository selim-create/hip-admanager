<?php
/**
 * Display Rules metabox
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
			<label for="gam_placement"><?php esc_html_e( 'Placement', 'hip-admanager' ); ?></label>
		</th>
		<td>
			<select id="gam_placement" name="gam_placement">
				<option value="header" <?php selected( $gam_placement, 'header' ); ?>><?php esc_html_e( 'Header', 'hip-admanager' ); ?></option>
				<option value="sidebar" <?php selected( $gam_placement, 'sidebar' ); ?>><?php esc_html_e( 'Sidebar', 'hip-admanager' ); ?></option>
				<option value="in-content" <?php selected( $gam_placement, 'in-content' ); ?>><?php esc_html_e( 'In Content', 'hip-admanager' ); ?></option>
				<option value="footer" <?php selected( $gam_placement, 'footer' ); ?>><?php esc_html_e( 'Footer', 'hip-admanager' ); ?></option>
				<option value="mobile-sticky" <?php selected( $gam_placement, 'mobile-sticky' ); ?>><?php esc_html_e( 'Mobile Sticky', 'hip-admanager' ); ?></option>
				<option value="interstitial" <?php selected( $gam_placement, 'interstitial' ); ?>><?php esc_html_e( 'Interstitial', 'hip-admanager' ); ?></option>
			</select>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for="gam_device"><?php esc_html_e( 'Device', 'hip-admanager' ); ?></label>
		</th>
		<td>
			<select id="gam_device" name="gam_device">
				<option value="all" <?php selected( $gam_device, 'all' ); ?>><?php esc_html_e( 'All Devices', 'hip-admanager' ); ?></option>
				<option value="mobile" <?php selected( $gam_device, 'mobile' ); ?>><?php esc_html_e( 'Mobile', 'hip-admanager' ); ?></option>
				<option value="tablet" <?php selected( $gam_device, 'tablet' ); ?>><?php esc_html_e( 'Tablet', 'hip-admanager' ); ?></option>
				<option value="desktop" <?php selected( $gam_device, 'desktop' ); ?>><?php esc_html_e( 'Desktop', 'hip-admanager' ); ?></option>
			</select>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for="gam_lazy_load"><?php esc_html_e( 'Lazy Load', 'hip-admanager' ); ?></label>
		</th>
		<td>
			<label>
				<input type="checkbox" id="gam_lazy_load" name="gam_lazy_load" value="1" <?php checked( $gam_lazy_load, '1' ); ?> />
				<?php esc_html_e( 'Enable lazy loading for this ad slot', 'hip-admanager' ); ?>
			</label>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for="gam_display_rules"><?php esc_html_e( 'Display Rules', 'hip-admanager' ); ?></label>
		</th>
		<td>
			<textarea id="gam_display_rules" name="gam_display_rules" rows="5" class="large-text code"><?php echo esc_textarea( $gam_display_rules ); ?></textarea>
			<p class="description">
				<?php esc_html_e( 'JSON object with display rules', 'hip-admanager' ); ?><br />
				<?php esc_html_e( 'Example: {"page_types": ["post", "page"], "categories": ["news"]}', 'hip-admanager' ); ?>
			</p>
		</td>
	</tr>
</table>
