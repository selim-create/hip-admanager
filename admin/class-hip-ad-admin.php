<?php
/**
 * HIP Ads v2 admin application.
 *
 * @package HIP_Ad_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HIP_Ad_Admin {

	const CAPABILITY = 'manage_options';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'admin_post_hip_ad_save_slot', array( $this, 'save_slot' ) );
		add_action( 'admin_post_hip_ad_delete_slot', array( $this, 'delete_slot' ) );
		add_action( 'admin_post_hip_ad_duplicate_slot', array( $this, 'duplicate_slot' ) );
		add_action( 'admin_post_hip_ad_save_settings', array( $this, 'save_settings' ) );
		add_action( 'admin_post_hip_ad_clear_cache', array( $this, 'clear_cache' ) );
		add_action( 'admin_post_hip_ad_import_upload', array( $this, 'import_upload' ) );
		add_action( 'admin_post_hip_ad_import_confirm', array( $this, 'import_confirm' ) );
		add_action( 'admin_post_hip_ad_import_cancel', array( $this, 'import_cancel' ) );
		add_action( 'admin_post_hip_ad_export_json', array( $this, 'export_json' ) );
		add_action( 'admin_post_hip_ad_run_migration', array( $this, 'run_migration' ) );
	}

	public function add_admin_menu() {
		add_menu_page(
			__( 'HIP Ads', 'hip-admanager' ),
			__( 'HIP Ads', 'hip-admanager' ),
			self::CAPABILITY,
			'hip-ad-manager',
			array( $this, 'render_dashboard_page' ),
			'dashicons-megaphone',
			30
		);
		add_submenu_page( 'hip-ad-manager', __( 'Genel Bakış', 'hip-admanager' ), __( 'Genel Bakış', 'hip-admanager' ), self::CAPABILITY, 'hip-ad-manager', array( $this, 'render_dashboard_page' ) );
		add_submenu_page( 'hip-ad-manager', __( 'Reklam Alanları', 'hip-admanager' ), __( 'Reklam Alanları', 'hip-admanager' ), self::CAPABILITY, 'hip-ad-slots', array( $this, 'render_slots_page' ) );
		add_submenu_page( 'hip-ad-manager', __( 'Yeni Alan', 'hip-admanager' ), __( 'Yeni Alan Ekle', 'hip-admanager' ), self::CAPABILITY, 'hip-ad-slot-new', array( $this, 'render_slot_editor_page' ) );
		add_submenu_page( 'hip-ad-manager', __( 'İçe / Dışa Aktar', 'hip-admanager' ), __( 'İçe / Dışa Aktar', 'hip-admanager' ), self::CAPABILITY, 'hip-ad-import', array( $this, 'render_import_page' ) );
		add_submenu_page( 'hip-ad-manager', __( 'Ayarlar', 'hip-admanager' ), __( 'Ayarlar', 'hip-admanager' ), self::CAPABILITY, 'hip-ad-settings', array( $this, 'render_settings_page' ) );
		add_submenu_page( 'hip-ad-manager', __( 'Tanılama', 'hip-admanager' ), __( 'Tanılama', 'hip-admanager' ), self::CAPABILITY, 'hip-ad-diagnostics', array( $this, 'render_diagnostics_page' ) );
	}

	public function enqueue_admin_assets() {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		if ( 0 !== strpos( $page, 'hip-ad' ) ) {
			return;
		}
		wp_enqueue_style( 'hip-ad-admin', HIP_AD_MANAGER_PLUGIN_URL . 'admin/assets/css/admin.css', array(), HIP_AD_MANAGER_VERSION );
		wp_enqueue_script( 'hip-ad-admin', HIP_AD_MANAGER_PLUGIN_URL . 'admin/assets/js/admin.js', array(), HIP_AD_MANAGER_VERSION, true );
		wp_localize_script( 'hip-ad-admin', 'HIPAdsAdmin', array(
			'confirmDelete'    => __( 'Bu reklam alanını Çöp Kutusu’na taşımak istediğinizden emin misiniz?', 'hip-admanager' ),
			'confirmImport'    => __( 'Önizlemedeki değişiklikleri uygulamak istiyor musunuz?', 'hip-admanager' ),
			'refreshMinNotice' => __( 'Zaman/event bazlı yenileme en az 30 saniye olmalıdır.', 'hip-admanager' ),
		) );
	}

	public function render_dashboard_page() {
		$this->guard();
		$stats = HIP_Ad_Repository::stats();
		$settings = HIP_Ad_Settings::get_all();
		$issues = HIP_Ad_Repository::diagnostics();
		$slots = array_slice( HIP_Ad_Repository::all(), 0, 6 );
		$flash = $this->pull_flash();
		include HIP_AD_MANAGER_PLUGIN_DIR . 'admin/views/v2/dashboard.php';
	}

	public function render_slots_page() {
		$this->guard();
		$filters = array(
			'search'          => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '',
			'status'          => isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '',
			'placement_group' => isset( $_GET['placement_group'] ) ? sanitize_key( wp_unslash( $_GET['placement_group'] ) ) : '',
		);
		$slots = HIP_Ad_Repository::all( array_filter( $filters ) );
		$flash = $this->pull_flash();
		include HIP_AD_MANAGER_PLUGIN_DIR . 'admin/views/v2/slots.php';
	}

	public function render_slot_editor_page() {
		$this->guard();
		$post_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		$slot = $post_id ? HIP_Ad_Repository::get( $post_id ) : HIP_Ad_Schema::slot_defaults();
		if ( $post_id && ! $slot ) {
			$this->set_flash( 'error', __( 'Reklam alanı bulunamadı.', 'hip-admanager' ) );
			wp_safe_redirect( admin_url( 'admin.php?page=hip-ad-slots' ) );
			exit;
		}
		$flash = $this->pull_flash();
		include HIP_AD_MANAGER_PLUGIN_DIR . 'admin/views/v2/slot-edit.php';
	}

	public function render_settings_page() {
		$this->guard();
		$settings = HIP_Ad_Settings::get_all();
		$ads_txt = HIP_Ad_Settings::get_ads_txt();
		$flash = $this->pull_flash();
		include HIP_AD_MANAGER_PLUGIN_DIR . 'admin/views/v2/settings.php';
	}

	public function render_import_page() {
		$this->guard();
		$preview = get_transient( $this->import_key() );
		$results = get_transient( $this->import_result_key() );
		$flash = $this->pull_flash();
		include HIP_AD_MANAGER_PLUGIN_DIR . 'admin/views/v2/import-export.php';
	}

	public function render_diagnostics_page() {
		$this->guard();
		$settings = HIP_Ad_Settings::get_all();
		$stats = HIP_Ad_Repository::stats();
		$issues = HIP_Ad_Repository::diagnostics();
		$flash = $this->pull_flash();
		include HIP_AD_MANAGER_PLUGIN_DIR . 'admin/views/v2/diagnostics.php';
	}

	public function save_slot() {
		$this->guard_action( 'hip_ad_save_slot' );
		$post_id = isset( $_POST['slot_id'] ) ? absint( $_POST['slot_id'] ) : 0;
		$input = array(
			'id'              => $post_id,
			'name'            => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
			'key'             => isset( $_POST['key'] ) ? wp_unslash( $_POST['key'] ) : '',
			'inventory_id'    => isset( $_POST['inventory_id'] ) ? sanitize_text_field( wp_unslash( $_POST['inventory_id'] ) ) : '',
			'ad_unit_path'    => isset( $_POST['ad_unit_path'] ) ? wp_unslash( $_POST['ad_unit_path'] ) : '',
			'placement_group' => isset( $_POST['placement_group'] ) ? wp_unslash( $_POST['placement_group'] ) : 'content',
			'placement_key'   => isset( $_POST['placement_key'] ) ? wp_unslash( $_POST['placement_key'] ) : '',
			'device'          => isset( $_POST['device'] ) ? wp_unslash( $_POST['device'] ) : 'all',
			'status'          => isset( $_POST['status'] ) ? wp_unslash( $_POST['status'] ) : 'active',
			'priority'        => isset( $_POST['priority'] ) ? absint( $_POST['priority'] ) : 10,
			'sizes'           => $this->parse_sizes_form(),
			'size_mappings'   => $this->parse_mappings_form(),
			'targeting'       => $this->parse_targeting_form( 'target_key', 'target_value' ),
			'page_types'      => isset( $_POST['page_types'] ) ? (array) wp_unslash( $_POST['page_types'] ) : array( 'all' ),
			'categories'      => isset( $_POST['categories'] ) ? wp_unslash( $_POST['categories'] ) : '',
			'lazy_load'       => isset( $_POST['lazy_load'] ),
			'collapse_empty'  => isset( $_POST['collapse_empty'] ),
			'min_height'      => array(
				'desktop' => isset( $_POST['min_height_desktop'] ) ? absint( $_POST['min_height_desktop'] ) : 0,
				'tablet'  => isset( $_POST['min_height_tablet'] ) ? absint( $_POST['min_height_tablet'] ) : 0,
				'mobile'  => isset( $_POST['min_height_mobile'] ) ? absint( $_POST['min_height_mobile'] ) : 0,
			),
			'schedule'        => array(
				'start' => isset( $_POST['schedule_start'] ) ? wp_unslash( $_POST['schedule_start'] ) : '',
				'end'   => isset( $_POST['schedule_end'] ) ? wp_unslash( $_POST['schedule_end'] ) : '',
			),
			'refresh'         => array(
				'enabled'           => isset( $_POST['refresh_enabled'] ),
				'trigger'           => isset( $_POST['refresh_trigger'] ) ? wp_unslash( $_POST['refresh_trigger'] ) : 'time',
				'interval'          => isset( $_POST['refresh_interval'] ) ? absint( $_POST['refresh_interval'] ) : 30,
				'max_refreshes'     => isset( $_POST['max_refreshes'] ) ? absint( $_POST['max_refreshes'] ) : 0,
				'require_visible'   => isset( $_POST['refresh_visible'] ),
				'pause_when_hidden' => isset( $_POST['refresh_pause_hidden'] ),
			),
			'notes'           => isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : '',
		);

		$saved = HIP_Ad_Repository::save( $input, $post_id );
		if ( is_wp_error( $saved ) ) {
			$this->set_flash( 'error', implode( ' ', $saved->get_error_messages() ) );
			$url = admin_url( 'admin.php?page=hip-ad-slot-new' . ( $post_id ? '&id=' . $post_id : '' ) );
			wp_safe_redirect( $url );
			exit;
		}
		$this->set_flash( 'success', $post_id ? __( 'Reklam alanı güncellendi.', 'hip-admanager' ) : __( 'Reklam alanı oluşturuldu.', 'hip-admanager' ) );
		wp_safe_redirect( admin_url( 'admin.php?page=hip-ad-slot-new&id=' . absint( $saved['id'] ) ) );
		exit;
	}

	public function delete_slot() {
		$this->guard_action( 'hip_ad_delete_slot' );
		$post_id = isset( $_POST['slot_id'] ) ? absint( $_POST['slot_id'] ) : 0;
		$result = HIP_Ad_Repository::trash( $post_id );
		$this->set_flash( is_wp_error( $result ) ? 'error' : 'success', is_wp_error( $result ) ? $result->get_error_message() : __( 'Reklam alanı Çöp Kutusu’na taşındı.', 'hip-admanager' ) );
		wp_safe_redirect( admin_url( 'admin.php?page=hip-ad-slots' ) );
		exit;
	}

	public function duplicate_slot() {
		$this->guard_action( 'hip_ad_duplicate_slot' );
		$post_id = isset( $_POST['slot_id'] ) ? absint( $_POST['slot_id'] ) : 0;
		$result = HIP_Ad_Repository::duplicate( $post_id );
		if ( is_wp_error( $result ) ) {
			$this->set_flash( 'error', $result->get_error_message() );
			wp_safe_redirect( admin_url( 'admin.php?page=hip-ad-slots' ) );
			exit;
		}
		$this->set_flash( 'success', __( 'Kopya reklam alanı oluşturuldu; GAM yolunu kontrol edip kaydedin.', 'hip-admanager' ) );
		wp_safe_redirect( admin_url( 'admin.php?page=hip-ad-slot-new&id=' . absint( $result['id'] ) ) );
		exit;
	}

	public function save_settings() {
		$this->guard_action( 'hip_ad_save_settings' );
		$input = array(
			'ads_enabled'           => isset( $_POST['ads_enabled'] ),
			'network_code'          => isset( $_POST['network_code'] ) ? wp_unslash( $_POST['network_code'] ) : '',
			'property_code'         => isset( $_POST['property_code'] ) ? wp_unslash( $_POST['property_code'] ) : '',
			'enable_single_request' => isset( $_POST['enable_single_request'] ),
			'enable_lazy_load'      => isset( $_POST['enable_lazy_load'] ),
			'collapse_empty'        => isset( $_POST['collapse_empty'] ),
			'lazy_fetch_margin'     => isset( $_POST['lazy_fetch_margin'] ) ? absint( $_POST['lazy_fetch_margin'] ) : 500,
			'lazy_render_margin'    => isset( $_POST['lazy_render_margin'] ) ? absint( $_POST['lazy_render_margin'] ) : 200,
			'lazy_mobile_scaling'   => isset( $_POST['lazy_mobile_scaling'] ) ? (float) $_POST['lazy_mobile_scaling'] : 2,
			'global_targeting'      => $this->parse_targeting_form( 'global_target_key', 'global_target_value' ),
			'cache_duration'        => isset( $_POST['cache_duration'] ) ? absint( $_POST['cache_duration'] ) : 300,
			'debug_mode'            => isset( $_POST['debug_mode'] ),
		);
		$result = HIP_Ad_Settings::update( $input );
		if ( is_wp_error( $result ) ) {
			$this->set_flash( 'error', implode( ' ', $result->get_error_messages() ) );
		} else {
			HIP_Ad_Settings::update_ads_txt( isset( $_POST['ads_txt_content'] ) ? wp_unslash( $_POST['ads_txt_content'] ) : '' );
			$this->set_flash( 'success', __( 'Ayarlar kaydedildi.', 'hip-admanager' ) );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=hip-ad-settings' ) );
		exit;
	}

	public function clear_cache() {
		$this->guard_action( 'hip_ad_clear_cache' );
		HIP_Ad_Repository::bump_cache_version();
		$this->set_flash( 'success', __( 'Reklam cache’i temizlendi.', 'hip-admanager' ) );
		wp_safe_redirect( wp_get_referer() ?: admin_url( 'admin.php?page=hip-ad-diagnostics' ) );
		exit;
	}

	public function import_upload() {
		$this->guard_action( 'hip_ad_import_upload' );
		if ( empty( $_FILES['csv_file'] ) || UPLOAD_ERR_OK !== (int) $_FILES['csv_file']['error'] ) {
			$this->set_flash( 'error', __( 'CSV yüklenemedi.', 'hip-admanager' ) );
			$this->redirect_import();
		}
		if ( (int) $_FILES['csv_file']['size'] > 5 * MB_IN_BYTES ) {
			$this->set_flash( 'error', __( 'CSV dosyası 5 MB’tan büyük olamaz.', 'hip-admanager' ) );
			$this->redirect_import();
		}
		$extension = strtolower( pathinfo( sanitize_file_name( $_FILES['csv_file']['name'] ), PATHINFO_EXTENSION ) );
		if ( 'csv' !== $extension ) {
			$this->set_flash( 'error', __( 'Yalnızca .csv dosyaları kabul edilir.', 'hip-admanager' ) );
			$this->redirect_import();
		}
		$importer = new HIP_Ad_Importer();
		$rows = $importer->parse_csv( $_FILES['csv_file']['tmp_name'] );
		if ( is_wp_error( $rows ) ) {
			$this->set_flash( 'error', $rows->get_error_message() );
			$this->redirect_import();
		}
		$preview = $importer->preview( $rows, HIP_Ad_Settings::get( 'network_code' ) );
		set_transient( $this->import_key(), $preview, HOUR_IN_SECONDS );
		delete_transient( $this->import_result_key() );
		$this->set_flash( 'info', __( 'CSV okundu. Uygulamadan önce değişiklikleri kontrol edin.', 'hip-admanager' ) );
		$this->redirect_import();
	}

	public function import_confirm() {
		$this->guard_action( 'hip_ad_import_confirm' );
		$preview = get_transient( $this->import_key() );
		if ( ! $preview ) {
			$this->set_flash( 'error', __( 'İçe aktarma önizlemesi bulunamadı veya süresi doldu.', 'hip-admanager' ) );
			$this->redirect_import();
		}
		$importer = new HIP_Ad_Importer();
		$results = $importer->commit( $preview, isset( $_POST['update_existing'] ) );
		set_transient( $this->import_result_key(), $results, HOUR_IN_SECONDS );
		delete_transient( $this->import_key() );
		$this->set_flash( 'success', __( 'İçe aktarma tamamlandı.', 'hip-admanager' ) );
		$this->redirect_import();
	}

	public function import_cancel() {
		$this->guard_action( 'hip_ad_import_cancel' );
		delete_transient( $this->import_key() );
		delete_transient( $this->import_result_key() );
		$this->set_flash( 'info', __( 'İçe aktarma iptal edildi.', 'hip-admanager' ) );
		$this->redirect_import();
	}

	public function export_json() {
		$this->guard_action( 'hip_ad_export_json' );
		$payload = array(
			'exportVersion' => 2,
			'generatedAt'   => gmdate( 'c' ),
			'settings'      => HIP_Ad_Settings::get_all(),
			'adsTxt'        => HIP_Ad_Settings::get_ads_txt(),
			'slots'         => HIP_Ad_Repository::all(),
		);
		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="hip-ads-' . gmdate( 'Y-m-d-His' ) . '.json"' );
		echo wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	public function run_migration() {
		$this->guard_action( 'hip_ad_run_migration' );
		HIP_Ad_Migrator::maybe_migrate( true );
		$this->set_flash( 'success', __( 'Veri migration/normalizasyonu yeniden çalıştırıldı.', 'hip-admanager' ) );
		wp_safe_redirect( admin_url( 'admin.php?page=hip-ad-diagnostics' ) );
		exit;
	}

	private function parse_sizes_form() {
		$widths = isset( $_POST['size_width'] ) ? (array) wp_unslash( $_POST['size_width'] ) : array();
		$heights = isset( $_POST['size_height'] ) ? (array) wp_unslash( $_POST['size_height'] ) : array();
		$sizes = array();
		foreach ( $widths as $index => $width ) {
			$height = isset( $heights[ $index ] ) ? $heights[ $index ] : 0;
			$sizes[] = array( absint( $width ), absint( $height ) );
		}
		return $sizes;
	}

	private function parse_mappings_form() {
		$viewports = isset( $_POST['mapping_viewport'] ) ? (array) wp_unslash( $_POST['mapping_viewport'] ) : array();
		$size_sets = isset( $_POST['mapping_sizes'] ) ? (array) wp_unslash( $_POST['mapping_sizes'] ) : array();
		$mappings = array();
		foreach ( $viewports as $index => $viewport ) {
			$raw_sizes = isset( $size_sets[ $index ] ) ? (string) $size_sets[ $index ] : '';
			preg_match_all( '/(\d{1,4})\s*[xX]\s*(\d{1,4})/', $raw_sizes, $matches, PREG_SET_ORDER );
			$sizes = array();
			foreach ( $matches as $match ) {
				$sizes[] = array( (int) $match[1], (int) $match[2] );
			}
			$mappings[] = array( 'viewport' => array( absint( $viewport ), 0 ), 'sizes' => $sizes );
		}
		return $mappings;
	}

	private function parse_targeting_form( $key_field, $value_field ) {
		$keys = isset( $_POST[ $key_field ] ) ? (array) wp_unslash( $_POST[ $key_field ] ) : array();
		$values = isset( $_POST[ $value_field ] ) ? (array) wp_unslash( $_POST[ $value_field ] ) : array();
		$targeting = array();
		foreach ( $keys as $index => $key ) {
			$key = preg_replace( '/[^A-Za-z0-9_.-]/', '', (string) $key );
			if ( ! $key ) {
				continue;
			}
			$value = isset( $values[ $index ] ) ? sanitize_text_field( $values[ $index ] ) : '';
			if ( false !== strpos( $value, ',' ) ) {
				$targeting[ $key ] = array_values( array_filter( array_map( 'trim', explode( ',', $value ) ) ) );
			} elseif ( '' !== $value ) {
				$targeting[ $key ] = $value;
			}
		}
		return $targeting;
	}

	private function guard() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'Bu sayfaya erişim yetkiniz yok.', 'hip-admanager' ) );
		}
	}

	private function guard_action( $action ) {
		$this->guard();
		check_admin_referer( $action );
	}

	private function set_flash( $type, $message ) {
		set_transient( 'hip_ad_flash_' . get_current_user_id(), array( 'type' => sanitize_key( $type ), 'message' => sanitize_text_field( $message ) ), MINUTE_IN_SECONDS );
	}

	private function pull_flash() {
		$key = 'hip_ad_flash_' . get_current_user_id();
		$flash = get_transient( $key );
		delete_transient( $key );
		return is_array( $flash ) ? $flash : null;
	}

	private function import_key() {
		return 'hip_ad_import_preview_' . get_current_user_id();
	}

	private function import_result_key() {
		return 'hip_ad_import_results_' . get_current_user_id();
	}

	private function redirect_import() {
		wp_safe_redirect( admin_url( 'admin.php?page=hip-ad-import' ) );
		exit;
	}
}
