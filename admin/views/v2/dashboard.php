<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$error_count = count( array_filter( $issues, function( $issue ) { return 'error' === $issue['level']; } ) );
$warning_count = count( array_filter( $issues, function( $issue ) { return 'warning' === $issue['level']; } ) );
$health = max( 0, 100 - ( $error_count * 20 ) - ( $warning_count * 5 ) - ( empty( $settings['network_code'] ) ? 30 : 0 ) );
$health_class = $health >= 90 ? '' : ( $health >= 70 ? 'warn' : 'bad' );
?>
<div class="wrap hip-ads">
	<div class="hip-ads-pagehead">
		<div class="hip-ads-brand"><span class="hip-ads-mark">hip.</span><div><h1>HIP Ads</h1><p>Google Ad Manager envanterini, headless yerleşimleri ve teslimat kurallarını tek yerden yönet.</p></div></div>
		<div class="hip-ads-actions">
			<a class="hip-ads-btn" href="<?php echo esc_url( admin_url( 'admin.php?page=hip-ad-diagnostics' ) ); ?>">Tanılama</a>
			<a class="hip-ads-btn primary" href="<?php echo esc_url( admin_url( 'admin.php?page=hip-ad-slot-new' ) ); ?>">+ Yeni reklam alanı</a>
		</div>
	</div>
	<?php include HIP_AD_MANAGER_PLUGIN_DIR . 'admin/views/v2/partials/flash.php'; ?>

	<div class="hip-ads-grid stats">
		<div class="hip-ads-card"><div class="hip-ads-stat-label">Toplam alan</div><div class="hip-ads-stat-value"><?php echo esc_html( $stats['total'] ); ?></div><div class="hip-ads-stat-foot">Tüm kayıtlı yerleşimler</div></div>
		<div class="hip-ads-card"><div class="hip-ads-stat-label">Aktif</div><div class="hip-ads-stat-value"><?php echo esc_html( $stats['active'] ); ?></div><div class="hip-ads-stat-foot">API tarafından yayınlanabilir</div></div>
		<div class="hip-ads-card"><div class="hip-ads-stat-label">Network</div><div class="hip-ads-stat-value" style="font-size:24px"><?php echo $settings['network_code'] ? esc_html( $settings['network_code'] ) : '—'; ?></div><div class="hip-ads-stat-foot"><?php echo $settings['ads_enabled'] ? 'Master switch açık' : 'Master switch kapalı'; ?></div></div>
		<div class="hip-ads-card"><div class="hip-ads-health"><div class="hip-ads-health-score <?php echo esc_attr( $health_class ); ?>"><?php echo esc_html( $health ); ?></div><div><div class="hip-ads-stat-label">Sistem sağlığı</div><strong><?php echo $error_count ? esc_html( $error_count . ' kritik konu' ) : 'Kritik hata yok'; ?></strong><div class="hip-ads-stat-foot"><?php echo esc_html( $warning_count ); ?> uyarı</div></div></div></div>
	</div>

	<div class="hip-ads-grid two" style="margin-top:18px">
		<section class="hip-ads-card flush">
			<div class="hip-ads-cardhead" style="padding:18px 20px 0"><div><h2>Reklam alanları</h2><p class="hip-ads-muted">Son kayıtların hızlı görünümü</p></div><a href="<?php echo esc_url( admin_url( 'admin.php?page=hip-ad-slots' ) ); ?>">Tümünü gör →</a></div>
			<?php if ( $slots ) : ?>
			<div class="hip-ads-table-wrap"><table class="hip-ads-table"><thead><tr><th>Alan</th><th>Yerleşim</th><th>Boyut</th><th>Durum</th></tr></thead><tbody>
			<?php foreach ( $slots as $slot ) : ?>
			<tr><td><strong><a href="<?php echo esc_url( admin_url( 'admin.php?page=hip-ad-slot-new&id=' . $slot['id'] ) ); ?>"><?php echo esc_html( $slot['name'] ); ?></a></strong><br><span class="hip-ads-code"><?php echo esc_html( $slot['key'] ); ?></span></td><td><?php echo esc_html( $slot['placement_group'] ); ?><br><small class="hip-ads-muted"><?php echo esc_html( $slot['placement_key'] ); ?></small></td><td><?php echo esc_html( implode( ', ', array_map( function( $size ) { return $size[0] . '×' . $size[1]; }, $slot['sizes'] ) ) ); ?></td><td><span class="hip-ads-status <?php echo esc_attr( $slot['status'] ); ?>"><?php echo esc_html( ucfirst( $slot['status'] ) ); ?></span></td></tr>
			<?php endforeach; ?>
			</tbody></table></div>
			<?php else : ?><div class="hip-ads-empty"><strong>Henüz reklam alanı yok.</strong>İlk alanı oluştur veya GAM CSV’si içe aktar.</div><?php endif; ?>
		</section>

		<aside class="hip-ads-grid">
			<div class="hip-ads-card">
				<div class="hip-ads-cardhead"><h2>Yayın kontrolü</h2><span class="hip-ads-status <?php echo $settings['ads_enabled'] ? 'good' : 'warning'; ?>"><?php echo $settings['ads_enabled'] ? 'Reklamlar açık' : 'Reklamlar kapalı'; ?></span></div>
				<dl class="hip-ads-kv"><dt>Property</dt><dd><?php echo esc_html( $settings['property_code'] ?: '—' ); ?></dd><dt>SRA</dt><dd><?php echo $settings['enable_single_request'] ? 'Açık' : 'Kapalı'; ?></dd><dt>Lazy load</dt><dd><?php echo $settings['enable_lazy_load'] ? 'Açık' : 'Kapalı'; ?></dd><dt>Cache</dt><dd><?php echo esc_html( $settings['cache_duration'] ); ?> sn</dd></dl>
				<hr><a class="hip-ads-btn" href="<?php echo esc_url( admin_url( 'admin.php?page=hip-ad-settings' ) ); ?>">Ayarları düzenle</a>
			</div>
			<div class="hip-ads-card">
				<div class="hip-ads-cardhead"><h2>Headless API</h2><span class="hip-ads-status good">v2 şema</span></div>
				<div class="hip-ads-endpoint"><span class="method">GET</span><code class="hip-ads-code"><?php echo esc_html( rest_url( 'hip-ads/v1/config' ) ); ?></code><button type="button" class="hip-ads-btn small" data-copy="<?php echo esc_attr( rest_url( 'hip-ads/v1/config' ) ); ?>">Kopyala</button></div>
				<div class="hip-ads-endpoint"><span class="method">GET</span><code class="hip-ads-code"><?php echo esc_html( rest_url( 'hip-ads/v1/slots' ) ); ?></code><button type="button" class="hip-ads-btn small" data-copy="<?php echo esc_attr( rest_url( 'hip-ads/v1/slots' ) ); ?>">Kopyala</button></div>
			</div>
		</aside>
	</div>
</div>
