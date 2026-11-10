<?php
defined( 'ABSPATH' ) or die();
class cmplz_integrations {
	private static $_this;

	function __construct() {
		if ( isset( self::$_this ) ) {
			wp_die( sprintf( '%s is a singleton class and you cannot create a second instance.', get_class( $this ) ) );
		}
		self::$_this = $this;

		add_filter( "cmplz_do_action", array( $this, 'integrations_data' ), 10, 3 );
		add_filter( "cmplz_warning_types", array( $this, 'notify_of_plugin_integrations' ), 10, 3 );
		add_action( "cmplz_after_save_field", array( $this, 'sync_services' ), 10, 4 );
		add_filter( 'cmplz_default_value', array($this, 'set_default'), 10, 3 );
	}

	static function this() {
		return self::$_this;
	}

	public function headers(){
		return function_exists('rsssl_get_option') && rsssl_get_option('hsts') &&
		                       rsssl_get_option('x_frame_options')!=='disabled' &&
		                       rsssl_get_option('x_content_type_options') &&
		                       rsssl_get_option('x_xss_protection')!=='disabled' &&
		                       rsssl_get_option('referrer_policy')==='strict-origin-when-cross-origin';
	}

	public function hardening(){
		return function_exists('rsssl_get_option') &&
		             rsssl_get_option('disable_file_editing') &&
		             rsssl_get_option('block_code_execution_uploads') &&
		             rsssl_get_option('hide_wordpress_version') &&
		             rsssl_get_option('disable_login_feedback') &&
		             rsssl_get_option('disable_indexing') &&
		             rsssl_get_option('disable_user_enumeration');
	}

	public function set_default( $value, $fieldname, $field ) {
		if ( function_exists( 'rsssl_get_option' ) && $fieldname === 'which_personal_data_secure' ) {
			if ( !is_array($value)) $value = array();
			if ( ! isset( $value['6'] ) && rsssl_get_option( 'enable_vulnerability_scanner' ) ) {
				$value[] = '6';
			}

			if ( ! isset( $value['4'] ) && $this->headers() ) {
				$value[] = '4';
			}

			if ( ! isset( $value['5'] ) && $this->hardening() ) {
				$value[] = '5';
			}

			if ( ! isset( $value['3'] ) && rsssl_get_option( 'ssl_enabled' ) ) {
				$value[] = '3';
			}
		}
		return $value;
	}

	/**
	 * Keep services in the settings in sync with services in the database
	 * @return void
	 */
	public function sync_services($fieldname, $fieldvalue, $prev_value, $type) {
		if ( !cmplz_user_can_manage() ) {
			return;
		}
		if ($fieldname==='uses_thirdparty_services' || $fieldname==='thirdparty_services_on_site') {
			$thirdparty_services = COMPLIANZ::$config->thirdparty_services;
			foreach ( $thirdparty_services as $service => $label ) {
				$service_obj = new CMPLZ_SERVICE($service);
				if ( cmplz_uses_thirdparty($service) ) {
					if (!$service_obj->ID ) {
						$service_obj->add( $label, COMPLIANZ::$banner_loader->get_supported_languages(), false, 'utility' );
					}
				} else if ($service_obj) {
					$service_obj->delete();
				}
			}
		}

		if ($fieldname==='uses_social_media' || $fieldname==='socialmedia_on_site') {
			$socialmedia = COMPLIANZ::$config->thirdparty_socialmedia;
			foreach ( $socialmedia as $service => $label ) {
				$service_obj = new CMPLZ_SERVICE( $service );
				if ( cmplz_uses_thirdparty( $service ) ) {
					if (!$service_obj->ID ){
						$service_obj->add( $label, COMPLIANZ::$banner_loader->get_supported_languages(), false, 'social' );
					}
				} else if ( $service_obj ) {
					$service_obj->delete();
				}
			}
		}
	}

