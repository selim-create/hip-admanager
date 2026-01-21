<?php
/**
 * Import page
 *
 * @package HIP_Ad_Manager
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Import Ad Slots from CSV', 'hip-admanager' ); ?></h1>

	<?php if ( isset( $_GET['error'] ) ) : ?>
		<div class="notice notice-error">
			<p>
				<?php
				switch ( $_GET['error'] ) {
					case 'upload_failed':
						esc_html_e( 'Failed to upload file.', 'hip-admanager' );
						break;
					case 'invalid_file_type':
						esc_html_e( 'Invalid file type. Please upload a CSV file.', 'hip-admanager' );
						break;
					case 'parse_failed':
						esc_html_e( 'Failed to parse CSV file.', 'hip-admanager' );
						break;
					case 'no_preview':
						esc_html_e( 'No preview data found. Please upload a CSV file first.', 'hip-admanager' );
						break;
					default:
						esc_html_e( 'An error occurred.', 'hip-admanager' );
				}
				?>
			</p>
		</div>
	<?php endif; ?>

	<?php if ( 'upload' === $step ) : ?>
		<div class="hip-ad-import-upload">
			<h2><?php esc_html_e( 'Step 1: Upload CSV File', 'hip-admanager' ); ?></h2>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
				<input type="hidden" name="action" value="hip_ad_import_csv" />
				<?php wp_nonce_field( 'hip_ad_import_csv', 'hip_ad_import_nonce' ); ?>

				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="csv_file"><?php esc_html_e( 'CSV File', 'hip-admanager' ); ?></label>
						</th>
						<td>
							<input type="file" id="csv_file" name="csv_file" accept=".csv" required />
							<p class="description">
								<?php esc_html_e( 'Upload a CSV file exported from Google Ad Manager', 'hip-admanager' ); ?>
							</p>
						</td>
					</tr>
				</table>

				<p class="submit">
					<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Upload and Preview', 'hip-admanager' ); ?>" />
				</p>
			</form>

			<div class="hip-ad-sample-csv">
				<h3><?php esc_html_e( 'Sample CSV Format', 'hip-admanager' ); ?></h3>
				<p>
					<a href="<?php echo esc_url( HIP_AD_MANAGER_PLUGIN_URL . 'templates/sample-import.csv' ); ?>" class="button" download>
						<?php esc_html_e( 'Download Sample CSV', 'hip-admanager' ); ?>
					</a>
				</p>
				<p class="description">
					<?php esc_html_e( 'The CSV should contain columns: ID, Parent Id, Code, Name, Sizes:, Description, Enabled for AdSense, Placements, Target Window, Labels', 'hip-admanager' ); ?>
				</p>
			</div>
		</div>

	<?php elseif ( 'preview' === $step && $preview_data ) : ?>
		<div class="hip-ad-import-preview">
			<h2><?php esc_html_e( 'Step 2: Preview Import', 'hip-admanager' ); ?></h2>
			<p><?php printf( esc_html__( 'The following %d ad slots will be created:', 'hip-admanager' ), count( $preview_data ) ); ?></p>

			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Name', 'hip-admanager' ); ?></th>
						<th><?php esc_html_e( 'Slot ID', 'hip-admanager' ); ?></th>
						<th><?php esc_html_e( 'Ad Unit Path', 'hip-admanager' ); ?></th>
						<th><?php esc_html_e( 'Sizes', 'hip-admanager' ); ?></th>
						<th><?php esc_html_e( 'Placement', 'hip-admanager' ); ?></th>
						<th><?php esc_html_e( 'Device', 'hip-admanager' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $preview_data as $slot ) : ?>
						<tr>
							<td><?php echo esc_html( $slot['name'] ); ?></td>
							<td><?php echo esc_html( $slot['gam_slot_id'] ); ?></td>
							<td><code><?php echo esc_html( $slot['gam_ad_unit_path'] ); ?></code></td>
							<td>
								<?php
								$sizes_display = array();
								foreach ( $slot['gam_sizes'] as $size ) {
									$sizes_display[] = implode( 'x', $size );
								}
								echo esc_html( implode( ', ', $sizes_display ) );
								?>
							</td>
							<td><span class="hip-ad-badge"><?php echo esc_html( $slot['gam_placement'] ); ?></span></td>
							<td><span class="hip-ad-badge"><?php echo esc_html( $slot['gam_device'] ); ?></span></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="hip_ad_confirm_import" />
				<?php wp_nonce_field( 'hip_ad_confirm_import', 'hip_ad_confirm_nonce' ); ?>

				<p class="submit">
					<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Confirm Import', 'hip-admanager' ); ?>" />
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=hip-ad-import' ) ); ?>" class="button">
						<?php esc_html_e( 'Cancel', 'hip-admanager' ); ?>
					</a>
				</p>
			</form>
		</div>

	<?php elseif ( 'results' === $step ) : ?>
		<?php
		$results = get_transient( 'hip_ad_import_results' );
		if ( $results ) :
			delete_transient( 'hip_ad_import_results' );
			?>
			<div class="hip-ad-import-results">
				<h2><?php esc_html_e( 'Import Results', 'hip-admanager' ); ?></h2>

				<?php if ( ! empty( $results['success'] ) ) : ?>
					<div class="notice notice-success">
						<p>
							<?php
							printf(
								esc_html__( 'Successfully imported %d ad slots.', 'hip-admanager' ),
								count( $results['success'] )
							);
							?>
						</p>
					</div>

					<h3><?php esc_html_e( 'Imported Slots', 'hip-admanager' ); ?></h3>
					<ul>
						<?php foreach ( $results['success'] as $slot ) : ?>
							<li>
								<a href="<?php echo esc_url( get_edit_post_link( $slot['id'] ) ); ?>">
									<?php echo esc_html( $slot['name'] ); ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>

				<?php if ( ! empty( $results['failed'] ) ) : ?>
					<div class="notice notice-error">
						<p>
							<?php
							printf(
								esc_html__( 'Failed to import %d ad slots.', 'hip-admanager' ),
								count( $results['failed'] )
							);
							?>
						</p>
					</div>

					<h3><?php esc_html_e( 'Failed Imports', 'hip-admanager' ); ?></h3>
					<ul>
						<?php foreach ( $results['failed'] as $failed ) : ?>
							<li>
								<?php echo esc_html( $failed['name'] ); ?>
								- <?php echo esc_html( $failed['error'] ); ?>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>

				<p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=hip-ad-import' ) ); ?>" class="button">
						<?php esc_html_e( 'Import More', 'hip-admanager' ); ?>
					</a>
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . HIP_Ad_Slot::POST_TYPE ) ); ?>" class="button button-primary">
						<?php esc_html_e( 'View Ad Slots', 'hip-admanager' ); ?>
					</a>
				</p>
			</div>
		<?php endif; ?>
	<?php endif; ?>
</div>
