<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$groups = HIP_Ad_Schema::placement_groups();
$devices = HIP_Ad_Schema::devices();
$page_types = HIP_Ad_Schema::page_types();
$statuses = HIP_Ad_Schema::statuses();
$refresh_triggers = HIP_Ad_Schema::refresh_triggers();
$sizes = $slot['sizes'] ? $slot['sizes'] : array( array( '', '' ) );
$mappings = $slot['size_mappings'] ? $slot['size_mappings'] : array();
$targeting = $slot['targeting'] ? $slot['targeting'] : array( '' => '' );
$schedule_start = $slot['schedule']['start'] ? wp_date( 'Y-m-d\TH:i', strtotime( $slot['schedule']['start'] ) ) : '';
$schedule_end = $slot['schedule']['end'] ? wp_date( 'Y-m-d\TH:i', strtotime( $slot['schedule']['end'] ) ) : '';
$is_new = empty( $slot['id'] );
?>
<div class="wrap hip-ads">
	<div class="hip-ads-pagehead">
		<div class="hip-ads-brand"><span class="hip-ads-mark">hip.</span><div><h1><?php echo $is_new ? 'Yeni Reklam Alanı' : esc_html( $slot['name'] ); ?></h1><p>GAM envanterini frontend placement key’i ve teslimat kurallarıyla eşleştir.</p></div></div>
		<div class="hip-ads-actions"><a class="hip-ads-btn" href="<?php echo esc_url( admin_url( 'admin.php?page=hip-ad-slots' ) ); ?>">← Listeye dön</a></div>
	</div>
	<?php include HIP_AD_MANAGER_PLUGIN_DIR . 'admin/views/v2/partials/flash.php'; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-slot-form>
		<input type="hidden" name="action" value="hip_ad_save_slot">
		<input type="hidden" name="slot_id" value="<?php echo esc_attr( $slot['id'] ); ?>">
		<?php wp_nonce_field( 'hip_ad_save_slot' ); ?>

		<div class="hip-ads-form-layout">
			<div class="hip-ads-form-main">
				<section class="hip-ads-section">
					<div class="hip-ads-section-head"><div><h2>Kimlik & GAM</h2><p>Frontend anahtarı ile Google Ad Manager yolunu birbirinden ayrı ve net tut.</p></div></div>
					<div class="hip-ads-fields">
						<div class="hip-ads-field full"><label for="hip-slot-name">Alan adı *</label><input id="hip-slot-name" name="name" type="text" value="<?php echo esc_attr( $slot['name'] ); ?>" placeholder="Örn. Article Inline 1" required><span class="hip-ads-help">AdOps panelinde göreceğin açıklayıcı ad.</span></div>
						<div class="hip-ads-field"><label for="hip-slot-key">Stable slot key *</label><input id="hip-slot-key" name="key" type="text" value="<?php echo esc_attr( $slot['key'] ); ?>" placeholder="article_inline_1" required><span class="hip-ads-help">Frontend component’inin çağırdığı kalıcı anahtar. Sonradan değiştirmemek en sağlıklısıdır.</span></div>
						<div class="hip-ads-field"><label for="hip-placement-key">Placement key *</label><input id="hip-placement-key" name="placement_key" type="text" value="<?php echo esc_attr( $slot['placement_key'] ); ?>" placeholder="article_inline_1" required><span class="hip-ads-help">Aynı placement’a birden fazla slot varyantı bağlamak istersen slot key’den farklı olabilir.</span></div>
						<div class="hip-ads-field full"><label for="hip-ad-unit-path">GAM ad unit path *</label><input id="hip-ad-unit-path" name="ad_unit_path" type="text" value="<?php echo esc_attr( $slot['ad_unit_path'] ); ?>" placeholder="/1234567/hipinup/article_inline_1" required><span class="hip-ads-help">Başında / olacak şekilde tam Google Ad Manager ad unit yolu.</span></div>
						<div class="hip-ads-field"><label>GAM inventory ID</label><input name="inventory_id" type="text" value="<?php echo esc_attr( $slot['inventory_id'] ); ?>" placeholder="Opsiyonel numeric ID"><span class="hip-ads-help">CSV import / envanter eşleştirmesi için opsiyonel.</span></div>
						<div class="hip-ads-field"><label>Yerleşim grubu</label><select name="placement_group"><?php foreach ( $groups as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $slot['placement_group'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></div>
					</div>
				</section>

				<section class="hip-ads-section">
					<div class="hip-ads-section-head"><div><h2>Boyutlar & responsive mapping</h2><p>Layout shift oluşmaması için gerçek creative boyutlarını ve breakpoint davranışını tanımla.</p></div><button class="hip-ads-btn small" type="button" data-add-row="#hip-size-rows" data-template="#hip-size-template">+ Boyut ekle</button></div>
					<div id="hip-size-rows" class="hip-ads-repeater">
					<?php foreach ( $sizes as $size ) : ?><div class="hip-ads-repeat-row sizes" data-repeat-row data-size-row><input name="size_width[]" type="number" min="1" max="5000" value="<?php echo esc_attr( $size[0] ); ?>" placeholder="Genişlik"><input name="size_height[]" type="number" min="1" max="5000" value="<?php echo esc_attr( $size[1] ); ?>" placeholder="Yükseklik"><button class="hip-ads-iconbtn" type="button" data-remove-row aria-label="Satırı kaldır">×</button></div><?php endforeach; ?>
					</div>
					<hr>
					<div class="hip-ads-cardhead"><div><h3>Viewport mappings</h3><p class="hip-ads-muted">Örn. 1024px → 970x250, 970x90, 728x90. Boş bırakırsan ana boyut listesi kullanılır.</p></div><button class="hip-ads-btn small" type="button" data-add-row="#hip-mapping-rows" data-template="#hip-mapping-template">+ Breakpoint</button></div>
					<div id="hip-mapping-rows" class="hip-ads-repeater">
					<?php foreach ( $mappings as $mapping ) : ?><div class="hip-ads-repeat-row mapping" data-repeat-row><input name="mapping_viewport[]" type="number" min="0" value="<?php echo esc_attr( $mapping['viewport'][0] ); ?>" placeholder="Min px"><input name="mapping_sizes[]" type="text" value="<?php echo esc_attr( implode( ', ', array_map( function( $size ) { return $size[0] . 'x' . $size[1]; }, $mapping['sizes'] ) ) ); ?>" placeholder="970x250, 728x90"><button class="hip-ads-iconbtn" type="button" data-remove-row>×</button></div><?php endforeach; ?>
					</div>
				</section>

				<section class="hip-ads-section">
					<div class="hip-ads-section-head"><div><h2>Teslimat kuralları</h2><p>Bu alanın hangi cihaz ve sayfa bağlamlarında frontend’e gönderileceğini belirle.</p></div></div>
					<div class="hip-ads-fields">
						<div class="hip-ads-field"><label>Cihaz</label><select name="device"><?php foreach ( $devices as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $slot['device'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></div>
						<div class="hip-ads-field"><label>Kategori slug’ları</label><input name="categories" type="text" value="<?php echo esc_attr( implode( ', ', $slot['categories'] ) ); ?>" placeholder="moda, seyahat"><span class="hip-ads-help">Boşsa tüm kategoriler. Virgülle ayır.</span></div>
						<div class="hip-ads-field full"><span class="hip-ads-label">Sayfa tipleri</span><div class="hip-ads-checkgrid"><?php foreach ( $page_types as $key => $label ) : ?><label class="hip-ads-check"><input type="checkbox" name="page_types[]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $slot['page_types'], true ) ); ?>><?php echo esc_html( $label ); ?></label><?php endforeach; ?></div></div>
					</div>
					<hr>
					<div class="hip-ads-switch"><div><strong>Lazy load</strong><div class="hip-ads-help">Slot yaklaşana kadar reklam isteğini ertele.</div></div><label class="hip-ads-toggle"><input type="checkbox" name="lazy_load" value="1" <?php checked( $slot['lazy_load'] ); ?>><span></span></label></div>
					<div class="hip-ads-switch"><div><strong>Boş slotu collapse et</strong><div class="hip-ads-help">No-fill durumunda ayrılan alanı frontend’in kapatmasına izin verir.</div></div><label class="hip-ads-toggle"><input type="checkbox" name="collapse_empty" value="1" <?php checked( $slot['collapse_empty'] ); ?>><span></span></label></div>
					<hr>
					<div class="hip-ads-fields"><div class="hip-ads-field"><label>Desktop min-height</label><input type="number" name="min_height_desktop" min="0" value="<?php echo esc_attr( $slot['min_height']['desktop'] ); ?>"></div><div class="hip-ads-field"><label>Tablet min-height</label><input type="number" name="min_height_tablet" min="0" value="<?php echo esc_attr( $slot['min_height']['tablet'] ); ?>"></div><div class="hip-ads-field"><label>Mobile min-height</label><input type="number" name="min_height_mobile" min="0" value="<?php echo esc_attr( $slot['min_height']['mobile'] ); ?>"></div></div>
				</section>

				<section class="hip-ads-section">
					<div class="hip-ads-section-head"><div><h2>Slot targeting</h2><p>GAM key-value targeting. Birden fazla değeri virgülle ayırabilirsin.</p></div><button class="hip-ads-btn small" type="button" data-add-row="#hip-target-rows" data-template="#hip-target-template">+ Key/value</button></div>
					<div id="hip-target-rows" class="hip-ads-repeater"><?php foreach ( $targeting as $key => $value ) : ?><div class="hip-ads-repeat-row" data-repeat-row><input name="target_key[]" type="text" value="<?php echo esc_attr( $key ); ?>" placeholder="key"><input name="target_value[]" type="text" value="<?php echo esc_attr( is_array( $value ) ? implode( ', ', $value ) : $value ); ?>" placeholder="value veya value1, value2"><button class="hip-ads-iconbtn" type="button" data-remove-row>×</button></div><?php endforeach; ?></div>
				</section>

				<section class="hip-ads-section">
					<div class="hip-ads-section-head"><div><h2>Zamanlama & refresh</h2><p>Refresh kullanacaksan GAM inventory rules tarafındaki refresh declaration ile aynı davranışı tanımlamalısın.</p></div></div>
					<div class="hip-ads-fields"><div class="hip-ads-field"><label>Başlangıç</label><input name="schedule_start" type="datetime-local" value="<?php echo esc_attr( $schedule_start ); ?>"></div><div class="hip-ads-field"><label>Bitiş</label><input name="schedule_end" type="datetime-local" value="<?php echo esc_attr( $schedule_end ); ?>"></div></div>
					<hr>
					<div class="hip-ads-switch"><div><strong>Ad refresh</strong><div class="hip-ads-help">Varsayılan kapalı. Sadece gerçekten gerekli inventory’de aç.</div></div><label class="hip-ads-toggle"><input type="checkbox" name="refresh_enabled" value="1" <?php checked( $slot['refresh']['enabled'] ); ?>><span></span></label></div>
					<div data-refresh-fields>
						<div class="hip-ads-callout warning" style="margin:12px 0">Time/event refresh minimum 30 saniyedir. Google Ad Manager’da ilgili inventory için refresh declaration ayrıca yapılmalıdır.</div>
						<div class="hip-ads-fields"><div class="hip-ads-field"><label>Tetikleyici</label><select name="refresh_trigger"><?php foreach ( $refresh_triggers as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $slot['refresh']['trigger'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></div><div class="hip-ads-field"><label>Minimum interval (sn)</label><input name="refresh_interval" type="number" min="0" value="<?php echo esc_attr( $slot['refresh']['interval'] ); ?>"></div><div class="hip-ads-field"><label>Maks. refresh</label><input name="max_refreshes" type="number" min="0" max="100" value="<?php echo esc_attr( $slot['refresh']['max_refreshes'] ); ?>"><span class="hip-ads-help">0 = oturum boyunca limitsiz; dikkatli kullan.</span></div></div>
						<div class="hip-ads-switch"><div><strong>Yalnız görünürken refresh</strong></div><label class="hip-ads-toggle"><input type="checkbox" name="refresh_visible" value="1" <?php checked( $slot['refresh']['require_visible'] ); ?>><span></span></label></div>
						<div class="hip-ads-switch"><div><strong>Tab gizliyken duraklat</strong></div><label class="hip-ads-toggle"><input type="checkbox" name="refresh_pause_hidden" value="1" <?php checked( $slot['refresh']['pause_when_hidden'] ); ?>><span></span></label></div>
					</div>
				</section>

				<section class="hip-ads-section"><div class="hip-ads-section-head"><div><h2>Operasyon notları</h2><p>Frontend’e gönderilmez; yalnızca ekip içi kullanım içindir.</p></div></div><textarea name="notes" rows="4" class="large-text" placeholder="Bu slotla ilgili notlar…"><?php echo esc_textarea( $slot['notes'] ); ?></textarea></section>
			</div>

			<aside class="hip-ads-form-side">
				<section class="hip-ads-card">
					<div class="hip-ads-cardhead"><h2>Yayın</h2><span class="hip-ads-status <?php echo esc_attr( $slot['status'] ); ?>"><?php echo esc_html( isset( $statuses[ $slot['status'] ] ) ? $statuses[ $slot['status'] ] : $slot['status'] ); ?></span></div>
					<div class="hip-ads-field"><label>Durum</label><select name="status"><?php foreach ( $statuses as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $slot['status'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></div>
					<div class="hip-ads-field" style="margin-top:12px"><label>Öncelik (1–100)</label><input name="priority" type="number" min="1" max="100" value="<?php echo esc_attr( $slot['priority'] ); ?>"><span class="hip-ads-help">Düşük sayı daha yüksek öncelik.</span></div>
					<div class="hip-ads-savebar" style="margin-top:18px"><button class="hip-ads-btn primary" type="submit"><?php echo $is_new ? 'Reklam alanını oluştur' : 'Değişiklikleri kaydet'; ?></button></div>
				</section>
				<section class="hip-ads-card"><h2>Frontend önizleme</h2><div class="hip-ads-preview" data-slot-preview><div><strong data-preview-key><?php echo esc_html( $slot['key'] ?: '—' ); ?></strong><small data-preview-placement><?php echo esc_html( $slot['placement_key'] ?: '—' ); ?></small><br><code class="hip-ads-code" data-preview-path><?php echo esc_html( $slot['ad_unit_path'] ?: '—' ); ?></code><br><small data-preview-sizes><?php echo esc_html( $slot['sizes'] ? implode( ' · ', array_map( function( $size ) { return $size[0] . '×' . $size[1]; }, $slot['sizes'] ) ) : 'Boyut eklenmedi' ); ?></small></div></div></section>
				<?php if ( ! $is_new ) : ?><section class="hip-ads-card hip-ads-danger-zone"><h3>Tehlikeli alan</h3><p class="hip-ads-muted">Bu kayıt frontend API’den kaldırılır ve Çöp Kutusu’na taşınır.</p></section><?php endif; ?>
			</aside>
		</div>
	</form>

	<template id="hip-size-template"><div class="hip-ads-repeat-row sizes" data-repeat-row data-size-row><input name="size_width[]" type="number" min="1" max="5000" placeholder="Genişlik"><input name="size_height[]" type="number" min="1" max="5000" placeholder="Yükseklik"><button class="hip-ads-iconbtn" type="button" data-remove-row>×</button></div></template>
	<template id="hip-mapping-template"><div class="hip-ads-repeat-row mapping" data-repeat-row><input name="mapping_viewport[]" type="number" min="0" placeholder="Min px"><input name="mapping_sizes[]" type="text" placeholder="970x250, 728x90"><button class="hip-ads-iconbtn" type="button" data-remove-row>×</button></div></template>
	<template id="hip-target-template"><div class="hip-ads-repeat-row" data-repeat-row><input name="target_key[]" type="text" placeholder="key"><input name="target_value[]" type="text" placeholder="value veya value1, value2"><button class="hip-ads-iconbtn" type="button" data-remove-row>×</button></div></template>
</div>
