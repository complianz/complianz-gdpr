<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

//ensure it's only loaded once, because of theme and plugin possibility
if ( !function_exists('cmplz_divi_map_script')) {
	if ( !defined('CMPLZ_GOOGLE_MAPS_INTEGRATION_ACTIVE') ) define('CMPLZ_GOOGLE_MAPS_INTEGRATION_ACTIVE', true);

	add_filter( 'cmplz_known_script_tags', 'cmplz_divi_map_script' );
	function cmplz_divi_map_script( $tags ) {
		if ( !cmplz_uses_thirdparty('google-maps') ) {
			return $tags;
		}

		$tags[] = array(
			'name' => 'google-maps',
			'category' => 'marketing',
			'placeholder' => 'google-maps',
			'urls' => array(
				'maps.googleapis.com',
				'cmplz_divi_init_map'
			),
			'enable_placeholder' => '1',
			'placeholder_class' => 'et_pb_map',
			'enable_dependency' => '1',
			'dependency' => [
				//'wait-for-this-script' => 'script-that-should-wait'
					'maps.googleapis.com' => 'cmplz_divi_init_map',
			],
		);
		return $tags;
	}

	/**
	 */

	function cmplz_divi_whitelist($tags){
		$tags[] = 'et_animation_data';
		$tags[] = 'Divi/core/admin/js/recaptcha.js';
		return $tags;
	}
	add_filter( 'cmplz_whitelisted_script_tags', 'cmplz_divi_whitelist');

	/**
	 * Add services to the list of detected items, so it will get set as default, and will be added to the notice about it
	 *
	 * @param $services
	 *
	 * @return array
	 */

	function cmplz_divi_map_detected_services( $services ) {
		if ( ! in_array( 'google-maps', $services ) ) {
			$services[] = 'google-maps';
		}
		return $services;
	}
	add_filter( 'cmplz_detected_services', 'cmplz_divi_map_detected_services' );

	/**
	 * Initialize recaptcha
	 *
	 */

	function cmplz_divi_init_recaptcha() {
		if ( !cmplz_uses_thirdparty('google-recaptcha') ){
			return;
		}
		ob_start();
		?>
		<script>
			// Idempotent: this after-script may be re-injected by cmplz_run_script.
			window.cmplz_activated_divi_recaptcha = window.cmplz_activated_divi_recaptcha || false;
			if ( ! window.cmplz_divi_recaptcha_hooked ) {
				window.cmplz_divi_recaptcha_hooked = true;
				document.addEventListener("cmplz_enable_category", function (e) {
					if (!window.cmplz_activated_divi_recaptcha && (e.detail.category==='marketing' || e.detail.service === 'google-recaptcha') ){
						cmplz_divi_init_recaptcha();
					}
				});
			}

			function cmplz_divi_init_recaptcha() {
				if ('undefined' === typeof window.jQuery || 'undefined' === typeof window.etCore ) {
					setTimeout(cmplz_divi_init_recaptcha, 500);
				} else {
					window.etCore.api.spam.recaptcha.init();
					window.cmplz_activated_divi_recaptcha = true;
				}
			}
		</script>
		<?php
		$script = ob_get_clean();
		$script = str_replace(array('<script>', '</script>'), '', $script);
		wp_add_inline_script( 'cmplz-cookiebanner', $script );
	}
	add_action( 'wp_enqueue_scripts', 'cmplz_divi_init_recaptcha',PHP_INT_MAX );

	/**
	 * Init Google Maps
	 *
	 * @return void
	 */
	function cmplz_divi_init_maps() {
		if ( !cmplz_uses_thirdparty('google-maps') ) {
			return;
		}

		ob_start();
		?>
		<script>
			// Idempotent: this after-script may be re-injected by cmplz_run_script.
			window.cmplz_activated_divi_maps = window.cmplz_activated_divi_maps || false;
			if ( ! window.cmplz_divi_maps_hooked ) {
				window.cmplz_divi_maps_hooked = true;
				document.addEventListener("cmplz_enable_category", function (e) {
					if (!window.cmplz_activated_divi_maps && (e.detail.category==='marketing' || e.detail.service === 'google-maps') ){
						cmplz_divi_init_map();
					}
				});
				setTimeout(cmplz_divi_init_map, 300);
			}

			function cmplz_divi_init_map() {
				if ('undefined' === typeof window.jQuery || 'undefined' === typeof window.et_pb_map_init ) {
					setTimeout(cmplz_divi_init_map, 1000);
				} else {
					let map_container = jQuery(".et_pb_map_container");
					map_container.each(function () {
						window.et_pb_map_init(jQuery(this));
						window.cmplz_activated_divi_maps = true;
					})
				}
			}
		</script>

		<?php
		$script = ob_get_clean();
		$script = str_replace(array('<script>', '</script>'), '', $script);
		wp_add_inline_script( 'cmplz-cookiebanner', $script );
	}
	add_action( 'wp_enqueue_scripts', 'cmplz_divi_init_maps',PHP_INT_MAX );
}
