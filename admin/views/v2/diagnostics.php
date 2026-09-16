<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$settings_errors = HIP_Ad_Schema::validate_settings( $settings );
$errors = count( array_filter( $issues, function( $issue ) { return 'error' === $issue['level']; } ) );
$warnings = count( array_filter( $issues, function( $issue ) { return 'warning' === $issue['level']; } ) );
$ready = ! $settings_errors->has_errors() && 0 === $errors;
$endpoints = array(
	array( 'GET', rest_url( 'hip-ads/v1/health' ), 'Health' ),
	array( 'GET', rest_url( 'hip-ads/v1/config' ), 'Config + slots' ),
	array( 'GET', rest_url( 'hip-ads/v1/slots' ), 'Active slots' ),
	array( 'GET', rest_url( 'hip-ads/v1/ads-txt' ), 'ads.txt source' ),
);
?>
<div class="wrap hip-ads">
	<div class="hip-ads-pagehead">
		<div class="hip-ads-brand"><span class="hip-ads-mark">hip.</span><div><h1>Tanılama</h1><p>Yayına çıkmadan önce veri bütünlüğünü, API sözleşmesini ve kritik reklam ayarlarını doğrula.</p></div></div>
		<div class="hip-ads-actions"><a class="hip-ads-btn" href="<?php echo esc_url( admin_url( 'admin.php?page=hip-ad-settings' ) ); ?>">Ayarlar</a><a class="hip-ads-btn" href="<?php echo esc_url( admin_url( 'admin.php?page=hip-ad-slots' ) ); ?>">Alanlar</a></div>
	</div>
	<?php include HIP_AD_MANAGER_PLUGIN_DIR . 'admin/views/v2/partials/flash.php'; ?>

	<div class="hip-ads-grid stats">
		<div class="hip-ads-card"><div class="hip-ads-stat-label">Durum</div><div class="hip-ads-stat-value" style="font-size:24px"><?php echo $ready ? 'Hazır' : 'Kontrol gerekli'; ?></div><div class="hip-ads-stat-foot"><span class="hip-ads-status <?php echo $ready ? 'good' : 'warning'; ?>"><?php echo $ready ? 'Kritik sorun yok' : 'Aşağıdaki maddeleri incele'; ?></span></div></div>
		<div class="hip-ads-card"><div class="hip-ads-stat-label">Aktif slot</div><div class="hip-ads-stat-value"><?php echo esc_html( $stats['active'] ); ?></div><div class="hip-ads-stat-foot"><?php echo esc_html( $stats['total'] ); ?> toplam kayıt</div></div>
		<div class="hip-ads-card"><div class="hip-ads-stat-label">Hata</div><div class="hip-ads-stat-value"><?php echo esc_html( $errors + count( $settings_errors->get_error_codes() ) ); ?></div><div class="hip-ads-stat-foot">Duplicate / invalid config dahil</div></div>
		<div class="hip-ads-card"><div class="hip-ads-stat-label">Cache version</div><div class="hip-ads-stat-value"><?php echo esc_html( HIP_Ad_Repository::cache_version() ); ?></div><div class="hip-ads-stat-foot">Her mutation’da otomatik artar</div></div>
	</div>

	<div class="hip-ads-grid two" style="margin-top:18px">
		<section class="hip-ads-card">
			<div class="hip-ads-cardhead"><div><h2>Kontroller</h2><p class="hip-ads-muted">Schema ve operasyon güvenliği</p></div><span class="hip-ads-chip invalid"><?php echo esc_html( $errors ); ?> hata · <?php echo esc_html( $warnings ); ?> uyarı</span></div>
			<?php foreach ( $settings_errors->get_error_messages() as $message ) : ?><div class="hip-ads-issue"><span class="hip-ads-status error">Ayar</span><strong>Global</strong><span><?php echo esc_html( $message ); ?></span><a href="<?php echo esc_url( admin_url( 'admin.php?page=hip-ad-settings' ) ); ?>">Düzelt</a></div><?php endforeach; ?>
			<?php foreach ( $issues as $issue ) : ?><div class="hip-ads-issue"><span class="hip-ads-status <?php echo 'error' === $issue['level'] ? 'error' : 'warning'; ?>"><?php echo esc_html( ucfirst( $issue['level'] ) ); ?></span><strong><?php echo esc_html( $issue['slot'] ); ?></strong><span><?php echo esc_html( $issue['message'] ); ?></span><a href="<?php echo esc_url( admin_url( 'admin.php?page=hip-ad-slot-new&id=' . absint( $issue['slot_id'] ) ) ); ?>">Düzenle</a></div><?php endforeach; ?>
			<?php if ( ! $settings_errors->has_errors() && ! $issues ) : ?><div class="hip-ads-empty"><strong>Her şey temiz görünüyor.</strong>Slot şeması, duplicate kontrolü ve global ayarlar geçerli.</div><?php endif; ?>
		</section>

		<aside class="hip-ads-grid">
			<section class="hip-ads-card"><div class="hip-ads-cardhead"><h2>Headless endpoint’ler</h2><span class="hip-ads-status good">Public read-only</span></div><?php foreach ( $endpoints as $endpoint ) : ?><div class="hip-ads-endpoint"><span class="method"><?php echo esc_html( $endpoint[0] ); ?></span><code class="hip-ads-code"><?php echo esc_html( $endpoint[1] ); ?></code><button class="hip-ads-btn small" type="button" data-copy="<?php echo esc_attr( $endpoint[1] ); ?>">Kopyala</button></div><?php endforeach; ?></section>
			<section class="hip-ads-card"><h2>Bakım araçları</h2><p class="hip-ads-muted">Normal kullanımda gerekmez; değişikliklerde cache otomatik invalid edilir.</p><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin:14px 0"><input type="hidden" name="action" value="hip_ad_clear_cache"><?php wp_nonce_field( 'hip_ad_clear_cache' ); ?><button class="hip-ads-btn" type="submit">Cache version artır</button></form><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="hip_ad_run_migration"><?php wp_nonce_field( 'hip_ad_run_migration' ); ?><button class="hip-ads-btn" type="submit">Legacy normalizasyonu yeniden çalıştır</button></form></section>
			<section class="hip-ads-card"><h2>Google politika notu</h2><div class="hip-ads-callout warning">Refresh açık slotlar GAM’de de refreshing inventory olarak declare edilmelidir. Time/event refresh 30 saniyenin altına ayarlanamaz.</div></section>
		</aside>
	</div>
</div>
