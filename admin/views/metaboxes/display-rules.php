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
				<!-- Header -->
				<optgroup label="<?php esc_attr_e( 'Header', 'hip-admanager' ); ?>">
					<option value="header-leaderboard" <?php selected( $gam_placement, 'header-leaderboard' ); ?>><?php esc_html_e( 'Header - Leaderboard (728x90)', 'hip-admanager' ); ?></option>
					<option value="header-masthead" <?php selected( $gam_placement, 'header-masthead' ); ?>><?php esc_html_e( 'Header - Masthead (970x250)', 'hip-admanager' ); ?></option>
					<option value="header-mobile" <?php selected( $gam_placement, 'header-mobile' ); ?>><?php esc_html_e( 'Header - Mobile (320x100)', 'hip-admanager' ); ?></option>
				</optgroup>
				
				<!-- Sidebar -->
				<optgroup label="<?php esc_attr_e( 'Sidebar', 'hip-admanager' ); ?>">
					<option value="sidebar-top" <?php selected( $gam_placement, 'sidebar-top' ); ?>><?php esc_html_e( 'Sidebar - Top (300x250)', 'hip-admanager' ); ?></option>
					<option value="sidebar-middle" <?php selected( $gam_placement, 'sidebar-middle' ); ?>><?php esc_html_e( 'Sidebar - Middle (300x600)', 'hip-admanager' ); ?></option>
					<option value="sidebar-bottom" <?php selected( $gam_placement, 'sidebar-bottom' ); ?>><?php esc_html_e( 'Sidebar - Bottom', 'hip-admanager' ); ?></option>
					<option value="sidebar-sticky" <?php selected( $gam_placement, 'sidebar-sticky' ); ?>><?php esc_html_e( 'Sidebar - Sticky (160x600)', 'hip-admanager' ); ?></option>
				</optgroup>
				
				<!-- Content -->
				<optgroup label="<?php esc_attr_e( 'Content', 'hip-admanager' ); ?>">
					<option value="content-top" <?php selected( $gam_placement, 'content-top' ); ?>><?php esc_html_e( 'Content - Top', 'hip-admanager' ); ?></option>
					<option value="content-after-hero" <?php selected( $gam_placement, 'content-after-hero' ); ?>><?php esc_html_e( 'Content - After Hero', 'hip-admanager' ); ?></option>
					<option value="content-in-feed" <?php selected( $gam_placement, 'content-in-feed' ); ?>><?php esc_html_e( 'Content - In Feed (between cards)', 'hip-admanager' ); ?></option>
					<option value="content-after-section" <?php selected( $gam_placement, 'content-after-section' ); ?>><?php esc_html_e( 'Content - After Section', 'hip-admanager' ); ?></option>
					<option value="content-middle" <?php selected( $gam_placement, 'content-middle' ); ?>><?php esc_html_e( 'Content - Middle', 'hip-admanager' ); ?></option>
					<option value="content-bottom" <?php selected( $gam_placement, 'content-bottom' ); ?>><?php esc_html_e( 'Content - Bottom', 'hip-admanager' ); ?></option>
				</optgroup>
				
				<!-- Footer -->
				<optgroup label="<?php esc_attr_e( 'Footer', 'hip-admanager' ); ?>">
					<option value="footer-banner" <?php selected( $gam_placement, 'footer-banner' ); ?>><?php esc_html_e( 'Footer - Banner (728x90)', 'hip-admanager' ); ?></option>
					<option value="footer-sticky-mobile" <?php selected( $gam_placement, 'footer-sticky-mobile' ); ?>><?php esc_html_e( 'Footer - Sticky Mobile (320x50)', 'hip-admanager' ); ?></option>
				</optgroup>
				
				<!-- Special -->
				<optgroup label="<?php esc_attr_e( 'Special', 'hip-admanager' ); ?>">
					<option value="interstitial" <?php selected( $gam_placement, 'interstitial' ); ?>><?php esc_html_e( 'Interstitial', 'hip-admanager' ); ?></option>
					<option value="native" <?php selected( $gam_placement, 'native' ); ?>><?php esc_html_e( 'Native Ad', 'hip-admanager' ); ?></option>
				</optgroup>
				
				<!-- Legacy (backward compatibility) -->
				<optgroup label="<?php esc_attr_e( 'Legacy', 'hip-admanager' ); ?>">
					<option value="header" <?php selected( $gam_placement, 'header' ); ?>><?php esc_html_e( 'Header (Legacy)', 'hip-admanager' ); ?></option>
					<option value="sidebar" <?php selected( $gam_placement, 'sidebar' ); ?>><?php esc_html_e( 'Sidebar (Legacy)', 'hip-admanager' ); ?></option>
					<option value="in-content" <?php selected( $gam_placement, 'in-content' ); ?>><?php esc_html_e( 'In-Content (Legacy)', 'hip-admanager' ); ?></option>
					<option value="footer" <?php selected( $gam_placement, 'footer' ); ?>><?php esc_html_e( 'Footer (Legacy)', 'hip-admanager' ); ?></option>
					<option value="mobile-sticky" <?php selected( $gam_placement, 'mobile-sticky' ); ?>><?php esc_html_e( 'Mobile Sticky (Legacy)', 'hip-admanager' ); ?></option>
				</optgroup>
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
