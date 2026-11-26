<?php // phpcs:ignore Squiz.Commenting.FileComment.Missing

/**
 * Complianz Dynamic Notifications
 *
 * This code is used to fetch and store the dynamic notifications from Complianz and inform the user of the latest news.
 *
 * @package Complianz
 * @since 7.5.3
 */

const COMPLIANZ_DYNAMIC_NOTIFICATIONS_ENDPOINT             = 'https://notifications.complianz.io/';
const COMPLIANZ_DYNAMIC_NOTIFICATIONS_TRANSIENT_KEY        = 'cmplz_dn_notifications_';
const COMPLIANZ_DYNAMIC_NOTIFICATIONS_TRANSIENT_EXPIRATION = 24 * HOUR_IN_SECONDS;
const COMPLIANZ_DYNAMIC_NOTIFICATIONS_FETCH_EVENT          = 'cmplz_dn_fetch_event';
const COMPLIANZ_DYNAMIC_NOTIFICATIONS_ATTEMPTS_OPTION      = 'cmplz_dn_attempts';

// Hook into the admin_init action to schedule the notifications update.
add_action( 'admin_init', 'cmplz_dn_update' );
// Fetch the notifications in the background, so the admin request never waits for the endpoint.
add_action( COMPLIANZ_DYNAMIC_NOTIFICATIONS_FETCH_EVENT, 'cmplz_dn_fetch_notifications' );
// Hook into the cmplz_warning_types filter to include the notifications in the warnings array.
add_filter( 'cmplz_warning_types', 'cmplz_dn_include_notifications' );

/**
 * Schedule an update of the notifications when they are not cached yet.
 *
 * Never fetches inline: the request to the endpoint runs in a separate WP-Cron request. When
 * WP-Cron is disabled, it runs at shutdown after the response was sent, where the server supports that.
 *
 * @return void
 */
function cmplz_dn_update(): void {
	// Allow the dynamic notifications to be disabled.
	if ( ! apply_filters( 'cmplz_dynamic_notifications_enabled', true ) ) {
		return;
	}

	// Retrieve the user language, return just the language code (en, nl, de, etc).
	$language = cmplz_dn_get_language();
	// Generate the transient key.
	$transient_key = cmplz_dn_transient_key( $language );

	// Notifications are cached, nothing to do.
	if ( is_array( get_transient( $transient_key ) ) ) {
		return;
	}

	// Not more than one attempt per 24 hours, even if the transient does not persist (e.g. a broken object cache).
	if ( ! cmplz_dn_claim_attempt( $language ) ) {
		return;
	}

	// WP-Cron disabled: fetch at shutdown, after the response was sent to the browser. Without
	// fastcgi_finish_request() that would keep the request open, so only schedule the event
	// (a system cron may still run it).
	if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON && function_exists( 'fastcgi_finish_request' ) ) {
		add_action(
			'shutdown',
			function () use ( $language ) {
				fastcgi_finish_request();
				cmplz_dn_fetch_notifications( $language );
			}
		);
		return;
	}

	wp_schedule_single_event( time(), COMPLIANZ_DYNAMIC_NOTIFICATIONS_FETCH_EVENT, array( $language ) );
}

/**
 * Register a fetch attempt for a language, unless one was already made in the last 24 hours.
 *
 * The attempts are stored in an option instead of a transient, so the throttle also works
 * when transients do not persist.
 *
 * @param string $language The language code.
 * @return bool True if the attempt was claimed, false if one was already made recently.
 */
function cmplz_dn_claim_attempt( string $language ): bool {
	$attempts = get_option( COMPLIANZ_DYNAMIC_NOTIFICATIONS_ATTEMPTS_OPTION, array() );
	$attempts = is_array( $attempts ) ? $attempts : array();
	$now      = time();

	if ( isset( $attempts[ $language ] ) && $now - (int) $attempts[ $language ] < COMPLIANZ_DYNAMIC_NOTIFICATIONS_TRANSIENT_EXPIRATION ) {
		return false;
	}

	$attempts[ $language ] = $now;
	update_option( COMPLIANZ_DYNAMIC_NOTIFICATIONS_ATTEMPTS_OPTION, $attempts, false );

	return true;
}

