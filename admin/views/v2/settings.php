<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$global_targeting = $settings['global_targeting'] ? $settings['global_targeting'] : array( '' => '' );
?>
<div class="wrap hip-ads">
	<div class="hip-ads-pagehead">
		<div class="hip-ads-brand"><span class="hip-ads-mark">hip.</span><div><h1>Ayarlar</h1><p>Global GAM sözleşmesi, performans ayarları, targeting ve ads.txt tek yerde.</p></div></div>
		<div class="hip-ads-actions"><a class="hip-ads-btn" href="<?php echo esc_url( admin_url( 'admin.php?page=hip-ad-diagnostics' ) ); ?>">Tanılama</a></div>
	</div>
	<?php include HIP_AD_MANAGER_PLUGIN_DIR . 'admin/views/v2/partials/flash.php'; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-settings-form>
		<input type="hidden" name="action" value="hip_ad_save_settings"><?php wp_nonce_field( 'hip_ad_save_settings' ); ?>
		<div class="hip-ads-form-layout">
			<div class="hip-ads-form-main">
				<section class="hip-ads-section">
					<div class="hip-ads-section-head"><div><h2>Master kontrol</h2><p>Acil durumda tüm reklam teslimatını API seviyesinde tek tuşla kapat.</p></div></div>
					<div class="hip-ads-switch"><div><strong>Reklamları etkinleştir</strong><div class="hip-ads-help">Kapalıyken config yayınlanır fakat frontend’e aktif slot dönmez.</div></div><label class="hip-ads-toggle"><input type="checkbox" name="ads_enabled" value="1" <?php checked( $settings['ads_enabled'] ); ?>><span></span></label></div>
				</section>

				<section class="hip-ads-section">
					<div class="hip-ads-section-head"><div><h2>Google Ad Manager</h2><p>Frontend hiçbir ham script kodunu API’den almaz; yalnızca bu yapılandırmayı kullanır.</p></div></div>
					<div class="hip-ads-fields">
						<div class="hip-ads-field"><label>Network code *</label><input type="text" name="network_code" inputmode="numeric" value="<?php echo esc_attr( $settings['network_code'] ); ?>" placeholder="1234567"><span class="hip-ads-help">Yalnız rakam. GAM → Admin → Global settings.</span></div>
						<div class="hip-ads-field"><label>Property code *</label><input type="text" name="property_code" value="<?php echo esc_attr( $settings['property_code'] ); ?>" placeholder="hipinup"><span class="hip-ads-help">Global targeting ve çoklu yayın yönetimi için kısa site anahtarı.</span></div>
					</div>
					<div class="hip-ads-callout" style="margin-top:16px">GPT script’i frontend tarafından Google’ın resmi kaynağından yüklenmelidir: <code class="hip-ads-code">https://securepubads.g.doubleclick.net/tag/js/gpt.js</code>. Bu eklenti API’den çalıştırılabilir JavaScript taşımaz.</div>
				</section>

				<section class="hip-ads-section">
					<div class="hip-ads-section-head"><div><h2>GPT teslimat varsayılanları</h2><p>Modern GPT Config API ile eşleşen global ayarlar.</p></div></div>
					<div class="hip-ads-switch"><div><strong>Single Request Architecture</strong><div class="hip-ads-help">Aynı batch’teki slotları tek istekte toplar; roadblock / competitive exclusion için de önemlidir.</div></div><label class="hip-ads-toggle"><input type="checkbox" name="enable_single_request" value="1" <?php checked( $settings['enable_single_request'] ); ?>><span></span></label></div>
					<div class="hip-ads-switch"><div><strong>Lazy load</strong><div class="hip-ads-help">Viewport’a yaklaşmayan slotların fetch/render işlemini erteler.</div></div><label class="hip-ads-toggle"><input type="checkbox" name="enable_lazy_load" value="1" <?php checked( $settings['enable_lazy_load'] ); ?>><span></span></label></div>
					<div data-lazy-fields><div class="hip-ads-fields" style="margin-top:14px"><div class="hip-ads-field"><label>Fetch margin (%)</label><input type="number" name="lazy_fetch_margin" min="0" max="2000" value="<?php echo esc_attr( $settings['lazy_fetch_margin'] ); ?>"></div><div class="hip-ads-field"><label>Render margin (%)</label><input type="number" name="lazy_render_margin" min="0" max="2000" value="<?php echo esc_attr( $settings['lazy_render_margin'] ); ?>"></div><div class="hip-ads-field"><label>Mobile scaling</label><input type="number" name="lazy_mobile_scaling" min="0.1" max="5" step="0.1" value="<?php echo esc_attr( $settings['lazy_mobile_scaling'] ); ?>"></div></div></div>
					<div class="hip-ads-switch"><div><strong>Collapse empty slots</strong><div class="hip-ads-help">No-fill sonrasında boş reklam alanını kapatmayı frontend’e bildirir.</div></div><label class="hip-ads-toggle"><input type="checkbox" name="collapse_empty" value="1" <?php checked( $settings['collapse_empty'] ); ?>><span></span></label></div>
				</section>

				<section class="hip-ads-section">
					<div class="hip-ads-section-head"><div><h2>Global targeting</h2><p>Her reklam isteğine uygulanacak GAM key-value çiftleri.</p></div><button class="hip-ads-btn small" type="button" data-add-row="#hip-global-target-rows" data-template="#hip-global-target-template">+ Key/value</button></div>
					<div id="hip-global-target-rows" class="hip-ads-repeater"><?php foreach ( $global_targeting as $key => $value ) : ?><div class="hip-ads-repeat-row" data-repeat-row><input name="global_target_key[]" type="text" value="<?php echo esc_attr( $key ); ?>" placeholder="site"><input name="global_target_value[]" type="text" value="<?php echo esc_attr( is_array( $value ) ? implode( ', ', $value ) : $value ); ?>" placeholder="hipinup"><button class="hip-ads-iconbtn" type="button" data-remove-row>×</button></div><?php endforeach; ?></div>
				</section>

				<section class="hip-ads-section">
					<div class="hip-ads-section-head"><div><h2>Cache & debug</h2><p>Headless API response süresi ve tanılama görünürlüğü.</p></div></div>
					<div class="hip-ads-fields"><div class="hip-ads-field"><label>API cache süresi (sn)</label><input type="number" name="cache_duration" min="0" max="86400" step="30" value="<?php echo esc_attr( $settings['cache_duration'] ); ?>"><span class="hip-ads-help">0 = cache kapalı. Değişikliklerde cache version otomatik artar.</span></div></div>
					<div class="hip-ads-switch"><div><strong>Debug mode</strong><div class="hip-ads-help">API response’a yalnızca güvenli cache bilgisi ekler. Production’da kapalı tut.</div></div><label class="hip-ads-toggle"><input type="checkbox" name="debug_mode" value="1" <?php checked( $settings['debug_mode'] ); ?>><span></span></label></div>
				</section>

				<section class="hip-ads-section">
					<div class="hip-ads-section-head"><div><h2>ads.txt</h2><p>Headless frontend’in <code>/ads.txt</code> route’u bu içeriği API’den okuyabilir.</p></div></div>
					<textarea class="large-text code" name="ads_txt_content" rows="10" placeholder="google.com, pub-XXXXXXXXXXXXXXXX, DIRECT, f08c47fec0942fa0"><?php echo esc_textarea( $ads_txt ); ?></textarea>
					<p class="hip-ads-help">API: <code class="hip-ads-code"><?php echo esc_html( rest_url( 'hip-ads/v1/ads-txt' ) ); ?></code></p>
				</section>
			</div>

			<aside class="hip-ads-form-side">
				<section class="hip-ads-card"><div class="hip-ads-cardhead"><h2>Yayın özeti</h2><span class="hip-ads-status <?php echo $settings['ads_enabled'] ? 'good' : 'warning'; ?>"><?php echo $settings['ads_enabled'] ? 'Açık' : 'Kapalı'; ?></span></div><dl class="hip-ads-kv"><dt>Network</dt><dd><?php echo esc_html( $settings['network_code'] ?: '—' ); ?></dd><dt>Property</dt><dd><?php echo esc_html( $settings['property_code'] ?: '—' ); ?></dd><dt>Schema</dt><dd>v<?php echo esc_html( HIP_Ad_Schema::VERSION ); ?></dd><dt>Cache version</dt><dd><?php echo esc_html( HIP_Ad_Repository::cache_version() ); ?></dd></dl><div class="hip-ads-savebar" style="margin-top:18px"><button class="hip-ads-btn primary" type="submit">Ayarları kaydet</button></div></section>
				<section class="hip-ads-card"><h2>Cache</h2><p class="hip-ads-muted">Slot veya ayar kaydında otomatik invalidation yapılır. Gerekirse manuel temizleyebilirsin.</p><a class="hip-ads-btn" href="<?php echo esc_url( admin_url( 'admin.php?page=hip-ad-diagnostics' ) ); ?>">Tanılamaya git</a></section>
			</aside>
		</div>
	</form>
	<template id="hip-global-target-template"><div class="hip-ads-repeat-row" data-repeat-row><input name="global_target_key[]" type="text" placeholder="key"><input name="global_target_value[]" type="text" placeholder="value veya value1, value2"><button class="hip-ads-iconbtn" type="button" data-remove-row>×</button></div></template>
</div>
