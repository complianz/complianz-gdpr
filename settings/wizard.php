<?php // phpcs:disable WordPress.Files.FileName.InvalidClassFileName -- Legacy file name retained for backward compatibility.
defined( 'ABSPATH' ) || die( 'you do not have access to this page!' );

if ( ! class_exists( 'cmplz_wizard' ) ) {
	// phpcs:disable PEAR.NamingConventions.ValidClassName.StartWithCapital, PEAR.NamingConventions.ValidClassName.Invalid -- Legacy class name retained for backward compatibility.
	/**
	 * Wizard handler: enforces the singleton and reacts to wizard field saves.
	 */
	class cmplz_wizard {
		/**
		 * Singleton instance.
		 *
		 * @var cmplz_wizard
		 */
		private static $_this; // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore -- Legacy property name kept for backward compatibility.

		/**
		 * Constructor: enforce the singleton and register wizard hooks.
		 */
		public function __construct() {
			if ( isset( self::$_this ) ) {
				wp_die(
					sprintf(
						'%s is a singleton class and you cannot create a second instance.',
						esc_html( get_class( $this ) )
					)
				);
			}

			self::$_this = $this;
			// callback from settings.
			add_action( 'cmplz_finish_wizard', array( $this, 'finish_wizard' ), 10, 1 );
			add_action( 'cmplz_before_save_options', array( $this, 'before_save_options' ), 10, 5 );
		}

		/**
		 * Get the singleton instance.
		 *
		 * @return self
		 */
		public static function this() {
			return self::$_this;
		}

		/**
		 * On click finish wizard
		 *
		 * @return void
		 */
		public function finish_wizard() {
			if ( ! cmplz_user_can_manage() ) {
				return;
			}
			update_option( 'cmplz_wizard_completed_once', true );
			// ensure some default values.
			cmplz_update_all_banners();
		}

		/**
		 * Do stuff when a field in the wizard is saved.
		 *
		 * @param array       $options     Options being saved.
		 * @param string|bool $field_id    ID of the saved field.
		 * @param mixed       $field_value New value of the field.
		 * @param mixed       $prev_value  Previous value of the field.
		 * @param string|bool $type        Field type.
		 *
		 * @return array
		 */
		public function before_save_options( $options = array(), $field_id = false, $field_value = false, $prev_value = false, $type = false ): array {
			if ( ! cmplz_admin_logged_in() ) {
				return $options;
			}

			// clear cookieshredder list, if cps is enabled.
			if ( 'consent_per_service' === $field_id && 'yes' === $field_value ) {
				cmplz_delete_transient( 'cmplz_cookie_shredder_list' );
			}

			if ( $field_value === $prev_value ) {
				return $options;
			}

			// if these values are changed, ensure that the services sync starts again.
			if ( 'uses_thirdparty_services' === $field_id || 'uses_social_media' === $field_id ) {
				COMPLIANZ::$sync->resync();
			}

			update_option( 'cmplz_documents_update_date', time() );
			$enable_categories = false;
			$uses_tagmanager   = ( $options['compile_statistics'] ?? false ) === 'google-tag-manager';

			// if the cookie banner is enabled by the user, add complianz cookies to the cookies array.
			if ( 'enable_cookie_banner' === $field_id && 'yes' === $field_value ) {
				$prefix  = COMPLIANZ::$banner_loader->get_cookie_prefix();
				$cookies = array( $prefix . 'functional', $prefix . 'statistics', $prefix . 'preferences', $prefix . 'marketing' );
				if ( ! cmplz_uses_statistic_cookies() ) {
					unset( $cookies[1] );
				}
				$cookies += array( $prefix . 'policy_id', $prefix . 'consented_services', $prefix . 'banner-status', $prefix . 'saved_categories' );
				foreach ( $cookies as $cookie ) {
					$cookie = new CMPLZ_COOKIE( $cookie );
					$cookie->save( true );
				}
			}

			/* if tag manager fires scripts, cats should be enabled for each cookiebanner. */
			if ( 'compile_statistics' === $field_id && 'google-tag-manager' === $field_value ) {
				$enable_categories = true;
			}

			if ( ( 'consent_for_anonymous_stats' === $field_id ) && 'yes' === $field_value ) {
				$enable_categories = true;
			}

			if ( 'a_b_testing' === $field_id && ! $field_value ) {
				$options['a_b_testing_buttons'] = false;
			}

			// when ab testing is just enabled icw TM, cats should be enabled for each banner.
			if ( ( 'a_b_testing_buttons' === $field_id && true === $field_value && false === $prev_value ) ) {
				if ( $uses_tagmanager ) {
					$enable_categories = true;
				}
			}

			if ( $enable_categories ) {
				$banners = cmplz_get_cookiebanners();
				if ( ! empty( $banners ) ) {
					foreach ( $banners as $banner ) {
						$banner                 = cmplz_get_cookiebanner( $banner->ID );
						$banner->use_categories = 'view-preferences';
						$banner->save();
					}
				}
			}

			// save last changed date.
			COMPLIANZ::$banner_loader->update_cookie_policy_date();

			$fields = COMPLIANZ::$config->fields;
			// if the fieldname is from the "revoke cookie consent on change" list, change the policy if it's changed.

			$ids   = array_column( $fields, 'id' );
			$index = array_search( $field_id, $ids, true );
			$field = $fields[ $index ] ?? false;
			if ( $field && isset( $field['revoke_consent_onchange'] ) && $field['revoke_consent_onchange'] && ! $this->is_unchanged_default( $field, $field_value ) ) {
				COMPLIANZ::$banner_loader->upgrade_active_policy_id();
				if ( ! get_option( 'cmplz_generate_new_cookiepolicy_snapshot' ) ) {
					update_option( 'cmplz_generate_new_cookiepolicy_snapshot', time(), false );
				}
			}

			// if the region is not EU anymore, and it was previously enabled for EU / eu_consent_regions, reset impressum.
			if ( ( 'regions' === $field_id ) && cmplz_get_option( 'eu_consent_regions' ) === 'yes' ) {
				if ( is_array( $field_value ) && ! in_array( 'eu', $field_value ) ) {
					$options['eu_consent_regions'] = 'no';
				} elseif ( is_string( $field_value ) && 'eu' !== $field_value ) {
					$options['eu_consent_regions'] = 'no';
				}
			}

			$generate_css = false;
			// update google analytics service depending on anonymization choices.
			if ( 'compile_statistics' === $field_id
				|| 'compile_statistics_more_info' === $field_id
				|| 'compile_statistics_more_info_tag_manager' === $field_id
			) {
				COMPLIANZ::$banner_loader->maybe_add_statistics_service();
				$generate_css = true;
			}

			/**
			 * If TCF was just disabled or enabled, regenerate the css.
			 */
			if ( 'uses_ad_cookies_personalized' === $field_id ) {
				$generate_css = true;
			}

			if ( 'uses_ad_cookies' === $field_id && 'no' === $field_value ) {
				$options['uses_ad_cookies_personalized'] = 'no';
			}

			if ( 'children-safe-harbor' === $field_id && cmplz_get_option( 'targets-children' ) === 'no' ) {
				$options['children-safe-harbor'] = 'no';
			}

			// when region or policy generation type is changed, update cookiebanner version to ensure the changed banner is loaded.
			if ( $generate_css || 'privacy-statement' === $field_id || 'regions' === $field_id || 'cookie-statement' === $field_id ) {
				cmplz_update_all_banners();
			}

			// Disable German imprint appendix option when eu_consent_regions is no.
			$german_imprint = ( $options['german_imprint_appendix'] ?? false ) === 'yes';
			if ( 'eu_consent_regions' === $field_id
				&& 'no' === $field_value
				&& $german_imprint ) {
				$options['german_imprint_appendix'] = 'no';
			}
			return $options;
		}

		/**
		 * Lock the wizard for further use while it's being edited by the current user.
		 * */
		public function lock_wizard(): void {
			set_transient( 'cmplz_wizard_locked_by_user', get_current_user_id(), apply_filters( 'cmplz_wizard_lock_time', 2 * MINUTE_IN_SECONDS ) );
		}

		/**
		 * Check if the wizard is locked by another user
		 * */
		public function wizard_is_locked(): bool {
			$user_id      = get_current_user_id();
			$lock_user_id = $this->get_lock_user();
			return $lock_user_id && $lock_user_id !== $user_id;
		}

		/**
		 * Get the user that has locked the wizard
		 *
		 * @return int
		 */
		public function get_lock_user(): int {
			$user_id = get_transient( 'cmplz_wizard_locked_by_user' ) ? get_transient( 'cmplz_wizard_locked_by_user' ) : get_current_user_id();
			return (int) $user_id;
		}

		/**
		 * Whether a field that was never stored is being saved with the value it already showed by default.
		 *
		 * A field added in an update has no stored value, so the wizard displays its default and posts it on the
		 * next save. That is not a change, so it must not revoke consent. Only call this from the
		 * cmplz_before_save_options filter: the new value is written after it runs, so the stored option still
		 * holds the state before this save. The default is sanitized like the incoming value (an empty checkbox is 0).
		 *
		 * @param array $field       Field config.
		 * @param mixed $field_value New value of the field, sanitized.
		 *
		 * @return bool
		 */
		private function is_unchanged_default( array $field, $field_value ): bool {
			$stored = get_option( 'cmplz_options', array() );
			if ( ! is_array( $stored ) || isset( $stored[ $field['id'] ] ) ) {
				return false;
			}

			$default = apply_filters( 'cmplz_default_value', $field['default'] ?? '', $field['id'], $field ) ?? '';
			$default = cmplz_sanitize_field( $default, cmplz_sanitize_field_type( $field['type'] ?? 'text' ), $field['id'] );
			if ( is_array( $field_value ) || is_array( $default ) ) {
				// Lists are compared by their values, keyed arrays by their keys, so the order of ticked options does not matter.
				$normalize = static function ( $value ): array {
					$value = (array) $value;
					if ( array_keys( $value ) === array_keys( array_values( $value ) ) ) {
						sort( $value );
					} else {
						ksort( $value );
					}
					return $value;
				};
				return $normalize( $field_value ) == $normalize( $default ); // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual -- 1 vs '1' must not matter.
			}

			return (string) $field_value === (string) $default;
		}

		/**
		 * Whether the wizard has been completed at least once.
		 *
		 * @return bool
		 */
		public function wizard_completed_once() {
			return get_option( 'cmplz_wizard_completed_once', false );
		}
	}
	// phpcs:enable PEAR.NamingConventions.ValidClassName.StartWithCapital, PEAR.NamingConventions.ValidClassName.Invalid

} //class closure
