/**
 * Admin JavaScript
 *
 * @package HIP_Ad_Manager
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		// JSON validation for textarea fields
		const jsonFields = [
			'#gam_sizes',
			'#gam_size_mappings',
			'#gam_targeting',
			'#gam_display_rules',
			'#global_targeting'
		];

		jsonFields.forEach(function(selector) {
			const $field = $(selector);
			if ($field.length) {
				$field.on('blur', function() {
					validateJSON($(this));
				});
			}
		});

		function validateJSON($field) {
			const value = $field.val().trim();
			
			if (value === '') {
				removeError($field);
				return;
			}

			try {
				JSON.parse(value);
				removeError($field);
			} catch (e) {
				showError($field, 'Invalid JSON format');
			}
		}

		function showError($field, message) {
			removeError($field);
			$field.css('border-color', '#d63638');
			$field.after('<p class="hip-ad-error" style="color: #d63638; margin-top: 5px;">' + message + '</p>');
		}

		function removeError($field) {
			$field.css('border-color', '');
			$field.siblings('.hip-ad-error').remove();
		}

		// Auto-format JSON on focus out
		$('.code').on('blur', function() {
			const $this = $(this);
			const value = $this.val().trim();
			
			if (value === '') {
				return;
			}

			try {
				const parsed = JSON.parse(value);
				const formatted = JSON.stringify(parsed, null, 2);
				$this.val(formatted);
			} catch (e) {
				// Don't format if invalid JSON
			}
		});

		// Confirm before importing
		$('form[action*="hip_ad_confirm_import"]').on('submit', function(e) {
			if (!confirm('Are you sure you want to import these ad slots? This action cannot be undone.')) {
				e.preventDefault();
				return false;
			}
		});
	});

})(jQuery);