/**
 * Fetch the notifications from the endpoint.
 *
 * @param string $language The language code.
 * @return void
 */
function cmplz_dn_fetch_notifications( string $language ): void {
	// Define the transient key.
	$transient_key = cmplz_dn_transient_key( $language );

	$response = cmplz_dn_request( $language );

	// The endpoint only supports a fixed list of languages and answers 400 for any other. Fall back to English.
	if ( 'en' !== $language && 400 === (int) wp_remote_retrieve_response_code( $response ) ) {
		$response = cmplz_dn_request( 'en' );
	}

	if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
		// Avoid fetching notifications repeatedly if the response is invalid and set an empty array.
		cmplz_dn_set_empty_notifications( $transient_key );
		return;
	}

	$raw_notifications = json_decode( wp_remote_retrieve_body( $response ), true );

	// Check if the response is valid.
	if ( ! is_array( $raw_notifications ) || ! isset( $raw_notifications['notifications'] ) || ! is_array( $raw_notifications['notifications'] ) ) {
		// Avoid fetching notifications repeatedly if the response is invalid and set an empty array.
		cmplz_dn_set_empty_notifications( $transient_key );
		return;
	}

	// Store the notifications for 24 hours.
	set_transient( $transient_key, $raw_notifications['notifications'], COMPLIANZ_DYNAMIC_NOTIFICATIONS_TRANSIENT_EXPIRATION );
}

/**
 * Request the notifications for a language from the endpoint.
 *
 * @param string $language The language code.
 * @return array|WP_Error The response.
 */
function cmplz_dn_request( string $language ) {
	$payload = array(
		'version'  => COMPLIANZ::$wsc_scanner->get_cmplz_version(),
		'language' => $language,
	);

	return wp_remote_post(
		cmplz_dn_endpoint(),
		array(
			'headers' => array(
				'Content-Type' => 'application/json',
			),
			'body'    => wp_json_encode( $payload ),
			'timeout' => 5,
		)
	);
}

/**
 * Set the transient to an empty array to avoid fetching notifications repeatedly.
 *
 * @param string $transient_key The transient key.
 * @return void
 */
function cmplz_dn_set_empty_notifications( string $transient_key ): void {
	set_transient( $transient_key, array(), COMPLIANZ_DYNAMIC_NOTIFICATIONS_TRANSIENT_EXPIRATION );
}

/**
 * Include the notifications in the warnings array.
 *
 * @param array $warnings The warnings array.
 * @return array The warnings array with the notifications included.
 */
function cmplz_dn_include_notifications( array $warnings ): array {
	$language      = cmplz_dn_get_language();
	$transient_key = cmplz_dn_transient_key( $language );
	$notifications = get_transient( $transient_key );

	if ( empty( $notifications ) ) {
		return $warnings;
	}

	// Map the notifications to the warnings array.
	$var = cmplz_dn_map_notifications( $notifications );

	// Add the notifications to the warnings array.
	$warnings = array_merge( $var, $warnings );

	return $warnings;
}

/**
 * Map the notifications to the warnings array.
 *
 * @param array $notifications The notifications array.
 * @return array The warnings array with the notifications included.
 */