	/**
	 * Handle rest api integration updates
	 * @return array
	 */
	public function integrations_data( $data, $action, $request ) {
		if (!cmplz_user_can_manage()) {
			return $data;
		}
		if ( $action === 'get_integrations_data' ) {
			$blocked_scripts = array_keys(COMPLIANZ::$cookie_blocker->blocked_scripts());
			//create a key => key array from the $blocked_scripts array
			$blocked_scripts = array_combine($blocked_scripts, $blocked_scripts);
			$data = [
				'plugins' => $this->get_plugins(),
				'services' => $this->get_services(),
				'scripts' => $this->get_scripts(),
				'placeholders' => COMPLIANZ::$config->placeholders,
				'blocked_scripts' => $blocked_scripts,
			];
		} else if ( $action === 'update_placeholder_status' ) {
			$data = $request->get_json_params();
			$id = isset( $data['id'] ) ? cmplz_canonical_placeholder_id( sanitize_title( $data['id'] ) ) : '';
			$enabled = $data['enabled'] ?? false;
			$disabled_placeholders = (array) get_option( 'cmplz_disabled_placeholders', array() );

			// clear every stored spelling of the id first, a legacy underscore entry included, so a
			// disable cannot end up recorded only under a form the writer no longer produces. This
			// also makes the re-index and the duplicate guard fall out of one code path.
			foreach ( $disabled_placeholders as $key => $disabled_placeholder ) {
				if ( is_string( $disabled_placeholder ) && cmplz_canonical_placeholder_id( $disabled_placeholder ) === $id ) {
					unset( $disabled_placeholders[ $key ] );
				}
			}
			$disabled_placeholders = array_values( $disabled_placeholders );
			if ( ! $enabled ) {
				$disabled_placeholders[] = $id;
			}
			update_option( 'cmplz_disabled_placeholders', $disabled_placeholders );

			// clear blocked scripts transient on edits.
			cmplz_delete_transient( 'cmplz_blocked_scripts' );
			$data = [
				'success' => true,
			];
		} else if ( $action === 'update_plugin_status' ){
			$data = $request->get_json_params('plugin');
			$plugin = isset($data['plugin']) ? sanitize_title($data['plugin']) : '';
			$enabled = $data['enabled'] ?? false;
			$plugins = get_option( 'complianz_options_integrations', [] );
			$plugins[ $plugin ] = (bool) $enabled;
			update_option( 'complianz_options_integrations', $plugins );
			$data = [
				'success' => true,
			];
		} else if ( $action === 'update_scripts') {
			$data = $request->get_json_params('plugin');

			//clear blocked scripts transient on edits.
			cmplz_delete_transient('cmplz_blocked_scripts');

			$scripts = $data['scripts'] ?? [];
			$scripts = $this->parse_args($scripts);
			$scripts = $this->sanitize_scripts($scripts);
			update_option( 'complianz_options_custom-scripts', $scripts );
		} else if ( $action === 'get_security_measures_data' ) {
			$is_7 = defined('rsssl_version') && version_compare(    rsssl_version,'7','>=' ) ? true : false;
			$measures = [];
			$measures[] = [
				'id' => 'vulnerability_detection',
				'enabled' => $is_7 && rsssl_get_option('enable_vulnerability_scanner')
			];
			$measures[] = [
				'id' => 'recommended_headers',
				'enabled' => $this->headers(),
			];

			$measures[] = [
				'id' => 'ssl',
                'enabled' => $is_7 && rsssl_get_option('ssl_enabled'),
			];

			$measures[] = [
				'id' => 'hardening',
				'enabled' => $this->hardening(),
			];

			$data = [
				'measures' => $measures,
				'has_7' => $is_7,
			];
		}
		return $data;
	}

	/**
	 * @return array
	 */

	private function get_scripts() : array {
		$scripts = get_option("complianz_options_custom-scripts", [] );
		return $this->parse_args($scripts);
	}

