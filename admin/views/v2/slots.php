<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$groups = HIP_Ad_Schema::placement_groups();
$statuses = HIP_Ad_Schema::statuses();
?>
<div class="wrap hip-ads">
	<div class="hip-ads-pagehead">
		<div class="hip-ads-brand"><span class="hip-ads-mark">hip.</span><div><h1>Reklam Alanları</h1><p>Frontend’de kullanılacak stable placement key’leri ile GAM ad unit’lerini yönet.</p></div></div>
		<div class="hip-ads-actions"><a class="hip-ads-btn" href="<?php echo esc_url( admin_url( 'admin.php?page=hip-ad-import' ) ); ?>">İçe aktar</a><a class="hip-ads-btn primary" href="<?php echo esc_url( admin_url( 'admin.php?page=hip-ad-slot-new' ) ); ?>">+ Yeni alan</a></div>
	</div>
	<?php include HIP_AD_MANAGER_PLUGIN_DIR . 'admin/views/v2/partials/flash.php'; ?>

	<form class="hip-ads-filters" method="get">
		<input type="hidden" name="page" value="hip-ad-slots">
		<input type="search" name="s" value="<?php echo esc_attr( $filters['search'] ); ?>" placeholder="Ad, key veya GAM yolu ara…">
		<select name="status"><option value="">Tüm durumlar</option><?php foreach ( $statuses as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $filters['status'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select>
		<select name="placement_group"><option value="">Tüm gruplar</option><?php foreach ( $groups as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $filters['placement_group'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select>
		<button class="hip-ads-btn" type="submit">Filtrele</button>
		<?php if ( array_filter( $filters ) ) : ?><a class="hip-ads-btn linklike" href="<?php echo esc_url( admin_url( 'admin.php?page=hip-ad-slots' ) ); ?>">Temizle</a><?php endif; ?>
	</form>

	<section class="hip-ads-card flush">
		<?php if ( $slots ) : ?>
		<div class="hip-ads-table-wrap"><table class="hip-ads-table">
			<thead><tr><th>Alan</th><th>GAM ad unit</th><th>Yerleşim</th><th>Cihaz</th><th>Boyutlar</th><th>Durum</th></tr></thead>
			<tbody>
			<?php foreach ( $slots as $slot ) :
				$edit_url = admin_url( 'admin.php?page=hip-ad-slot-new&id=' . $slot['id'] );
			?>
			<tr>
				<td><strong><a href="<?php echo esc_url( $edit_url ); ?>"><?php echo esc_html( $slot['name'] ); ?></a></strong><br><span class="hip-ads-code"><?php echo esc_html( $slot['key'] ); ?></span>
					<div class="row-actions"><a href="<?php echo esc_url( $edit_url ); ?>">Düzenle</a>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline"><input type="hidden" name="action" value="hip_ad_duplicate_slot"><input type="hidden" name="slot_id" value="<?php echo esc_attr( $slot['id'] ); ?>"><?php wp_nonce_field( 'hip_ad_duplicate_slot' ); ?><button class="button-link" type="submit">Kopyala</button></form>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline" data-confirm-delete><input type="hidden" name="action" value="hip_ad_delete_slot"><input type="hidden" name="slot_id" value="<?php echo esc_attr( $slot['id'] ); ?>"><?php wp_nonce_field( 'hip_ad_delete_slot' ); ?><button class="button-link delete" type="submit">Sil</button></form></div>
				</td>
				<td><code class="hip-ads-code"><?php echo esc_html( $slot['ad_unit_path'] ); ?></code><?php if ( $slot['inventory_id'] ) : ?><br><small class="hip-ads-muted">ID: <?php echo esc_html( $slot['inventory_id'] ); ?></small><?php endif; ?></td>
				<td><?php echo esc_html( isset( $groups[ $slot['placement_group'] ] ) ? $groups[ $slot['placement_group'] ] : $slot['placement_group'] ); ?><br><small class="hip-ads-muted"><?php echo esc_html( $slot['placement_key'] ); ?></small></td>
				<td><?php echo esc_html( isset( HIP_Ad_Schema::devices()[ $slot['device'] ] ) ? HIP_Ad_Schema::devices()[ $slot['device'] ] : $slot['device'] ); ?></td>
				<td><?php echo esc_html( implode( ', ', array_map( function( $size ) { return $size[0] . '×' . $size[1]; }, $slot['sizes'] ) ) ); ?></td>
				<td><span class="hip-ads-status <?php echo esc_attr( $slot['status'] ); ?>"><?php echo esc_html( isset( $statuses[ $slot['status'] ] ) ? $statuses[ $slot['status'] ] : $slot['status'] ); ?></span></td>
			</tr>
			<?php endforeach; ?>
			</tbody>
		</table></div>
		<?php else : ?>
		<div class="hip-ads-empty"><strong>Bu filtreye uyan reklam alanı yok.</strong><a href="<?php echo esc_url( admin_url( 'admin.php?page=hip-ad-slot-new' ) ); ?>">Yeni bir alan oluştur</a> veya GAM CSV’si içe aktar.</div>
		<?php endif; ?>
	</section>
</div>