function cmplz_dn_map_notifications( array $notifications ): array {
	$mapped_notifications = array();
	$allowed_statuses     = array( 'open', 'urgent', 'premium', 'completed' );

	// Map the notifications to the warnings array.
	// First, skip the malformed and the expired notifications.
	$notifications = array_filter(
		$notifications,
		function ( $notification ) {
			if ( ! is_array( $notification ) ) {
				return false;
			}

			// If the expiration is not set, it means it's a permanent notification. So must be included.
			if ( ! isset( $notification['expiration'] ) ) {
				return true;
			}

			// If the expiration is set, check if it's in the future.
			$expiration = is_string( $notification['expiration'] ) ? strtotime( $notification['expiration'] ) : false;

			return $expiration > time();
		}
	);

	foreach ( $notifications as $notification ) {
		// Nothing to show without an id and a message, skip the notification.
		if ( empty( $notification['id'] ) || ! is_scalar( $notification['id'] ) || empty( $notification['content'] ) || ! is_string( $notification['content'] ) ) {
			continue;
		}

		$id = sanitize_key( (string) $notification['id'] );
		if ( '' === $id ) {
			continue;
		}

		$status = isset( $notification['status'] ) && in_array( $notification['status'], $allowed_statuses, true ) ? $notification['status'] : 'open';

		// Explicit allowlist of display fields. Remote data must never reach a callable:
		// warning_condition, success_conditions and relation are deliberately never copied
		// from the endpoint response. See cmplz_dn_resolve_condition() for conditional display.
		$mapped = array(
			'status'              => $status,
			'dismissible'         => isset( $notification['dismissible'] ) ? (bool) $notification['dismissible'] : true,
			'admin_notice'        => ! empty( $notification['admin_notice'] ),
			'plus_one'            => ! empty( $notification['plus_one'] ),
			'include_in_progress' => false,
			'warning_condition'   => cmplz_dn_resolve_condition( $notification['condition'] ?? '' ) ? '_true_' : '_false_',
			'success_conditions'  => 'completed' === $status ? array( '_true_' ) : array(),
		);

		if ( ! empty( $notification['url'] ) && is_string( $notification['url'] ) ) {
			$url = esc_url_raw( $notification['url'], array( 'https' ) );
			if ( '' !== $url ) {
				$mapped['url'] = $url;
			}
		}

		// Plain-text title, used by the admin notice as the "Read more about %s" link text.
		if ( ! empty( $notification['title'] ) && is_string( $notification['title'] ) ) {
			$title = sanitize_text_field( $notification['title'] );
			if ( '' !== $title ) {
				$mapped['title'] = $title;
			}
		}

		// If it's an admin notice, we need to show it as open.
		$message_key            = $mapped['admin_notice'] ? 'open' : $status;
		$mapped[ $message_key ] = wp_kses_post( $notification['content'] );

		$mapped_notifications[ $id ] = $mapped;
	}

	return $mapped_notifications;
}

/**
 * Resolve a named display condition for a remote notification.
 *
 * Remote notifications may only reference a condition by name. Each name maps to a local
 * closure; a condition string is never evaluated as code. Unknown names resolve to false,
 * so a typo on the endpoint fails closed. A leading '!' negates the condition.
 *
 * @param mixed $condition The condition name sent by the endpoint ('' means always show).
 * @return bool Whether the notification should be shown.
 */
function cmplz_dn_resolve_condition( $condition ): bool {
	if ( '' === $condition || null === $condition ) {
		return true;
	}
	if ( ! is_string( $condition ) ) {
		return false;
	}

	/**
	 * Filter the named conditions available to remote notifications.
	 *
	 * @param array<string,callable> $conditions Condition name => closure returning bool.
	 */
	$conditions = apply_filters(
		'cmplz_dynamic_notification_conditions',
		array(
			'always'       => static function (): bool {
				return true;
			},
			'is_free'      => static function (): bool {
				return ! defined( 'cmplz_premium' );
			},
			'is_premium'   => static function (): bool {
				return defined( 'cmplz_premium' );
			},
			'is_multisite' => static function (): bool {
				return is_multisite();
			},
		)
	);

	$negate = 0 === strpos( $condition, '!' );
	$name   = ltrim( $condition, '!' );

	if ( ! isset( $conditions[ $name ] ) || ! is_callable( $conditions[ $name ] ) ) {
		return false;
	}

	$result = (bool) call_user_func( $conditions[ $name ] );

	return $negate ? ! $result : $result;
}