	/**
	 * @return array
	 */
	public function parse_args($scripts){
		$defaults_block_script = [
			'enable'             => 1,
			'name'               => '',
			'urls'               => [],
			'category'           => 'marketing',
			'enable_placeholder' => false,
			'iframe'             => false,
			'placeholder_class'  => '',
			'placeholder'        => '',
			'enable_dependency'  => '',
			'dependency'         => [],//maps.google.com => cmplz_divi_init_map
		];
		$defaults_add_script = [
			'enable'             => 1,
			'name'               => '',
			'urls'               => [],
			'category'           => 'marketing',
			'enable_placeholder' => false,
			'iframe'             => false,
			'placeholder_class'  => '',
			'placeholder'        => '',
			'editor'             => '',
			'async'             => '',
		];
		$defaults_whitelist_script = [
			'enable'             => 1,
			'name'               => '',
			'urls'               => [],
		];
		$defaults = [
			'block_script' => [],
			'add_script' => [],
			'whitelist_script' => [],
		];

		$default_values_add_script = array(
			array(
				'name' => __("Example", 'complianz-gdpr'),
				'editor' => 'console.log("fire marketing script")',
				'async' => '0',
				'category' => 'marketing',
				'enable_placeholder' => '1',
				'placeholder_class' => 'your-css-class',
				'placeholder' => 'default',
				'enable' => '0',
			),
		);

		$default_values_block_script = array(
			array(
				'name' => __("Example", 'complianz-gdpr'),
				'urls' => array('https://block-example.com'),
				'category' => 'marketing',
				'enable_placeholder' => '1',
				'iframe' => '1',
				'placeholder_class' => 'your-css-class',
				'placeholder' => 'default',
				'enable_dependency' => '1',
				'dependency' => array(),
				'enable' => '0',
			),
		);

		$default_values_whitelist_script = array(
			array(
				'name' => __("Example", 'complianz-gdpr'),
				'urls' => array('https://block-example.com'),
				'enable' => '0',
			),
		);

		$scripts = wp_parse_args( $scripts, $defaults );
		foreach ( $scripts as $type => $script ) {
			if ( empty( $script ) ) {
				$scripts[ $type ] = ${"default_values_$type"};
			}
			foreach ( $script as $key => $value ) {
				$scripts[ $type ][ $key ] = wp_parse_args( $value, ${"defaults_$type"} );
				//drop id
				unset($scripts[ $type ][ $key ]['id']);
			}
		}

		foreach ( $scripts as $type => $scripts_array ) {
			//ensure that the keys in $scripts_array start at 0, and are sequential
			$scripts_array = array_values($scripts_array);
			$scripts[ $type ] = $scripts_array;
		}
		return $scripts;
	}

	/**
	 * Sanitize scripts
	 * @param array $scripts
	 *
	 * @return array
	 */
	public function sanitize_scripts($scripts): array {
		foreach ( $scripts as $type => $script ) {
			if ( ! is_array( $script ) ) {
				$scripts[ $type ] = [];
			}
			foreach ( $script as $key => $value ) {
				$scripts[ $type ][ $key ]['name']               = sanitize_text_field( $value['name'] );
				$scripts[ $type ][ $key ]['enable']             = (bool) $value['enable'];

				if (isset($value['placeholder_class']) ) $scripts[ $type ][ $key ]['placeholder_class']  = sanitize_text_field( $value['placeholder_class'] );
				if (isset($value['placeholder']) ) $scripts[ $type ][ $key ]['placeholder']        = sanitize_text_field( $value['placeholder'] );
				if (isset($value['urls']) ) $scripts[ $type ][ $key ]['urls']               = array_map( function ( $url ) {
					return sanitize_text_field( $url );
				}, $value['urls'] );
				if (isset($value['dependency']) ) $scripts[ $type ][ $key ]['dependency']         = array_map( function ( $url ) {
					return sanitize_text_field( $url );
				}, $value['dependency'] );
				if (isset($value['category']) ) $scripts[ $type ][ $key ]['category']           = cmplz_sanitize_category( $value['category'] );
				if (isset($value['enable_placeholder']) ) $scripts[ $type ][ $key ]['enable_placeholder'] = (bool) $value['enable_placeholder'];
				if (isset($value['iframe']) ) $scripts[ $type ][ $key ]['iframe']             = (bool) $value['iframe'];
				if (isset($value['enable_dependency']) ) $scripts[ $type ][ $key ]['enable_dependency']  = (bool) $value['enable_dependency'];

				if (isset($value['editor']) ) $scripts[ $type ][ $key ]['editor']             = $value['editor'];
				if (isset($value['async']) ) $scripts[ $type ][ $key ]['async']              = (bool) $value['async'];

			}
		}

		return $scripts;
	}

