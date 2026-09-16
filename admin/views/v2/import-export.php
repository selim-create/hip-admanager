<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class="wrap hip-ads">
	<div class="hip-ads-pagehead">
		<div class="hip-ads-brand"><span class="hip-ads-mark">hip.</span><div><h1>İçe / Dışa Aktar</h1><p>Google Ad Manager CSV’sini önce doğrula, sonra create/update planını gör ve kontrollü biçimde uygula.</p></div></div>
		<div class="hip-ads-actions"><a class="hip-ads-btn" href="<?php echo esc_url( admin_url( 'admin.php?page=hip-ad-slots' ) ); ?>">Reklam alanları</a></div>
	</div>
	<?php include HIP_AD_MANAGER_PLUGIN_DIR . 'admin/views/v2/partials/flash.php'; ?>

	<?php if ( $results ) : ?>
	<section class="hip-ads-card" style="margin-bottom:18px">
		<div class="hip-ads-cardhead"><div><h2>Son import sonucu</h2><p class="hip-ads-muted">İşlem tamamlandı; detaylar bir saat saklanır.</p></div></div>
		<div class="hip-ads-import-counts"><span class="hip-ads-chip create"><?php echo esc_html( count( $results['created'] ) ); ?> oluşturuldu</span><span class="hip-ads-chip update"><?php echo esc_html( count( $results['updated'] ) ); ?> güncellendi</span><span class="hip-ads-chip"><?php echo esc_html( count( $results['skipped'] ) ); ?> atlandı</span><span class="hip-ads-chip invalid"><?php echo esc_html( count( $results['failed'] ) ); ?> başarısız</span></div>
		<?php if ( ! empty( $results['failed'] ) ) : ?><div class="hip-ads-callout danger"><?php foreach ( $results['failed'] as $failed ) : ?><div>Satır <?php echo esc_html( $failed['line'] ); ?> — <?php echo esc_html( $failed['reason'] ); ?></div><?php endforeach; ?></div><?php endif; ?>
	</section>
	<?php endif; ?>

	<?php if ( $preview ) : ?>
	<section class="hip-ads-card flush" style="margin-bottom:18px">
		<div style="padding:20px 20px 0"><div class="hip-ads-cardhead"><div><h2>Import önizlemesi</h2><p class="hip-ads-muted">Henüz hiçbir kayıt değiştirilmedi.</p></div></div>
		<div class="hip-ads-import-counts"><span class="hip-ads-chip create"><?php echo esc_html( $preview['counts']['create'] ); ?> yeni</span><span class="hip-ads-chip update"><?php echo esc_html( $preview['counts']['update'] ); ?> güncelleme</span><span class="hip-ads-chip invalid"><?php echo esc_html( $preview['counts']['invalid'] ); ?> geçersiz</span></div></div>
		<div class="hip-ads-table-wrap"><table class="hip-ads-table"><thead><tr><th>Satır</th><th>Plan</th><th>Alan</th><th>GAM yolu</th><th>Boyutlar</th><th>Not</th></tr></thead><tbody>
		<?php foreach ( $preview['items'] as $item ) : $slot = $item['slot']; ?>
		<tr><td><?php echo esc_html( $item['line'] ); ?></td><td><span class="hip-ads-chip <?php echo esc_attr( $item['action'] ); ?>"><?php echo esc_html( strtoupper( $item['action'] ) ); ?></span></td><td><strong><?php echo esc_html( isset( $slot['name'] ) ? $slot['name'] : '—' ); ?></strong><?php if ( ! empty( $slot['key'] ) ) : ?><br><span class="hip-ads-code"><?php echo esc_html( $slot['key'] ); ?></span><?php endif; ?></td><td><code class="hip-ads-code"><?php echo esc_html( isset( $slot['ad_unit_path'] ) ? $slot['ad_unit_path'] : '—' ); ?></code></td><td><?php echo esc_html( ! empty( $slot['sizes'] ) ? implode( ', ', array_map( function( $size ) { return $size[0] . '×' . $size[1]; }, $slot['sizes'] ) ) : '—' ); ?></td><td><?php if ( $item['errors'] ) : ?><span style="color:#c62828"><?php echo esc_html( implode( ' ', $item['errors'] ) ); ?></span><?php elseif ( $item['warnings'] ) : ?><span style="color:#9a5700"><?php echo esc_html( implode( ' ', $item['warnings'] ) ); ?></span><?php else : ?>Hazır<?php endif; ?></td></tr>
		<?php endforeach; ?>
		</tbody></table></div>
		<div style="padding:18px 20px;display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap;border-top:1px solid #eeeef2">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="hip_ad_import_cancel"><?php wp_nonce_field( 'hip_ad_import_cancel' ); ?><button class="hip-ads-btn" type="submit">İptal et</button></form>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-confirm-import><input type="hidden" name="action" value="hip_ad_import_confirm"><?php wp_nonce_field( 'hip_ad_import_confirm' ); ?><label style="margin-right:14px"><input type="checkbox" name="update_existing" value="1" checked> Eşleşen mevcut slotları güncelle</label><button class="hip-ads-btn primary" type="submit" <?php disabled( 0 === (int) $preview['counts']['create'] && 0 === (int) $preview['counts']['update'] ); ?>>Değişiklikleri uygula</button></form>
		</div>
	</section>
	<?php else : ?>
	<div class="hip-ads-grid two">
		<section class="hip-ads-card">
			<div class="hip-ads-cardhead"><div><h2>GAM CSV içe aktar</h2><p class="hip-ads-muted">Import hiçbir zaman doğrudan yazmaz; önce dry-run önizlemesi üretir.</p></div></div>
			<div class="hip-ads-callout" style="margin-bottom:18px">Google Ad Manager export’larındaki <strong>ID, Code, Name ve Sizes</strong> alanları otomatik tanınır. Aynı ad unit path veya slot key bulunursa yeni duplicate açmak yerine update planlanır.</div>
			<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="hip_ad_import_upload"><?php wp_nonce_field( 'hip_ad_import_upload' ); ?><div class="hip-ads-field"><label>CSV dosyası</label><input type="file" name="csv_file" accept=".csv,text/csv" required><span class="hip-ads-help">Maksimum 5 MB.</span></div><div style="margin-top:18px"><button class="hip-ads-btn primary" type="submit">CSV’yi doğrula ve önizle</button></div></form>
		</section>
		<section class="hip-ads-card">
			<div class="hip-ads-cardhead"><div><h2>JSON yedek al</h2><p class="hip-ads-muted">Ayarlar, ads.txt ve normalize slot kayıtlarını tek dosyada indir.</p></div></div>
			<p>Production değişikliklerinden önce veya toplu düzenleme öncesinde yedek almak için kullan.</p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="hip_ad_export_json"><?php wp_nonce_field( 'hip_ad_export_json' ); ?><button class="hip-ads-btn" type="submit">JSON yedeğini indir</button></form>
		</section>
	</div>
	<?php endif; ?>
</div>