/**
 * Get the endpoint for the notifications.
 *
 * @return string The endpoint for the notifications.
 */
function cmplz_dn_endpoint(): string {
	return COMPLIANZ_DYNAMIC_NOTIFICATIONS_ENDPOINT;
}

/**
 * Get the transient key for the notifications.
 *
 * @param string $language The language code.
 * @return string The transient key for the notifications.
 */
function cmplz_dn_transient_key( string $language ): string {
	return COMPLIANZ_DYNAMIC_NOTIFICATIONS_TRANSIENT_KEY . $language;
}

/**
 * Get the language for the notifications.
 *
 * @return string The language for the notifications.
 */
function cmplz_dn_get_language(): string {
	$locale = get_user_locale();
	$pos    = strpos( $locale, '_' );
	// Cut the language, stripping after the underscore. Locales without one (ja, fi, ca) are used as is.
	return false === $pos ? $locale : substr( $locale, 0, $pos );
}

/** End of Dynamic Notifications */
function cmplz_load_warning_types() {
	$warnings = apply_filters(
		'cmplz_warning_types',
		array(
			'phpversion'                     => array(
				'warning_condition'   => 'NOT cmplz_has_recommended_phpversion',
				'urgent'              => __( 'Your PHP version is lower than the recommended PHP version. Some features are not available. Support for this PHP version will be dropped soon.', 'complianz-gdpr' ),
				'url'                 => 'https://complianz.io/php-version/',
				'plus_one'            => true,
				'include_in_progress' => true,
			),
			'migrate_js'                     => array(
				'warning_condition' => 'get_value_enable_migrate_js==yes',
				'open'              => __( 'Migrate.js, which allowed a smooth upgrade to 6.0, has been deprecated.', 'complianz-gdpr' ),
				'url'               => 'https://complianz.io/help/wordpress/instructions/migrate-js-deprecated/',
				'admin_notice'      => true,
			),
			'enable_quebec_region'           => array(
				'warning_condition' => 'cmplz_requires_quebec_notice',
				'open'              => cmplz_quebec_notice(),
				'url'               => 'https://complianz.io/help/privacy-and-regulations/legal-requirements/quebec-bill-64/',
				'admin_notice'      => true,
				'dismissible'       => true,
			),
			'no-dnt'                         => array(
				'success_conditions' => array(
					'get_value_respect_dnt==yes',
				),
				'completed'          => __( 'Do Not Track and Global Privacy Control are respected.', 'complianz-gdpr' ),
				'url'                => 'https://complianz.io/help/wordpress/configuration/browser-privacy-controls/',
			),
			'ajax_fallback'                  => array(
				'warning_condition' => 'get_option_cmplz_ajax_fallback_active',
				'urgent'            => __( 'Please check if your REST API is loading correctly. Your site currently is using the slower Ajax fallback method to load the settings.', 'complianz-gdpr' ),
				'url'               => 'https://complianz.io/instructions/how-to-debug-a-blank-settings-page-in-complianz/',
				'plus_one'          => true,
			),
			'has_formal'                     => array(
				'success_conditions'  => array(
					'NOT document->locale_has_formal_variant',
				),
				'open'                => __( 'You have currently selected an informal language, which will result in informal use of language on the legal documents. If you prefer the formal style, you can activate this in the general settings.', 'complianz-gdpr' ),
				'include_in_progress' => true,
				'url'                 => 'https://complianz.io/help/wordpress/configuration/informal-language-in-legal-documents/',

			),
			'google-fonts'                   => array(
				'plus_one'            => true,
				'warning_condition'   => 'banner_loader->show_google_fonts_notice',
				'success_conditions'  => array(),
				// translators: %s is a placeholder for the anchor link.
				'open'                => __( 'Google Fonts requires your attention.', 'complianz-gdpr' ) . ' ' . __( 'We have added additional support and recommend reviewing your settings.', 'complianz-gdpr' ) . ' ' . cmplz_sprintf( __( 'Please read this %1$sarticle%2$s to read our position on self-hosting Google Fonts and Privacy by Design.', 'complianz-gdpr' ), '<a href="https://complianz.io/help/wordpress/configuration/self-hosting-google-fonts-for-wordpress/" target="_blank">', '</a>' ),
				'include_in_progress' => true,
				'url'                 => 'https://complianz.io/help/wordpress/configuration/self-hosting-google-fonts-for-wordpress/',
			),
			'cookies-changed'                => array(
				'plus_one'            => true,
				'warning_condition'   => 'scan->cookies_changed',
				'success_conditions'  => array(),
				'completed'           => __( 'No cookie changes have been detected.', 'complianz-gdpr' ),
				'open'                => __( 'Cookie changes have been detected.', 'complianz-gdpr' ) . ' ' . __( 'Please review your cookies for changes.', 'complianz-gdpr' ),
				'include_in_progress' => true,
			),
			'no-cookie-scan'                 => array(
				'success_conditions'  => array(
					'banner_loader->get_last_cookie_scan_date',
				),
				// translators: %s is a placeholder for the last scan completed date.
				'completed'           => cmplz_sprintf( __( 'Last site scan completed on %s.', 'complianz-gdpr' ), COMPLIANZ::$banner_loader->get_last_cookie_scan_date() ),
				'open'                => __( 'No site scan has been completed yet.', 'complianz-gdpr' ),
				'include_in_progress' => true,
				'dismissible'         => false,
			),
			'all-pages-created'              => array(
				'warning_condition'   => 'get_option_cmplz_wizard_completed_once',
				'success_conditions'  => array(
					'documents_admin->all_required_pages_created',
				),
				'completed'           => __( 'All required pages have been generated.', 'complianz-gdpr' ),
				'open'                => __( 'Not all required pages have been generated.', 'complianz-gdpr' ),
				'include_in_progress' => true,
			),
			'hardening'                      => array(
				'warning_condition'   => 'admin->no_security_plugin_active',
				'open'                => __( 'Harden your website and quickly detect vulnerabilities with Really Simple Security', 'complianz-gdpr' ),
				'include_in_progress' => true,
				'url'                 => '#tools/security',
			),
			'ga-needs-configuring'           => array(
				'warning_condition'   => 'banner_loader->uses_google_analytics',
				'success_conditions'  => array(
					'banner_loader->analytics_configured',
				),
				'open'                => __( 'Google Analytics is being used, but is not configured in Complianz.', 'complianz-gdpr' ),
				'include_in_progress' => true,
			),
			'gtm-needs-configuring'          => array(
				'warning_condition'   => 'banner_loader->uses_google_tagmanager',
				'success_conditions'  => array(
					'banner_loader->tagmanager_configured',
				),
				'open'                => __( 'Google Tag Manager is being used, but is not configured in Complianz.', 'complianz-gdpr' ),
				'include_in_progress' => true,
			),
			'matomo-needs-configuring'       => array(
				'warning_condition'   => 'banner_loader->uses_matomo',
				'success_conditions'  => array(
					'banner_loader->matomo_configured',
				),
				'open'                => __( 'Matomo is being used, but is not configured in Complianz.', 'complianz-gdpr' ),
				'include_in_progress' => true,
			),
			'docs-need-updating'             => array(
				'success_conditions'  => array(
					'NOT document->documents_need_updating',
				),
				'open'                => __( 'Your documents have not been updated in the past 12 months. Run the wizard to check your settings.', 'complianz-gdpr' ),
				'include_in_progress' => true,
			),
			'cookies-incomplete'             => array(
				'warning_condition'   => 'NOT banner_loader->use_cdb_api',
				'success_conditions'  => array(
					'NOT sync->has_empty_cookie_descriptions',
				),
				'open'                => __( 'You have cookies with incomplete descriptions.', 'complianz-gdpr' ) . ' '
							. __( 'Enable the cookiedatabase.org API for automatic descriptions, or add these manually.', 'complianz-gdpr' ),
				'include_in_progress' => true,
				'url'                 => '#wizard/cookie-descriptions',
			),
			'double-stats'                   => array(
				'success_conditions'  => array(
					'NOT get_option_cmplz_double_stats',
				),
				'warning_condition'   => 'cmplz_uses_statistics',
				'open'                => __( 'You have a duplicate implementation of your statistics tool on your site.', 'complianz-gdpr' ) .
							__( 'After the issue has been resolved, please re-run a scan to clear this message.', 'complianz-gdpr' ),
				'include_in_progress' => true,
				'dismissible'         => true,
				'url'                 => 'https://complianz.io/help/wordpress/configuration/duplicate-implementation-of-analytics/',
			),
			'console-errors'                 => array(
				'warning_condition'   => 'banner_loader->site_needs_cookie_warning',
				'success_conditions'  => array(
					'NOT cmplz_get_console_errors',
				),
				'open'                => __( 'JavaScript errors are detected on the front-end of your site. This may break the consent banner functionality.', 'complianz-gdpr' )
							. '<br />' . __( 'Last error in the console:', 'complianz-gdpr' )
							. '<div style="color:red">'
							. cmplz_get_console_errors()
							. '</div>',
				'include_in_progress' => true,
				'url'                 => 'https://complianz.io/help/wordpress/configuration/cookie-banner-does-not-appear/',
			),
			'cookie-banner-enabled'          => array(
				'success_conditions'  => array(
					'cmplz_cookiebanner_should_load(true)',
				),
				'completed'           => __( 'Your site requires a consent banner, which has been enabled.', 'complianz-gdpr' ),
				'urgent'              => __( 'Your site is not configured to show a consent banner at the moment.', 'complianz-gdpr' ),
				'include_in_progress' => true,
				'dismissible'         => true,
				'url'                 => 'https://complianz.io/help/wordpress/configuration/cookie-banner-does-not-appear/',
			),
			'pretty-permalinks-error'        => array(
				'success_conditions'  => array(
					'get_option_permalink_structure',
				),
				'plus_one'            => true,
				'urgent'              => __( 'Pretty permalinks are not enabled on your site. This can cause issues with the REST API, used by Complianz.', 'complianz-gdpr' ),
				'include_in_progress' => true,
				'dismissible'         => false,
				'url'                 => admin_url( 'options-permalink.php' ),
			),
			'uploads-folder-writable'        => array(
				'success_conditions'  => array(
					'cmplz_uploads_folder_writable',
				),
				'plus_one'            => true,
				'urgent'              => __( 'Your uploads folder is not writable. Complianz needs this folder to save the consent banner CSS.', 'complianz-gdpr' ),
				'include_in_progress' => true,
				'dismissible'         => false,
				'url'                 => 'https://complianz.io/help/wordpress/configuration/folder-permissions/',
			),
			'custom-google-maps'             => array(
				'warning_condition'   => 'cmplz_uses_google_maps',
				'success_conditions'  => array(
					'cmplz_google_maps_integration_enabled',
				),
				'plus_one'            => false,
				'open'                => __( 'We see you have enabled Google Maps as a service, but we can\'t find an integration. You can integrate manually if needed.', 'complianz-gdpr' ),
				'include_in_progress' => true,
				'url'                 => 'https://complianz.io/help/wordpress/customization/custom-google-maps-implementation/',
			),
			'other-cookie-plugins'           => array(
				'warning_condition'   => 'cmplz_detected_cookie_plugin',
				'plus_one'            => true,
				// translators: %s is a placeholder for the compliance detected plugin name.
				'urgent'              => cmplz_sprintf( __( 'We have detected the %s plugin on your website.', 'complianz-gdpr' ), cmplz_detected_cookie_plugin( true ) ) . '&nbsp;' . __( 'As Complianz handles all the functionality this plugin provides, you should disable this plugin to prevent unexpected behaviour.', 'complianz-gdpr' ),
				'include_in_progress' => true,
				'dismissible'         => false,
			),
			'advertising-enabled'            => array(
				'warning_condition'   => 'get_value_uses_ad_cookies==yes',
				'premium'             => __( 'Are you showing ads on your site? Consider implementing TCF.', 'complianz-gdpr' ),
				'include_in_progress' => false,
				'dismissible'         => false,
				'url'                 => 'https://complianz.io/help/wordpress/configuration/guide-to-implementing-the-tcf-framework-in-wordpress-with-complianz/',
			),
			'sync-privacy-statement'         => array(
				'premium'             => __( 'Create a Privacy Statement and other Legal Documents with Complianz.', 'complianz-gdpr' ),
				'include_in_progress' => false,
				'dismissible'         => false,
				'url'                 => 'https://complianz.io/pricing/',
			),
			'ecommerce-legal'                => array(
				'warning_condition'   => 'cmplz_ecommerce_legal',
				'premium'             => __( 'Legal compliance for webshops.', 'complianz-gdpr' ),
				'include_in_progress' => false,
				'dismissible'         => false,
				'url'                 => 'https://complianz.io/help/wordpress/configuration/legal-compliance-for-ecommerce/',
			),
			'configure-tag-manager'          => array(
				'warning_condition'   => 'cmplz_uses_google_tagmanager_or_analytics',
				'premium'             => __( 'Learn more about Google Consent Mode V2.', 'complianz-gdpr' ),
				'include_in_progress' => false,
				'dismissible'         => false,
				'url'                 => 'https://complianz.io/help/wordpress/configuration/consent-mode-for-7-0/',
			),
			'targeting-multiple-regions'     => array(
				'warning_condition'   => 'cmplz_targeting_multiple_regions',
				'premium'             => __( 'Are you targeting multiple regions?', 'complianz-gdpr' ),
				'include_in_progress' => false,
				'dismissible'         => false,
				'url'                 => 'https://complianz.io/help/wordpress/faq/what-regions-do-i-target/',
			),
			'textdomain-just-in-time-notice' => array(
				'dismissible'         => true,
				'open'                => __( 'You might see a notice about Complianz when debugging your WordPress website. Although not a breaking issue, please know we\'re attending to this issue.', 'complianz-gdpr' ),
				'warning_condition'   => ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? '_true_' : '_false_',
				'include_in_progress' => false,
				'url'                 => 'https://complianz.io/help/wordpress/faq/about-function-_load_textdomain_just_in_time-was-called-incorrectly/',
			),
			'clarity-consent-mode'           => array(
				'plus_one'          => true,
				'dismissable'       => true,
				'warning_condition' => 'get_value_compile_statistics==clarity',
				'open'              => __( 'Clarity Consent Mode V2 is now available. Please enable it in the wizard.', 'complianz-gdpr' ),
				'url'               => '#wizard/statistics-configuration',
			),
		)
	);

	return cmplz_add_referral_to_warnings( $warnings );
}

/**
 * Add referral parameters to premium field upgrade links
 *
 * @param array $fields Fields array.
 * @return array Modified fields with referral parameters.
 */
function cmplz_add_referral_to_warnings( $fields ) {
	foreach ( $fields as $key => &$field ) {
		if ( isset( $field['url'] ) && strpos( $field['url'], 'complianz.io' ) !== false ) {
			$field['url'] = cmplz_get_referral_url( 'warnings', $key, $field['url'] );
		}
	}

	return $fields;
}
