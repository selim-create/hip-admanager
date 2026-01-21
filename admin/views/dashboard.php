<?php
/**
 * Dashboard page
 *
 * @package HIP_Ad_Manager
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
	<h1><?php esc_html_e( 'HIP Ad Manager Dashboard', 'hip-admanager' ); ?></h1>

	<div class="hip-ad-dashboard">
		<div class="hip-ad-stats">
			<div class="hip-ad-stat-box">
				<h3><?php esc_html_e( 'Total Ad Slots', 'hip-admanager' ); ?></h3>
				<p class="hip-ad-stat-number"><?php echo esc_html( $total_slots->publish ); ?></p>
			</div>

			<div class="hip-ad-stat-box">
				<h3><?php esc_html_e( 'Network Code', 'hip-admanager' ); ?></h3>
				<p class="hip-ad-stat-text">
					<?php echo esc_html( isset( $settings['network_code'] ) ? $settings['network_code'] : __( 'Not set', 'hip-admanager' ) ); ?>
				</p>
			</div>

			<div class="hip-ad-stat-box">
				<h3><?php esc_html_e( 'Site Name', 'hip-admanager' ); ?></h3>
				<p class="hip-ad-stat-text">
					<?php echo esc_html( isset( $settings['site_name'] ) ? $settings['site_name'] : __( 'Not set', 'hip-admanager' ) ); ?>
				</p>
			</div>
		</div>

		<div class="hip-ad-quick-links">
			<h2><?php esc_html_e( 'Quick Links', 'hip-admanager' ); ?></h2>
			<ul>
				<li><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . HIP_Ad_Slot::POST_TYPE ) ); ?>"><?php esc_html_e( 'Manage Ad Slots', 'hip-admanager' ); ?></a></li>
				<li><a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . HIP_Ad_Slot::POST_TYPE ) ); ?>"><?php esc_html_e( 'Add New Ad Slot', 'hip-admanager' ); ?></a></li>
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=hip-ad-import' ) ); ?>"><?php esc_html_e( 'Import from CSV', 'hip-admanager' ); ?></a></li>
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=hip-ad-settings' ) ); ?>"><?php esc_html_e( 'Settings', 'hip-admanager' ); ?></a></li>
			</ul>
		</div>

		<div class="hip-ad-api-info">
			<h2><?php esc_html_e( 'REST API Endpoints', 'hip-admanager' ); ?></h2>
			<ul>
				<li><code>GET <?php echo esc_html( rest_url( 'hip-ads/v1/config' ) ); ?></code></li>
				<li><code>GET <?php echo esc_html( rest_url( 'hip-ads/v1/slots' ) ); ?></code></li>
				<li><code>GET <?php echo esc_html( rest_url( 'hip-ads/v1/slots/{id}' ) ); ?></code></li>
				<li><code>POST <?php echo esc_html( rest_url( 'hip-ads/v1/track' ) ); ?></code></li>
			</ul>
		</div>
	</div>
</div>
