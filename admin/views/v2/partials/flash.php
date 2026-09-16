<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! empty( $flash['message'] ) ) :
	$type = isset( $flash['type'] ) ? sanitize_html_class( $flash['type'] ) : 'info';
?>
<div class="hip-ads-flash <?php echo esc_attr( $type ); ?>" role="status">
	<?php echo esc_html( $flash['message'] ); ?>
</div>
<?php endif; ?>
