<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

/**
 * Install cookiebanner table
 * */
add_action( 'cmplz_install_tables', 'cmplz_install_cookiebanner_table' );
function cmplz_install_cookiebanner_table($force=false) {
	//only load on front-end if it's a cron job
	if ( !$force && !is_admin() && !wp_doing_cron() ) {
		return;
	}

	if ( !$force && !wp_doing_cron() && !cmplz_user_can_manage() ) {
		return;
	}

	if ( get_option( 'cmplz_cbdb_version' ) !== CMPLZ_VERSION ) {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$table_name = $wpdb->prefix . 'cmplz_cookiebanners';

		$sql        = "CREATE TABLE $table_name (
             `ID` int(11) NOT NULL AUTO_INCREMENT,
             `title` text NOT NULL,
             `banner_version` int(11) NOT NULL,
             `default` int(11) NOT NULL,
            `position` text NOT NULL,
            `checkbox_style` text NOT NULL,
            `use_logo` text NOT NULL,
            `logo_attachment_id` text NOT NULL,
			`close_button` text NOT NULL,
            `revoke` text NOT NULL,
            `manage_consent_options` text NOT NULL,
            `header` text NOT NULL,
            `dismiss` text NOT NULL,
            `save_preferences` text NOT NULL,
            `view_preferences` text NOT NULL,
            `category_functional` text NOT NULL,
            `category_all` text NOT NULL,
            `category_stats` text NOT NULL,
            `category_prefs` text NOT NULL,
            `accept` text NOT NULL,
            `message_optin` text NOT NULL,
            `use_categories` text NOT NULL,
            `disable_cookiebanner` int(11) NOT NULL,
            `banner_width` int(11) NOT NULL,
            `soft_cookiewall` int(11) NOT NULL,
            `dismiss_on_scroll` int(11) NOT NULL,
            `dismiss_on_timeout` int(11) NOT NULL,
            `dismiss_timeout` text NOT NULL,
            `accept_informational` text NOT NULL,
            `message_optout` text NOT NULL,
            `use_custom_cookie_css` text NOT NULL,
            `custom_css` text NOT NULL,
            `statistics` text NOT NULL,
            `functional_text` text NOT NULL,
            `statistics_text` text NOT NULL,
            `statistics_text_anonymous` text NOT NULL,
            `preferences_text` text NOT NULL,
            `marketing_text` text NOT NULL,
            `colorpalette_background` text NOT NULL,
            `colorpalette_text` text NOT NULL,
            `colorpalette_toggles` text NOT NULL,
            `colorpalette_border_radius` text NOT NULL,
            `border_width` text NOT NULL,
            `font_size` text NOT NULL,
            `colorpalette_button_accept` text NOT NULL,
            `colorpalette_button_deny` text NOT NULL,
            `colorpalette_button_settings` text NOT NULL,
            `buttons_border_radius` text NOT NULL,
            `animation` text NOT NULL,
            `use_box_shadow` int(11) NOT NULL,
            `header_footer_shadow` int(11) NOT NULL,
            `hide_preview` int(11) NOT NULL,
            `disable_width_correction` int(11) NOT NULL,
            `legal_documents` int(11) NOT NULL,
              PRIMARY KEY  (ID)
            ) $charset_collate;";
		dbDelta( $sql );

		/*
		 * use_categories_optinstats- border_color are obsolete
		 * for data integrity, we do not delete them, but change them to text to prevent row size issues.
		*/

		$columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name ");
		$upgrade_sql = [];
		foreach ($columns as $column) {
			if (strpos($column->Type, 'varchar')!==false){
				$upgrade_sql[]="`".$column->Field."` text NOT NULL";
			}
		}

		if (count($upgrade_sql)>0) {
			$sql = implode(','."\n",$upgrade_sql);
			$sql = "CREATE TABLE $table_name ($sql
					) $charset_collate;";
			dbDelta( $sql );
		}

		//drop obsolete columns
		$drop_columns = [
			'accept_all',
			'theme',
			'readmore_optin',
			'tagmanager_categories',
			'use_categories_optinstats',
			'hide_revoke',
			'readmore_optout',
			'readmore_optout_dnsmpi',
			'readmore_privacy',
			'readmore_impressum',
			'popup_background_color',
			'popup_text_color',
			'slider_background_color',
			'slider_background_color_inactive',
			'slider_bullet_color',
			'button_background_color',
			'button_text_color',
			'accept_all_background_color',
			'accept_all_border_color',
			'accept_all_text_color',
			'functional_background_color',
			'functional_text_color',
			'functional_border_color',
			'border_color',
			'custom_css_amp',
		];

		$db = DB_NAME;
		foreach ($drop_columns as $column) {
			$exists = $wpdb->query("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '$db' AND TABLE_NAME = '$table_name' AND COLUMN_NAME = '$column';");
			if ($exists) {
				$wpdb->query("ALTER TABLE $table_name DROP COLUMN $column;");
			}
		}
		//not preload false: used to check existence of database table.
		update_option( 'cmplz_cbdb_version', CMPLZ_VERSION );
	}
}