	/**
	 * Get list of plugins
	 *
	 * @return array
	 */
	private function get_plugins( bool $with_placeholder = true ): array {
		$plugins = [];
		if (!cmplz_user_can_manage()) {
			return $plugins;
		}
		global $cmplz_integrations_list;
		$enforced_names = null;
		foreach ( $cmplz_integrations_list as $plugin => $details ) {
			$file = apply_filters( 'cmplz_integration_path', CMPLZ_PATH . "integrations/plugins/$plugin.php", $plugin );
			if ( file_exists( $file ) && cmplz_integration_plugin_is_active( $plugin ) ) {
				// the toggle writes the plugin id, but the enforcer is queried with the blocked-script
				// name. Only offer the toggle when those are the same id, otherwise it is inert and
				// contradicts the Services row that controls the same placeholder.
				$placeholder = 'none';
				if ( $with_placeholder && $this->has_placeholder( $plugin ) ) {
					$enforced_names = $enforced_names ?? COMPLIANZ::$cookie_blocker->placeholder_service_names();
					if ( cmplz_placeholder_id_in_list( $plugin, $enforced_names ) ) {
						$placeholder = $this->get_placeholder_status( $plugin );
					}
				}
				$plugins[] = [
					'id' => $plugin,
					'label' => $details['label'],
					'enabled' => cmplz_integration_plugin_is_enabled($plugin),
					'placeholder' => $placeholder,
				];
			}
		}
		return $plugins;
	}

	/**
	 * Get list of services active on the site
	 * @return array[]
	 */
	private function get_services(){
		if (!cmplz_user_can_manage()) {
			return [];
		}
		$services = [
			[
				'id' => 'advertising',
				'label' => __('Advertising', 'complianz-gdpr'),
				'source' => 'uses_ad_cookies',
				'placeholder' => 'none',
			],
		];

		$thirdparty_services = COMPLIANZ::$config->thirdparty_services;
		foreach ( $thirdparty_services as $service => $label ) {

			$services[] = [
				'id' => $service,
				'label' => $label,
				'source' => 'thirdparty_services_on_site',
				'placeholder' => $this->get_placeholder_status($service),

			];
		}

		$socialmedia        = COMPLIANZ::$config->thirdparty_socialmedia;
		foreach ( $socialmedia as $service => $label ) {
			$services[] = [
				'id' => $service,
				'label' => $label,
				'source' => 'socialmedia_on_site',
				'placeholder' => $this->get_placeholder_status($service),
			];
		}
		return $services;
	}

	/**
	 * Get the status of a placeholder
	 *
	 * @param string $service
	 *
	 * @return string //none, disabled, enabled
	 */
	private function get_placeholder_status( string $service): string {
		if ( !$this->has_placeholder($service) ) {
			return 'none';
		}

		$disabled_placeholders = (array) get_option( 'cmplz_disabled_placeholders', array() );
		if ( cmplz_placeholder_id_in_list( $service, $disabled_placeholders ) ) {
			return 'disabled';
		}
		return 'enabled';
	}

	/**
	 * Check if a service or plugin has a placeholder
	 *
	 * @param string $name
	 *
	 * @return bool
	 */
	private function has_placeholder( string $name): bool {
		$_name = str_replace('-', '_', $name);
		return function_exists( "cmplz_{$name}_placeholder") || function_exists( "cmplz_{$_name}_placeholder" );
	}

	public function notify_of_plugin_integrations( $warnings ){
		$plugins = $this->get_plugins( false );
		foreach ($plugins as $plugin ) {
			if ( !$plugin['enabled']) continue;
			$warnings['integration_enabled'] = array(
				'open' => __('We have enabled integrations for plugins and services, please double-check your configuration.', 'complianz-gdpr' ),
				'url' => 'https://complianz.io/enabled-integration/',
				'include_in_progress' => false,
			);
			break;
		}

		return $warnings;
	}

}
$integrations = new cmplz_integrations();
