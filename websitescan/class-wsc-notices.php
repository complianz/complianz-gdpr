<?php

defined('ABSPATH') or die();

if (!class_exists("cmplz_wsc_notices")) {

	class cmplz_wsc_notices
	{

		public function init_hooks()
		{
			// If any hooks are needed for notices
			add_filter('cmplz_field_notices', array($this, 'notices'));
			add_filter('cmplz_warning_types', array($this, 'wsc_scan_add_warnings'));
		}

		/**
		 * Push some error messages if we find any
		 *
		 * @param array $notices
		 *
		 * @return array
		 */
		public function notices(array $notices): array
		{
			if (!cmplz_user_can_manage()) {
				return $notices;
			}
			if (get_option('cmplz_wsc_error_email_mismatch')) {
				$notices[] = [
					'field_id' => cmplz_wsc::WSC_EMAIL_OPTION_KEY,
					'label'    => 'warning',
					'title'    => __("E-mail mismatch", 'complianz-gdpr'),
					'text'     => __("The e-mail that you are authenticating does not match the e-mail stored in your settings currently. Please clear the e-mail, save, then enter your e-mail address again.", 'complianz-gdpr'),
				];
			}

			if (get_option('cmplz_wsc_error_missing_token')) {
				$notices[] = [
					'field_id' => cmplz_wsc::WSC_EMAIL_OPTION_KEY,
					'label'    => 'warning',
					'title'    => __("Missing token", 'complianz-gdpr'),
					'text'     => __("The token is missing from the URL which you are using to authenticate.", 'complianz-gdpr'),
					'url'      => 'https://complianz.io/authentication-failed',
				];
			}
			if (get_option('cmplz_wsc_error_email_auth_failed')) {
				$notices[] = [
					'field_id' => cmplz_wsc::WSC_EMAIL_OPTION_KEY,
					'label'    => 'warning',
					'title'    => __("Authentication failed", 'complianz-gdpr'),
					'text'     => __("The authentication of your e-mail address failed. Please try again later.", 'complianz-gdpr'),
					'url'      => 'https://complianz.io/authentication-failed',
				];
			}

			if (get_option('cmplz_wsc_error_token_api')) {
				$notices[] = [
					'field_id' => cmplz_wsc::WSC_EMAIL_OPTION_KEY,
					'label'    => 'warning',
					'title'    => __("Token not retrieved", 'complianz-gdpr'),
					'text'     => __("The token for the api could not be retrieved.", 'complianz-gdpr'),
					'url'      => 'https://complianz.io/authentication-failed',
				];
			}
			if (get_option('cmplz_wsc_error_email_not_sent')) {
				$notices[] = [
					'field_id' => cmplz_wsc::WSC_EMAIL_OPTION_KEY,
					'label'    => 'warning',
					'title'    => __("E-mail verification not sent", 'complianz-gdpr'),
					'text'     => __("The e-mail to verify your e-mail address could not be sent. Please check your e-mail address or try again later.", 'complianz-gdpr'),
					'url'      => 'https://complianz.io/authentication-failed',
				];
			}
			if (!cmplz_wsc_auth::wsc_is_authenticated()) {
				$notices[] = [
					'field_id' => cmplz_wsc::WSC_EMAIL_OPTION_KEY,
					'label'    => 'warning',
					'title'    => __("Try our new Website Scan!", 'complianz-gdpr'),
					'text'     => __("In the latest release of Complianz, we introduce our newest Website Scan. This scan will not only retrieve services and cookies but also help you configure our plugin and keep you up-to-date if changes are made that might need legal changes.", 'complianz-gdpr'),
					'url'      => 'https://complianz.io/about-the-website-scan'
				];
			}
			if (!cmplz_wsc_auth::wsc_is_authenticated()) {
				$notices[] = [
					'field_id' => 'cookie_scan',
					'label'    => 'warning',
					'title'    => __("Try our new Website Scan!", 'complianz-gdpr'),
					'text'     => __("In the latest release of Complianz, we introduce our newest Website Scan. This scan will not only retrieve services and cookies but also help you configure our plugin and keep you up-to-date if changes are made that might need legal changes.", 'complianz-gdpr'),
					'url' => '#settings/settings-cd',
				];
			}
			if (get_option('cmplz_wsc_signup_status') === 'pending') {
				$notices[] = [
					'field_id' => cmplz_wsc::WSC_EMAIL_OPTION_KEY,
					'label' => 'warning',
					'title' => __("Check your email!", 'complianz-gdpr'),
					'text' => __("Your authentication is still on pending, check your emails for a confirmation.", 'complianz-gdpr'),
					'url' => 'https://complianz.io/about-the-website-scan#pending'
				];
			}

			if ( cmplz_wsc_auth::wsc_is_authenticated() && ! COMPLIANZ::$wsc_scanner->wsc_rest_api_accessible() ) {
				$notices[] = [
					'field_id' => 'websitescan_status',
					'label'    => 'warning',
					'title'    => __( 'REST API not accessible', 'complianz-gdpr' ),
					'text'     => __( 'The Website Scan requires the WordPress REST API to be publicly accessible. It appears to be blocked, possibly by a security plugin. The batch scan will not work until this is resolved.', 'complianz-gdpr' ),
					'url'      => 'https://complianz.io/rest-api-required',
				];
			}

			return $notices;
		}


		/**
		 * Add a warning that integrations changed.
		 *
		 * @param array $warnings
		 *
		 * @return array
		 */
		public function wsc_scan_add_warnings(array $warnings): array {
			$wsc_checks_warnings = $this->retrieve_wsc_checks_warnings();

			if ( is_array( $wsc_checks_warnings ) && count( $wsc_checks_warnings ) > 0 ) {
				$warnings = array_merge( $warnings, $wsc_checks_warnings );
			}

			// if not authenticated.
			if ( cmplz_wsc_auth::wsc_is_authenticated() ) {
				return $warnings;
			}

			$warnings['wsc-scan'] = array(
				'plus_one' => true,
				'dismissible' => true,
				'warning_condition' => '_true_',
				'open' => __('You have a new feature! To enable the new and improved Website Scan you need to authenticate your website.', 'complianz-gdpr'),
				'url' => '#settings/settings-cd',
			);

			return $warnings;
		}

		/**
		 * Retrieve warnings from the WSC checks.
		 *
		 * This method retrieves the warnings from the WSC checks by fetching the detections,
		 * checking if they match the notification blocks for the current Complianz version,
		 * and then creating an array of warnings based on these detections.
		 *
		 * @return array The array of warnings retrieved from the WSC checks.
		 */
		private function retrieve_wsc_checks_warnings(): array {
			$warnings   = array();
			$detections = COMPLIANZ::$wsc_scanner->wsc_checks_retrieve_detections();

			if ( is_array( $detections ) && count( $detections ) > 0 ) {
				$blocks        = COMPLIANZ::$wsc_scanner->wsc_checks_notification_blocks();
				$cmplz_version = COMPLIANZ::$wsc_scanner->get_cmplz_version();

				foreach ( $detections as $detection ) {
					if ( isset( $blocks[ $detection['block'] ][ $cmplz_version ] ) ) {

						$block = $blocks[ $detection['block'] ][ $cmplz_version ];

						$warnings[ 'wsc-scan_' . $detection['block'] ] = array(
							'plus_one'          => false,
							'dismissible'       => true,
							'warning_condition' => '_true_',
							$block['type']      => COMPLIANZ::$wsc_scanner->wsc_checks_notification_generate_block_description( $block, $detection, true ),
							'url'               => $block['read_more'],
						);
					}
				}
			}

			return $warnings;
		}
	}
}