if ( ! class_exists( "cmplz_cookiebanner" ) ) {
	class CMPLZ_COOKIEBANNER {
		public $ID = false;
		public $banner_version = 0;
		public $title;
		public $default = false;

		/* styling */
		public $position;
		public $checkbox_style;
		public $use_logo;
		public $logo_attachment_id;
		public $close_button;
		public $use_custom_cookie_css;
		public $custom_css;
        public $colorpalette_background;
        public $colorpalette_text;
        public $colorpalette_toggles;
        public $colorpalette_border_radius;
        public $border_width;
        public $font_size;
        public $colorpalette_button_accept;
        public $colorpalette_button_deny;
        public $colorpalette_button_settings;
        public $buttons_border_radius;
        public $animation;
        public $use_box_shadow;
        public $header_footer_shadow;
        public $hide_preview;

		/* texts */
        public $header;
		public $revoke;
		public $manage_consent_options;
		public $dismiss;
		public $accept;
		public $message_optin;
		public $accept_informational;
		public $message_optout;
		public $save_preferences;
		public $view_preferences;
		public $category_functional;
		public $category_all;
		public $category_stats;
		public $category_prefs;
		public $use_categories;
		public $disable_cookiebanner;
		public $banner_width;
		public $soft_cookiewall;
		public $dismiss_on_scroll;
		public $dismiss_on_timeout;
		public $dismiss_timeout;
		public $save_preferences_x;
		public $view_preferences_x;
		public $category_functional_x;
		public $category_all_x;
		public $category_stats_x;
		public $category_prefs_x;
		public $accept_x;
		public $dismiss_x;
		public $revoke_x;
		public $message_optin_x;
		public $accept_informational_x;
		public $message_optout_x;
		public $header_x;
		public $translation_id;
		public $statistics;
		public $functional_text;
		public $functional_text_x;
		public $statistics_text;
		public $statistics_text_x;
		public $statistics_text_anonymous;
		public $statistics_text_anonymous_x;
		public $preferences_text;
		public $preferences_text_x;
		public $marketing_text;
		public $marketing_text_x;
		public $set_defaults;
		public $disable_width_correction;
		public $legal_documents;
		public $banner_fields;
		public $logo_options;

        function __construct( $ID = false, $set_defaults = true, $load_wysiwyg_options = false ) {
	        if ( !get_option('cmplz_cbdb_version') ) {
		        //table not created yet.
		        return;
	        }
			$this->banner_fields = cmplz_add_cookiebanner_settings([]);
	        $this->translation_id = $this->get_translation_id();
	        $this->ID             = $ID;
	        $this->set_defaults   = $set_defaults;
	        $this->get($load_wysiwyg_options);
        }

		/**
		 * Add a new cookiebanner database entry
		 */

		private function add() {
			if ( ! cmplz_user_can_manage() ) {
				return;
			}
			$array = ['default'=>false];
			global $wpdb;
			//make sure we have at least one default banner
			$cookiebanners = $wpdb->get_results( "select * from {$wpdb->prefix}cmplz_cookiebanners as cb where cb.default = true" );
			if ( empty( $cookiebanners ) ) {
				$array['default'] = true;
			}
			$wpdb->insert(
				$wpdb->prefix . 'cmplz_cookiebanners',
				$array
			);

			$this->ID = $wpdb->insert_id;
		}

		/**
		 * Load the cookiebanner data
		 * If ID has value 'default', we get the one with the value 'default'
		 */

		private function get($load_wysiwyg_options) {
			global $wpdb;
			if ( (int) $this->ID > 0 ) {
				$cookiebanner = wp_cache_get('cmplz_cookiebanner_'.$this->ID, 'cmplz');
				if (!$cookiebanner) {
					$cookiebanner = $wpdb->get_row( $wpdb->prepare( "select * from {$wpdb->prefix}cmplz_cookiebanners where ID = %s", intval( $this->ID ) ) );
					wp_cache_set('cmplz_cookiebanner_'.$this->ID, $cookiebanner, 'cmplz');
				}
				if ( $cookiebanner ) {
					$this->banner_version = $cookiebanner->banner_version;
					$this->default        = $cookiebanner->default;
					foreach ( $cookiebanner as $fieldname => $value ) {
						//check if $this->{$fieldname} exists
						if ( property_exists( $this, $fieldname ) ) {
							$this->{$fieldname} = $this->parse_value( $fieldname, $value );
						}
					}
				}
			} else if ( $this->set_defaults ) {
				//in case there's no cookiebanner, we do this outside the loop
				foreach ( $this as $fieldname => $value ) {
					if ( property_exists( $this, $fieldname ) ) {
						$this->{$fieldname} = $this->parse_value( $fieldname, $value, true );
					}
				}
			}

			/**
			 * translate
			 */

			foreach ( $this as $fieldname => $value ) {
				if ( $this->is_translatable( $fieldname ) ) {
					if ( is_array( $value ) && isset( $value['text'] ) ) {
						$this->{$fieldname . '_x'}['text'] = $this->translate( $value['text'], $fieldname );
					} else if ( ! is_array( $value ) ) {
						$this->{$fieldname . '_x'} = $this->translate( $value, $fieldname );
					}
				}
			}

			if ( $this->use_categories === 'hidden' ) {
				$this->use_categories = 'view-preferences';
			}

			if ($load_wysiwyg_options) {
				$this->logo_options = $this->get_banner_logo(true);
			}
		}

		/**
		 * Get a value, with default if available
		 *
		 * @param string $fieldname
		 * @param mixed $value
		 * @param bool   $force_defaults
		 *
		 * @return mixed
		 */

		private function parse_value( string $fieldname, $value, bool $force_defaults=false ){
			$set_defaults = $this->set_defaults;
			//get type of field
			$type = $this->get_field_type($fieldname);
			$default = $this->get_default( $fieldname );
			//treat as string
			if ( $type === 'text' || $type === 'select' || $type === 'editor' ) {
				//on some websites, the previous value seems to be cached. We try to catch that here.
				//should be removed at some future point
				if ( $fieldname==='revoke' && is_serialized($value) ){
					$value = unserialize($value);
					$value = isset($value['text']) ? $value['text'] :__( "Manage consent", 'complianz-gdpr' );
				}
				if ( empty($value) && $set_defaults ) {
					$value = $default;
				}
			} else if ( $type === 'checkbox' ) {
				if ( ( $value === false && $set_defaults) || $force_defaults ) {
					$value = $default;
				}
			} else if ( $type === 'number' || $type === 'logo_attachment_id' ) {
				if ( empty($value) ) {
					$value = $default;
				} else {
					$value = (int) $value;
				}
			} else if ( $type === 'text_checkbox' || $type === 'colorpicker' || $type === 'borderradius' || $type === 'borderwidth') {
				//array types
				if ( is_serialized($value ) ) {
					$value = unserialize($value);
					//code to prevent duplicate upgrades
					$stop_check = false;
					foreach ($value as $key => $key_value ) {
						if ( $stop_check ) continue;
						if ( is_serialized( $key_value )) {
							$value = $this->get_default( $fieldname );
							$stop_check = true;
						}
					}
				}

				//strip out empty values in arrays, so the default gets set.
				if ( is_array($value) ) {
					//store 'show' index, to prevent losing the 'false' settings
					if ( $type !== 'text_checkbox') {
						$value = array_filter($value, function($arr_value) {
							return ($arr_value !== null && $arr_value !== false && $arr_value !== '');
						});
					}
				} else {
					$value = [];
				}

				if ( is_array($default) ) {
					foreach ( $default as $key => $default_arr_value ) {
						//if the key is not set, we set the default
						if ( !isset($value[$key]) ) {
							$value[$key] = $default_arr_value;
						} else {
							//key is set. We only set the default, if it's empty and set_defaults is true
							if ( $key !== 'show' && $value[$key] === '' && $set_defaults ) {
								$value[$key] = $default_arr_value;
							}
						}
					}
				}

			} else if ( $type === 'css' ) {
				$value = !empty($value) ? htmlspecialchars_decode( $value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 ) : '';
				if (empty($value) && $set_defaults) {
					$value = $default;
				}
			}

			if ( $this->is_translatable( $fieldname ) ) {
				$this->{$fieldname . '_x'} = $this->translate($value, $fieldname);
			}

			return $value;
		}

		/**
		 * Check if a field is translatable
		 * @param string $fieldname
		 *
		 * @return bool
		 */

		private function is_translatable($fieldname) {
			if (property_exists($this, $fieldname.'_x')) {
				return true;
			}

			return false;
		}

		/**
		 * translate field
		 *
		 * @param string|array $value
		 * @param string $fieldname
		 *
		 * @return string|array
		 */

		private function translate( $value, $fieldname ) {
			$translate_string = $value;
			if ( is_array($value) && isset($value['text']) ) {
				$translate_string = $value['text'];
			}

			//e.g. When elementor integration is active, preferences may pass an array without the text entry here, causing an error with WPML
			if ( is_array( $translate_string ) ) {
				return '';
			}

			$key = $this->translation_id;
			if ( function_exists( 'pll__' ) ) {
				$translate_string = pll__( $translate_string );
			}

			if ( function_exists( 'icl_translate' ) ) {
				$translate_string = icl_translate( 'complianz', $fieldname . $key, $translate_string );
			}

			$translate_string = apply_filters( 'wpml_translate_single_string', $translate_string, 'complianz', $fieldname . $key );

			if ( is_array($value) && isset($value['text']) ) {
				$value['text'] = $translate_string;
			} else {
				$value = $translate_string;
			}
			return $value;
		}

		/**
		 * Register a translation
		 * @param string|array $string
		 * @param string $fieldname
		 */

		private function register_translation( $string, $fieldname ) {
			if (isset($string['text'])) {
				$string = $string['text'];
			}

			//e.g. When elementor integration is active, preferences may pass an array without the text entry here, causing an error with WPML
			if ( is_array( $string ) || is_serialized($string)) {
				return;
			}

			$key = $this->translation_id;
			//polylang
			if ( function_exists( "pll_register_string" ) ) {
				pll_register_string( $fieldname . $key, $string, 'complianz' );
			}

			//wpml
			if ( function_exists( 'icl_register_string' ) ) {

				icl_register_string( 'complianz', $fieldname . $key, $string );
			}

			do_action( 'wpml_register_single_string', 'complianz', $fieldname, $string );
		}

		/**
		 * Get a prefix for translation registration
		 * For backward compatibility we don't use a key when only one banner, or when the lowest.
		 * If we don't use this, all field names from each banner will be the same, registering won't work.
		 *
		 * @return string
		 */

		public function get_translation_id() {
			//if this is the banner with the lowest ID's, no ID
			$lowest = cmplz_get_transient('cmplz_min_banner_id');
			if ( !$lowest ){
				global $wpdb;
				$lowest = $wpdb->get_var( "select min(ID) from {$wpdb->prefix}cmplz_cookiebanners" );
				cmplz_set_transient('cmplz_min_banner_id', $lowest, HOUR_IN_SECONDS );
			}
			if ( $lowest == $this->ID ) {
				return '';
			}
			return $this->ID;
		}

		/**
		 * Get a default value
		 *
		 * @param string $fieldname
		 * @param bool|string   $key
		 *
		 * @return mixed
		 */

		private function get_default( string $fieldname, $key=false ) {
			$field = array_filter($this->banner_fields, static function($value) use ($fieldname) {
				return $value['id'] === $fieldname;
			});
			$field = reset($field);

			if ($key) {
				return $field['default'][ $key ] ?? '';
			}

			return $field['default'] ?? '';
		}

		/**
		 * Get the type of a field
		 * @param string $fieldname
		 *
		 * @return string
		 */
		private function get_field_type( string $fieldname ) {
			$field = array_filter($this->banner_fields, static function($value) use ($fieldname) {
				return $value['id'] === $fieldname;
			});
			$field = reset($field);
			return $field['type'] ?? 'none';
		}

		/**
		 * Save the edited data in the object
		 *
		 * @return void
		 */

		public function save() {
			if ( !cmplz_user_can_manage() && !wp_doing_cron() ) {
				return;
			}
			if ( ! $this->ID ) {
				$this->add();
			}
			$this->banner_version++;

			//register translations fields
			foreach ( $this as $fieldname => $value ) {
				if ( $this->is_translatable( $fieldname )) {
					$this->register_translation( $this->{$fieldname}, $fieldname );
				}
			}

			if ( ! is_array( $this->statistics ) ) {
				$this->statistics = array();
			}

			$statistics   = serialize( $this->statistics );
			if ( $this->use_categories === 'hidden' ) {
				$this->use_categories = 'view-preferences';
			}

			if ( !$this->disable_cookiebanner && cmplz_get_option('enable_cookie_banner') === 'no' ) {
				cmplz_update_option_no_hooks('enable_cookie_banner', 'yes');
			}

			$update_array = array(
				'title'                        => sanitize_text_field( $this->title ),
				'position'                     => $this->sanitize_position( $this->position ),
				'banner_version'               => $this->banner_version,
				'checkbox_style'               => $this->sanitize_checkbox_style( $this->checkbox_style ),
				'use_logo'                     => sanitize_text_field( $this->use_logo ),
				'logo_attachment_id'           => (int) $this->logo_attachment_id,
				'close_button'                 => (int) $this->close_button,
				'category_functional'          => sanitize_text_field( $this->category_functional ),
				'category_prefs'               => $this->sanitize_text_checkbox( $this->category_prefs ),
				'category_stats'               => $this->sanitize_text_checkbox( $this->category_stats ),
				'category_all'                 => $this->sanitize_text_checkbox( $this->category_all ),
				'header'                       => $this->sanitize_text_checkbox( $this->header ),
				'dismiss'                      => $this->sanitize_text_checkbox( $this->dismiss ),
				'revoke'                       => sanitize_text_field( $this->revoke ),
				'manage_consent_options'       => $this->sanitize_manage_consent( $this->manage_consent_options ),
				'save_preferences'             => sanitize_text_field( $this->save_preferences ),
				'view_preferences'             => sanitize_text_field( $this->view_preferences ),
				'accept'                       => sanitize_text_field( $this->accept ),
				'message_optin'                => wp_kses( $this->message_optin, cmplz_allowed_html() ),
				'use_categories'               => sanitize_text_field( $this->use_categories ),
				'disable_cookiebanner'         => (bool) ( $this->disable_cookiebanner ),
				'banner_width'                 => (int) $this->banner_width,
				'soft_cookiewall'              => (bool) $this->soft_cookiewall,
				'dismiss_on_scroll'            => (int) $this->dismiss_on_scroll,
				'dismiss_on_timeout'           => (int) $this->dismiss_on_timeout,
				'dismiss_timeout'              => (int) $this->dismiss_timeout,
				'font_size'                    => (int) $this->font_size,
				'accept_informational'         => $this->sanitize_text_checkbox( $this->accept_informational ),
				'message_optout'               => wp_kses( $this->message_optout, cmplz_allowed_html() ),
				'use_custom_cookie_css'        => (int) $this->use_custom_cookie_css,
				'custom_css'                   => $this->custom_css,
				'statistics'                   => $statistics,
				'functional_text'              => $this->sanitize_text_checkbox( $this->functional_text ),
				'preferences_text'             => $this->sanitize_text_checkbox( $this->preferences_text ),
				'statistics_text'              => $this->sanitize_text_checkbox( $this->statistics_text ),
				'statistics_text_anonymous'    => $this->sanitize_text_checkbox( $this->statistics_text_anonymous ),
				'marketing_text'               => $this->sanitize_text_checkbox( $this->marketing_text ),
				'colorpalette_background'      => $this->sanitize_hex_array( $this->colorpalette_background ),
				'colorpalette_text'            => $this->sanitize_hex_array( $this->colorpalette_text ),
				'colorpalette_toggles'         => $this->sanitize_hex_array( $this->colorpalette_toggles ),
				'colorpalette_border_radius'   => $this->sanitize_int_array( $this->colorpalette_border_radius ),
				'border_width'                 => $this->sanitize_int_array( $this->border_width ),
				'colorpalette_button_accept'   => $this->sanitize_hex_array( $this->colorpalette_button_accept ),
				'colorpalette_button_deny'     => $this->sanitize_hex_array( $this->colorpalette_button_deny ),
				'colorpalette_button_settings' => $this->sanitize_hex_array( $this->colorpalette_button_settings ),
				'buttons_border_radius'        => $this->sanitize_int_array( $this->buttons_border_radius ),
				'animation'                    => $this->sanitize_animation( $this->animation ),
				'use_box_shadow'               => (int) $this->use_box_shadow,
				'header_footer_shadow'         => (int) $this->header_footer_shadow,
				'hide_preview'                 => (int) $this->hide_preview,
				'disable_width_correction'     => (int) $this->disable_width_correction,
				'legal_documents'                   => (int) $this->legal_documents,
			);
			global $wpdb;
			$updated = $wpdb->update( $wpdb->prefix . 'cmplz_cookiebanners',
				$update_array,
				array( 'ID' => $this->ID )
			);

			if ( $updated === 0 ) {
				if ( !get_option( 'cmplz_generate_new_cookiepolicy_snapshot') ) update_option( 'cmplz_generate_new_cookiepolicy_snapshot', time(), false );
			}

			//get database value for "default"
			$db_default = $wpdb->get_var( $wpdb->prepare( "select cdb.default from {$wpdb->prefix}cmplz_cookiebanners as cdb where cdb.ID=%s", $this->ID ) );
			if ( $this->default && ! $db_default ) {
				$this->enable_default();
			} elseif ( ! $this->default && $db_default ) {
				$this->remove_default();
			}
			wp_cache_delete('cmplz_cookiebanner_'.$this->ID, 'cmplz');
			cmplz_delete_transient('cmplz_min_banner_id');
			cmplz_delete_transient('cmplz_default_banner_id');

			$this->generate_css();
		}

		/**
		 * Sanitize position
		 * @param $pos
		 *
		 * @return string
		 */
		private function sanitize_position($pos): string {
			$p = [
				'center',
				'bottom',
				'bottom-left',
				'bototm-right',
			];
			if ( in_array( $pos, $p, true ) ) return $pos;
			return 'bottom-right';
		}

		private function sanitize_animation($animation): string {
			$a = [
				'none',
				'fade',
				'slide',
			];
			if ( in_array( $animation, $a, true ) ) return $animation;
			return 'none';
		}

		private function sanitize_manage_consent($manage_consent): string {
			$m = [
				'hover-hide-mobile',
				'hover-show-mobile',
				'show-everywhere',
				'hide-everywhere',
			];
			if ( in_array( $manage_consent, $m, true ) ) return $manage_consent;
			return 'hover-hide-mobile';
		}

		private function sanitize_checkbox_style($checkbox){
			$c = ['slider', 'classic'];
			if ( in_array( $checkbox, $c, true ) ) return $checkbox;
			return 'slider';
		}

		/**
		 * Sanitize an array or string as hex
		 * @param array|string $hex
		 *
		 * @return string|array
		 */
		public function sanitize_hex_array( $hex ) {
			if ( is_array($hex) ) {
				$hex = serialize( array_map('sanitize_hex_color', $hex ) );
			} else {
				$hex = sanitize_hex_color($hex);
			}
			return $hex;
		}

		/**
		 * Sanitize text checkbox field
		 */
		public function sanitize_text_checkbox( $text_checkbox ) {
			if ( isset( $text_checkbox['text'], $text_checkbox['show'] ) ) {
				$text_checkbox = [
					'text' => sanitize_text_field($text_checkbox['text']),
					'show' => (int) $text_checkbox['show'],
				];
			} else {
				$text_checkbox = [
					'text' => "",
					'show' => true,
				];
			}


			return serialize($text_checkbox);
		}

		/**
		 * Sanitize an array or int as int
		 *
		 * @param $int
		 *
		 * @return int|array
		 */
		public function sanitize_int_array( $int ) {
			$store_type = false;
			if ( is_array($int) ) {
				if (isset($int['type'])) {
					$store_type = $int['type'];
				}
				$int = array_map('intval', $int );
				if ($store_type){
					$int['type'] = $store_type;
				}
				$int = serialize(  $int );
			} else {
				$int = intval($int);
			}
			return $int;
		}

		/**
		 * sanitize the css to remove any commented or empty classes
		 *
		 * @param string $css
		 *
		 * @return string
		 */

		private function sanitize_css( $css ) {
			$css = preg_replace( '/\/\*(.|\s)*?\*\//i', '', $css ); //comments
			$css = preg_replace( '/\..*{}/i', '', $css );//empty classes from custom css
			$css = str_replace(array("\r", "\n"), '', $css); //line breaks
			$css = preg_replace('/\s+/', ' ', $css); //duplicate spaces
			$css = trim( $css );
			return $css;
		}

		/**
		 * Delete a cookie variation
		 *
		 * @return bool $success
		 * @since 2.0
		 */

		public function delete( $force = false ) {
			if ( ! cmplz_user_can_manage() ) {
				return false;
			}

			$error = false;
			global $wpdb;

			//do not delete the last one.
			$count
				= $wpdb->get_var( "select count(*) as count from {$wpdb->prefix}cmplz_cookiebanners" );
			if ( $count == 1 && ! $force ) {
				$error = true;
			}

			if ( ! $error ) {
				if ( $this->default ) {
					$this->remove_default();
				}

				$wpdb->delete( $wpdb->prefix . 'cmplz_cookiebanners', array(
					'ID' => $this->ID,
				) );

				//clear all statistics regarding this banner
				$sql = $wpdb->prepare( "UPDATE {$wpdb->prefix}cmplz_statistics SET cookiebanner_id = 0 where poc_url=%s", $this->ID) ;
				$wpdb->query($sql);
			}

			return ! $error;
		}

		/**
		 * Get available categories
		 * @param bool $labels
		 * @param bool $exclude_no_warning
		 * @return array
		 */

		public function get_available_categories( $labels = true, $exclude_no_warning = false){
			$available_cats = [];

			$available_cats['functional'] = __("Functional", "complianz-gdpr");

			if ( cmplz_uses_preferences_cookies() ) {
				$available_cats['preferences'] = __("Preferences", "complianz-gdpr");
			}

			if ( cmplz_uses_statistic_cookies() ) {
				$available_cats['statistics'] = __( "Statistics", "complianz-gdpr" );
			}

			if ( cmplz_uses_marketing_cookies() ) {
				$available_cats['marketing'] = __("Marketing", "complianz-gdpr");
			}


			//get all categories
			$available_cats['do_not_track'] = __("Do Not Track", "complianz-gdpr");
			$available_cats['no_choice'] = __("No Choice", "complianz-gdpr");

			if ( ! $exclude_no_warning && cmplz_get_option( 'use_country' )) {
				$available_cats['no_warning'] = __("No Warning", "complianz-gdpr");
			}
			if ( !$labels ) {
				$available_cats = array_keys( $available_cats );
			}
			return $available_cats;
		}

		/**
		 * Check if current banner is the default, and if so move it to another banner.
		 */

		public function remove_default() {
			if ( cmplz_user_can_manage() ) {

				global $wpdb;
				//first, set one  of the other banners random to default.
				$cookiebanners = $wpdb->get_results( "select * from {$wpdb->prefix}cmplz_cookiebanners as cb where cb.default = false LIMIT 1" );
				if ( ! empty( $cookiebanners ) ) {
					$wpdb->update( $wpdb->prefix . 'cmplz_cookiebanners',
						array( 'default' => true ),
						array( 'ID' => $cookiebanners[0]->ID )
					);
				}

				//now set this one to not default and save
				$wpdb->update( $wpdb->prefix . 'cmplz_cookiebanners',
					array( 'default' => false ),
					array( 'ID' => $this->ID )
				);

			}
		}

		/**
		 * Check if current banner is not default, and if so disable the current default
		 */

		public function enable_default() {
			if ( cmplz_user_can_manage() ) {

				global $wpdb;
				//first set the current default to false
				$cookiebanners = $wpdb->get_results( "select * from {$wpdb->prefix}cmplz_cookiebanners as cb where cb.default = true LIMIT 1" );
				if ( ! empty( $cookiebanners ) ) {
					$wpdb->update( $wpdb->prefix . 'cmplz_cookiebanners',
						array( 'default' => false ),
						array( 'ID' => $cookiebanners[0]->ID )
					);
				}

				//now set this one to default
				$wpdb->update( $wpdb->prefix . 'cmplz_cookiebanners',
					array( 'default' => true ),
					array( 'ID' => $this->ID )
				);
			}

		}

		/**
		 * @param $statistics
		 *
		 * @return mixed
		 */

		public function report_conversion_count( $statistics ) {
			return $statistics['all'];
		}


		/**
		 * Get the conversion to marketing for a cookie banner
		 * @param string $filter_consenttype
		 * @return float percentage
		 */

		public function conversion_percentage( $filter_consenttype ) {

			$categories = $this->get_available_categories();
			$revers_arr = array_reverse($categories);
			$highest_level_cat = array_key_first($revers_arr);
			$conversion_count = $this->get_count( $highest_level_cat, $filter_consenttype );
			$total = $this->get_count( 'all', $filter_consenttype );
			$total = ( $total == 0 ) ? 1 : $total;
			return ROUND( 100 * ( $conversion_count / $total ) );

		}

		/**
		 * Get the count for this category and consent type.
		 *
		 * @param string $consent_category
		 * @param string $consenttype
		 *
		 * @return int $count
		 */

		public function get_count( $consent_category, $consenttype ) {
			global $wpdb;
			$available_categories  = $this->get_available_categories( false );
			$ab_testing_start_time = get_option('cmplz_tracking_ab_started');

			//sanitize status
			if ( $consent_category !== 'all' && !in_array( $consent_category, $available_categories ) ) return 0;

			//category
			$category_sql = '';
			if ($consent_category !== 'all') $category_sql = " AND $consent_category = 1";

			$consenttype_sql = " AND consenttype='$consenttype'";
			if ( $consenttype === 'all' ) {
				$consenttypes    = cmplz_get_used_consenttypes();
				$consenttype_sql = " AND (consenttype='" . implode( "' OR consenttype='", $consenttypes ) . "')";
			}

			$sql = $wpdb->prepare("SELECT count(*) from {$wpdb->prefix}cmplz_statistics WHERE time> %s $category_sql $consenttype_sql" , $ab_testing_start_time );

			if ( cmplz_ab_testing_enabled() ) {
				$sql = $wpdb->prepare( $sql . " AND cookiebanner_id=%s", $this->ID );
			}
			return $wpdb->get_var( $sql );
		}

		/**
		 * Get Logo url for the banner
		 *
		 * @return string|array
		 */
		public function get_banner_logo($all_variants = false )
		{
			$logo = "";
			if ($all_variants) {
				$custom_image = wp_get_attachment_image($this->logo_attachment_id, 'cmplz_banner_image', false, ['alt' => get_bloginfo('name') ]);
				if (empty($custom_image)) {
					$custom_image = '<img src="'.CMPLZ_URL.'/assets/images/placeholders/default-light.jpg" class="attachment-cmplz_banner_image size-cmplz_banner_image" alt="placeholder" loading="lazy" />';
				}
				return array(
					'complianz' => file_get_contents(trailingslashit(CMPLZ_PATH) . 'assets/images/poweredbycomplianz.svg'),
					'site' => get_custom_logo(),
					'custom' => $custom_image,
				);
			}
			switch ($this->use_logo) {
				case 'complianz':
					$logo = file_get_contents(trailingslashit(CMPLZ_PATH) . 'assets/images/poweredbycomplianz.svg');
					break;
				case 'site':
					$logo = get_custom_logo();
					break;
				case 'custom':
					$logo = wp_get_attachment_image($this->logo_attachment_id, 'cmplz_banner_image', false, ['alt' => get_bloginfo('name') ]);
			}

			return $logo;
		}


		/**
		 * Get array to output to front-end
		 *
		 * @return array
		 */

		public function get_html_settings() {
			$output = array(
				'id'                        => $this->ID,
				'title_class'               => sanitize_title($this->title),
				'logo'                      => $this->get_banner_logo(),
				'header'                    => $this->header_x,
				'accept_optin'              => $this->accept_x,
				'accept_optout'             => $this->accept_informational_x,
				'manage_consent'            => $this->revoke_x,
				'manage_options'            => $this->view_preferences_x,
				'save_settings'             => $this->save_preferences_x,
				'dismiss'                   => $this->dismiss_x,
				'message_optout'            => $this->message_optout_x,
				'message_optin'             => $this->message_optin_x,
				'category_functional'       => $this->category_functional_x,
				'category_preferences'      => $this->category_prefs_x,
				'category_statistics'       => $this->category_stats_x,
				'functional_text'           => $this->functional_text_x,
				'statistics_text'           => $this->statistics_text_x,
				'statistics_text_anonymous' => $this->statistics_text_anonymous_x,
				'preferences_text'          => $this->preferences_text_x,
				'marketing_text'            => $this->marketing_text_x,
				'category_marketing'        => $this->category_all_x,
				'position'                  => $this->position,
				'manage_consent_options'    => $this->manage_consent_options,
				'use_categories'            => $this->use_categories,
			);
			$output = apply_filters( 'cmplz_cookiebanner_settings_html', $output, $this );
			return apply_filters( 'cmplz_cookiebanner_settings', $output, $this );
		}

		/**
		 * Get list of required CSS modules
		 * @param string $consent_type
		 * @param bool $preview
		 * @return array
		 */
		function get_css_file_modules($consent_type, $preview)
		{
			//using minified files causes issue when using the slider version.
			$minified = '';//( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
			// Main and Position
			$css_files = [
				"reset$minified.css",
				"cookiebanner$minified.css",
			];

			$css_files[] = "$consent_type$minified.css";
			$css_files[] = "positions/{$this->position}$minified.css";
			if ( !cmplz_tcf_active() || $consent_type === 'optout' ) {
				if ( $this->use_categories === "no" ) {
					$css_files[] = "categories/accept-deny$minified.css";
				} else if ( $this->use_categories === "save-preferences" ) {
					$css_files[] = "categories/save-preferences$minified.css";
				} else {
					$css_files[] = "categories/view-preferences$minified.css";
				}
			}

			if ( cmplz_get_option( 'consent_per_service' ) !== 'yes' ) {
				$css_files[] = "settings/hide-manage-services$minified.css";
			}
			if ( cmplz_tcf_active() ) {
				$css_files[] = "tcf$minified.css";
			}
			// Animation
			if ( !$preview && $this->animation !== 'none' ) {
				if ( $this->animation === "slide" ) {
					$css_files[] = "settings/animation/{$this->position}-slide$minified.css";
				} else {
					$css_files[] = "settings/animation/{$this->animation}$minified.css";
				}
			}
			if ( isset($this->functional_text['show']) && !$this->functional_text['show'] )  $css_files[] = "settings/categories/hide-functional_text$minified.css";
			if ( ( isset( $this->category_prefs['show'] ) && ! $this->category_prefs['show'] ) || !cmplz_uses_preferences_cookies() ) $css_files[] = "settings/categories/hide-preferences$minified.css";
			if ( ( isset( $this->category_stats['show'] ) && ! $this->category_stats['show'] ) || !cmplz_uses_statistic_cookies() ) $css_files[] = "settings/categories/hide-statistics$minified.css";
			if ( ( isset( $this->category_all['show'] ) && ! $this->category_all['show'] ) || !cmplz_uses_marketing_cookies() )  $css_files[] = "settings/categories/hide-marketing$minified.css";
			if ( isset($this->preferences_text['show']) && !$this->preferences_text['show'] )  $css_files[] = "settings/categories/hide-preferences_text$minified.css";
			if ( isset($this->statistics_text['show']) && !$this->statistics_text['show'] )  $css_files[] = "settings/categories/hide-statistics_text$minified.css";
			if ( isset($this->statistics_text_anonymous['show']) && !$this->statistics_text_anonymous['show'] )  $css_files[] = "settings/categories/hide-statistics_text$minified.css";
			if ( isset($this->marketing_text['show']) && !$this->marketing_text['show'] )  $css_files[] = "settings/categories/hide-marketing_text$minified.css";
			if ( $consent_type==='optout' && isset($this->accept_informational['show']) && !$this->accept_informational['show'] ) $css_files[] = "settings/hide-accept$minified.css";
			if ( isset($this->dismiss['show']) && !$this->dismiss['show'] ) $css_files[] = "settings/hide-deny$minified.css";
			if ( isset($this->header['show']) && !$this->header['show'] ) $css_files[] = "settings/hide-title$minified.css";
			$css_files[] = "settings/$this->manage_consent_options$minified.css";

			if ( $this->use_logo === "hide" ) 	        $css_files[] = "settings/hide-logo$minified.css";
			if ( !$this->close_button ) 		 	    $css_files[] = "settings/hide-close$minified.css";
			if ( $this->checkbox_style === "slider" )   $css_files[] = "settings/toggle-slider$minified.css";
			if ( !$this->legal_documents ) $css_files[] = "settings/hide-links$minified.css";

			// Soft cookie wall
			if ( $this->soft_cookiewall ) $css_files[] = "settings/soft-cookie-wall$minified.css";

			// Shadow
			if ( $this->use_box_shadow )
				$css_files[] = "settings/shadow$minified.css";
			if ( $this->header_footer_shadow ) $css_files[] = "settings/header-footer-shadow$minified.css";

			//hide complete header if logo, title and close are hidden.
			if ( (!isset($this->header['show']) || !$this->header['show'])
				&& !$this->close_button
				&& $this->use_logo === 'hide'
			) {
				$css_files[] = "settings/hide-header$minified.css";
			}

			if ( cmplz_statistics_privacy_friendly() ) {
				$css_files[] = 'anonymous-stats.css';
			}
			return apply_filters('cmplz_banner_css_files', $css_files);
		}

		public function get_array_value($field, $key = false ){
			if ( $key ) {
				$value = $this->{$field}[ $key ] ?? $this->get_default( $field, $key );
			} else {
				$value = $this->{$field};
			}
			return $value;
		}

		public function get_css_settings() {
			$output = array(
				"banner_background_color" => $this->colorpalette_background['color'] ?? '',
				"banner_border_color" => $this->colorpalette_background['border'] ?? '',
				"banner_border_width" => $this->get_border_width(),
				"banner_width" => $this->banner_width.'px',
				"text_font_size" => $this->font_size.'px',
				"link_font_size" => $this->font_size.'px',
				"category_body_font_size" => $this->font_size.'px',
				"banner_border_radius" => $this->get_border_radius($this->colorpalette_border_radius),
				"text_color" => $this->colorpalette_text['color'] ?? '',
				"hyperlink_color" => $this->colorpalette_text['hyperlink'] ?? '',
				"category_header_always_active_color" => "green",
				"button_accept_background_color" => $this->colorpalette_button_accept['background'] ?? '',
				"button_accept_border_color" => $this->colorpalette_button_accept['border'] ?? '',
				"button_accept_text_color" => $this->colorpalette_button_accept['text'] ?? '',
				"button_deny_background_color" => $this->colorpalette_button_deny['background'] ?? '',
				"button_deny_border_color" => $this->colorpalette_button_deny['border'] ?? '',
				"button_deny_text_color" => $this->colorpalette_button_deny['text'] ?? '',
				"button_settings_background_color" => $this->colorpalette_button_settings['background'] ?? '',
				"button_settings_border_color" => $this->colorpalette_button_settings['border'] ?? '',
				"button_settings_text_color" => $this->colorpalette_button_settings['text'] ?? '',
				"button_border_radius" => $this->get_border_radius($this->buttons_border_radius),
				"slider_active_color" => $this->colorpalette_toggles['background'] ?? '',
				"slider_inactive_color" => $this->colorpalette_toggles['inactive'] ?? '',
				"slider_bullet_color" => $this->colorpalette_toggles['bullet'] ?? '',
				"category_open_icon_url" => "url(".trailingslashit( CMPLZ_URL)."assets/images/chevron-down.svg)",
			);
			$output = apply_filters( 'cmplz_cookiebanner_settings_css', $output, $this );
			return apply_filters( 'cmplz_cookiebanner_settings', $output, $this );
		}

		/**
		 * Generate the css file for the banner
		 * @param bool $preview
		 */
		public function generate_css( $preview = false ): void {
			if (get_transient('cmplz_generate_css_active')) {
				return;
			}
			set_transient('cmplz_generate_css_active', true, 10 );
			$upload_dir = cmplz_upload_dir('css');
			$consent_types = cmplz_get_used_consenttypes();
			//when there's nothing yet, get the default
			if (empty($consent_types)) {
				$consent_types = [COMPLIANZ::$company->get_default_consenttype()];
			}
			$settings = $this->get_css_settings();
			$banner_id = $this->ID ?: 'new';
			foreach ( $consent_types as $consent_type ) {
				$css_files = $this->get_css_file_modules($consent_type, $preview);
				$css = "";
				foreach ($css_files as $css_file) {
					$file_path = trailingslashit(CMPLZ_PATH) . "cookiebanner/css/$css_file";
					if ( file_exists($file_path) ) {
						$css .= file_get_contents($file_path) . "\n";
					}
				}

				if ( $this->use_custom_cookie_css ) {
					$css .=  $this->custom_css;
				}

				$category_count = 3;//functional is always available, so does not count here
				if ( ( isset( $this->category_prefs['show'] ) && ! $this->category_prefs['show'] ) || !cmplz_uses_preferences_cookies() ) {
					$category_count--;
				}
				if ( ( isset( $this->category_stats['show'] ) && ! $this->category_stats['show'] ) || !cmplz_uses_statistic_cookies() ) {
					$category_count--;
				}
				if ( ( isset( $this->category_all['show'] ) && ! $this->category_all['show'] ) || !cmplz_uses_marketing_cookies() )  {
					$category_count--;
				}
				$remove_count = 3 - $category_count;//functional always exists
				$height = 216 - $remove_count * 53;
				$settings['categories-height'] = $height.'px';
				foreach ($settings as $setting => $value) {
					$css = preg_replace("/--cmplz_$setting:[^;]*;/", "--cmplz_$setting: $value;", $css, 1);
				}

				ob_start();
				do_action("cmplz_banner_css");
				$css .= "\n" . ob_get_clean()."\n";
				$css = $this->sanitize_css( apply_filters('cmplz_cookiebanner_css', $css) );
				$file = $preview ? "{$upload_dir}banner-preview-{$banner_id}-$consent_type.css" : "{$upload_dir}banner-{$banner_id}-$consent_type.css";
				if ( file_exists($upload_dir) && is_writable($upload_dir) ){
					$handle = fopen($file, 'wb' );
					fwrite($handle, $css);
					fclose($handle);
				}
				// Trigger the action after file generation to allow custom handling
				do_action('cmplz_after_css_generation', $file, $css, $upload_dir, $banner_id, $consent_type);
			}
			delete_transient('cmplz_generate_css_active' );
		}

		/**
		 * Check if CSS should be regenerated based on source file modification times
		 * 
		 * @return bool
		 */
		private function should_regenerate_css() {
			$css_files = $this->get_css_file_modules('optin', false);
			$latest_source_time = 0;
			$latest_generated_time = 0;
			
			// Get the latest modification time of source files
			foreach ($css_files as $css_file) {
				$file_path = trailingslashit(CMPLZ_PATH) . "cookiebanner/css/$css_file";
				if (file_exists($file_path)) {
					$latest_source_time = max($latest_source_time, filemtime($file_path));
				}
			}
			
			// Get the latest modification time of generated files
			$upload_dir = cmplz_upload_dir('css');
			$consent_types = cmplz_get_used_consenttypes();
			foreach ($consent_types as $consent_type) {
				$file = "{$upload_dir}banner-{$this->ID}-$consent_type.css";
				if (file_exists($file)) {
					$latest_generated_time = max($latest_generated_time, filemtime($file));
				}
			}
			
			return $latest_source_time > $latest_generated_time;
		}

		/**
		 * Get array to output to front-end
		 * @param bool $preview
		 * @return array
		 */
		public function get_front_end_settings( $preview = false ) {
			// Check if CSS needs regeneration and trigger it if needed
			if (!$preview && $this->should_regenerate_css()) {
				$this->generate_css();
			}

			$store_consent = cmplz_ab_testing_enabled() || cmplz_get_option('records_of_consent') === 'yes';
			$this->dismiss_timeout = $this->dismiss_on_timeout ? 1000 * $this->dismiss_timeout : false;
			$upload_url = is_ssl() ? str_replace('http://', 'https://', cmplz_upload_url()) : cmplz_upload_url();
			//check if the css file exists. if not, use default.
			$css_file = $upload_url . 'css/banner-{banner_id}-{type}.css';
			$banner_id = $this->ID;

			if ( !$preview ) {
				$upload_dir = cmplz_upload_dir();
				$consent_types = cmplz_get_used_consenttypes();

				foreach ( $consent_types as $consent_type ) {
					$file =  "css/banner-$banner_id-$consent_type.css";
					if ( ! file_exists( $upload_dir . $file ) ) {
						$css_file = CMPLZ_URL . "cookiebanner/css/defaults/banner-{type}.css";
					}
				}
			}
			$script_debug = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? time() : '';
			$locale = get_locale();
			$page_links = cmplz_get_transient( "page_links_{$banner_id}_{$locale}" );
			if ( !$page_links ) {
				$page_links = COMPLIANZ::$document->get_page_links();
				cmplz_set_transient( "page_links_{$banner_id}_{$locale}", $page_links, 10 * MINUTE_IN_SECONDS);
			}

			$region = apply_filters('cmplz_user_region', COMPLIANZ::$company->get_default_region() );
			$disable_cookiebanner = $this->disable_cookiebanner || is_preview() || cmplz_is_pagebuilder_preview() || isset($_GET["cmplz_safe_mode"]);
			$output = array(
				'prefix'               => COMPLIANZ::$banner_loader->get_cookie_prefix(),
				'user_banner_id'       => apply_filters( 'cmplz_user_banner_id', cmplz_get_default_banner_id() ),
				'set_cookies'          => apply_filters( 'cmplz_set_cookies_on_consent', array() ), //cookies to set on acceptance, in order array('cookiename=>array('consent value', 'revoke value');
				'block_ajax_content'   => cmplz_get_option( 'enable_cookieblocker_ajax' ),
				'banner_version'       => $this->banner_version,
				'version'              => CMPLZ_VERSION,
				'store_consent'        => $store_consent,
				'do_not_track_enabled' => cmplz_get_option('respect_dnt') !== 'no',
				'consenttype'          => COMPLIANZ::$company->get_default_consenttype(),
				'region'               => $region,
				'geoip'                => cmplz_geoip_enabled(),
				'dismiss_timeout'      => $this->dismiss_timeout,
				'disable_cookiebanner' => $disable_cookiebanner,
				'soft_cookiewall'      => (bool) $this->soft_cookiewall,
				'dismiss_on_scroll'    => (bool) $this->dismiss_on_scroll,
				'cookie_expiry'        => cmplz_get_option( 'cookie_expiry' ),
				'url'                  => get_rest_url(null, 'complianz/v1/'),
				'locale'               => 'lang='.substr( get_locale(), 0, 2 ).'&locale='.get_locale(),
				'set_cookies_on_root'  => cmplz_get_option( 'set_cookies_on_root' ),
				'cookie_domain'        => COMPLIANZ::$banner_loader->get_cookie_domain(),
				'current_policy_id'    => COMPLIANZ::$banner_loader->get_active_policy_id(),
				'cookie_path'          => COMPLIANZ::$banner_loader->get_cookie_path(),
				'categories'           => ['statistics'=> _x("statistics","as in: click to accept statistics cookies","complianz-gdpr"), 'marketing'=> _x("marketing","as in: click to accept marketing cookies","complianz-gdpr")],
				'tcf_active'           => cmplz_tcf_active(),
				'placeholdertext'      => COMPLIANZ::$cookie_blocker->blocked_content_text(),
				'css_file'             => $css_file . '?v='.$this->banner_version.$script_debug,
				'page_links'           => $page_links,
				'tm_categories'        => COMPLIANZ::$banner_loader->uses_google_tagmanager() || (cmplz_get_option('compile_statistics', false )==='matomo-tag-manager'),
				'forceEnableStats'     => !COMPLIANZ::$banner_loader->cookie_warning_required_stats( $region ),
				'preview'              => false,
				'clean_cookies'        => cmplz_get_option( 'safe_mode' ) != 1 && cmplz_get_option( 'consent_per_service' ) === 'yes',
				'aria_label'           => cmplz_get_option( 'consent_per_service' ) === 'yes' ? __( "Click button to enable {service}", 'complianz-gdpr' ) : cmplz_get_option( 'blocked_content_text' ),
			);

			$output = apply_filters( 'cmplz_cookiebanner_settings_front_end', $output, $this );
			return apply_filters( 'cmplz_cookiebanner_settings', $output, $this );
		}

		/**
		 * Get border radius string
		 * @param array $element
		 *
		 * @return string
		 */
        private function get_border_radius($element) {
	        $types = array('px', '%','em', 'rem');
            $type   = !isset($element['type']) || ! in_array( $element['type'], $types, true ) ? 'px' : $element['type'];
	        $element = wp_parse_args($element, array(
	        	'top'=>0,
	        	'right'=>0,
	        	'bottom'=>0,
	        	'left'=>0,
		        )
	        );

	        $top    = $element['top'] .  $type . ' ';
            $right  = $element['right'] .  $type . ' ';
            $bottom = $element['bottom'] .  $type . ' ';
            $left   = $element['left'] .  $type;
            return $top . $right . $bottom . $left;
        }

        private function get_border_width() {
	        $top    = isset( $this->border_width['top'] ) ? $this->border_width['top'] . 'px ' : 0;
	        $right  = isset( $this->border_width['right'] ) ? $this->border_width['right'] . 'px ' : 0;
	        $bottom = isset( $this->border_width['bottom'] ) ? $this->border_width['bottom'] . 'px ' : 0;
	        $left   = isset( $this->border_width['left'] ) ? $this->border_width['left'] . 'px ' : 0;
            return $top . $right . $bottom . $left;
        }
	}
}
