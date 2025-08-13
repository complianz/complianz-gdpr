<?php

defined('ABSPATH') or die();

if (!class_exists("cmplz_wsc_settings")) {

	class cmplz_wsc_settings
	{

		public function init_hooks()
		{
			add_action("cmplz_do_action", array($this, 'handle_wsc_settings_action'), 10, 3);
			add_filter('cmplz_menu', array($this, 'add_website_scan_menu'));
			add_filter('cmplz_fields', array($this, 'add_website_scan_fields'), 80);
			add_action("cmplz_before_save_option", array($this, 'cmplz_before_save_option'), 100, 4);
			add_action('cmplz_every_day_hook', array($this, 'check_failed_user_deletion'));
			add_action('cmplz_store_newsletter_onboarding_consent', array($this, 'cmplz_store_newsletter_onboarding_consent_handler'), 10, 2);
		}


		/**
		 * Handles various onboarding actions.
		 *
		 * This method is responsible for handling different actions related to onboarding.
		 * It checks the user's capability to manage, and based on the action provided,
		 * performs the necessary operations such as sending authentication emails,
		 * resetting website scan, enabling or disabling the website scan, checking token status,
		 * and signing up for the newsletter.
		 *
		 * @param array $data The data associated with the action.
		 * @param string $action The action to be performed.
		 * @param WP_REST_Request $request The REST request object.
		 * @return array The updated data after performing the action.
		 */
		public function handle_wsc_settings_action(array $data, string $action, WP_REST_Request $request): array
		{
			if (!cmplz_user_can_manage()) {
				return [];
			}
			switch ($action) {
				case 'request_activation_email':
					$stored_email = cmplz_get_option(cmplz_wsc::WSC_EMAIL_OPTION_KEY);
					if (is_email($stored_email)) {
						cmplz_wsc_auth::send_auth_email($stored_email);
						// set an option with the signup date
						update_option('cmplz_wsc_signup_date', time(), false);
					}
					break;
				// reset Website Scan
				case 'reset_wsc':
					$reset = $this->cmplz_wsc_reset_websitescan(); // true false
					$data = [
						'result' => $reset,
						'redirect' => cmplz_admin_url('#settings/settings-cd')
					];
					break;
				// just enable the wsc, update the option 'cmplz_wsc_status'
				case 'enable_wsc':
					$data = $this->handle_wsc_actions('enable_wsc');
					break;
				// just disable the wsc, update the option 'cmplz_wsc_status'
				case 'disable_wsc':
					$data = $this->handle_wsc_actions('disable_wsc');
					break;
				// check the token status, return 'updated', 'token_status', 'wsc_status', 'wsc_signup_date'
				case 'get_wsc_status':
					$data = $this->handle_wsc_actions('get_wsc_status');
					break;
				// to be defined
				case 'signup_newsletter':
					$posted_data = $request->get_json_params();
					$email = sanitize_email($posted_data['email']);
					if (is_email($email)) {
						cmplz_wsc_auth::newsletter_sign_up($email);
						// newsletter_consent
						cmplz_wsc_onboarding::schedule_store_onboarding_consent('cmplz_store_newsletter_onboarding_consent', 1200, ['newsletter_consent', $posted_data]);
					}
					break;
			}
			return $data;
		}

		/**
		 * Handles WSC (Website Scan) actions.
		 *
		 * This method handles actions related to enabling or disabling the WSC feature.
		 * It updates the WSC status option based on the provided action.
		 * It also retrieves the token, WSC status, and WSC signup date options.
		 *
		 * @param string $action The action to be performed (enable_wsc or disable_wsc).
		 * @return array An array containing the updated date, token status, WSC status, and WSC signup date.
		 */
		private function handle_wsc_actions(string $action): array
		{
			$data = [];
			if (empty($action)) return $data;
			if ($action === 'enable_wsc') update_option('cmplz_wsc_status', 'enabled', false);
			if ($action === 'disable_wsc') update_option('cmplz_wsc_status', 'disabled', false);

			$token_status = $this->get_token_status(); // could be false

			$updated_wsc_status = get_option('cmplz_wsc_status'); // Website Scan status
			$wsc_signup_date = get_option('cmplz_wsc_signup_date') ? get_option('cmplz_wsc_signup_date') : false;
			$wsc_lock = COMPLIANZ::$wsc_onboarding->wsc_locked();

			return [
				'token_status' => $token_status, // token status
				'wsc_status' => $updated_wsc_status,
				'wsc_signup_date' => $wsc_signup_date,
				'wsc_lock' => $wsc_lock,
			];
		}


		/**
		 * Retrieves the token status.
		 *
		 * This method retrieves the token status by checking the value of the 'cmplz_wsc_signup_status' option.
		 * If the option is not set, it retrieves the token using the 'get_token' method and sets the status accordingly.
		 * The possible values for the status are 'pending', 'enabled', and 'disabled'.
		 *
		 * @return string The token status.
		 */
		public static function get_token_status(): string
		{
			$status = get_option('cmplz_wsc_signup_status', false);
			$token = cmplz_get_transient('cmplz_wsc_access_token');
			$error_token_api = get_option('cmplz_wsc_error_token_api');

			if (!$token) {
				$token = cmplz_wsc_auth::get_token(true);
			}

			if ($token) {
				$status = 'enabled';
			} elseif (!$token && $error_token_api) {
				$status = 'error';
			} else {
				$status = 'disabled';
			}

			if ($status !== get_option('cmplz_wsc_signup_status')) {
				update_option('cmplz_wsc_signup_status', $status);
			}

			return $status;
		}


		/**
		 * Add the website scan ux to Settings > APIs
		 * It modifies the existing menu array by appending a new menu item for website scan under the 'settings-cd' menu item.
		 *
		 * @param array $menu The existing menu array.
		 * @return array The modified menu array with the website scan menu item added.
		 *
		 */
		public function add_website_scan_menu(array $menu): array
		{
			foreach ($menu as $key => $item) {
				if ($item['id'] === 'settings') {
					foreach ($item['menu_items'] as $menu_key => $menu_item) {
						if ($menu_item['id'] === 'settings-cd') {
							$websiteScanItem = [
								'id' => 'settings-websitescan',
								'title' => __('Website Scan', 'complianz-gdpr'),
								'intro' => __('Here you can manage your credentials. If you don’t want to use the Website Scan, you can reset it. A token will be created to verify your website. After creating your credentials, please make sure to check your email for a confirmation.', 'complianz-gdpr'),
								'premium' => false,
								'upgrade' => 'https://complianz.io/pricing',
								'premium_text' => __("View and manage Processing Agreements with %sComplianz GDPR Premium%s", 'complianz-gdpr'),
								'helpLink' => 'https://complianz.io/about-the-website-scan/',
							];
							$menu[$key]['menu_items'][$menu_key]['groups'][] = $websiteScanItem;
						}
					}
				}
			}
			return $menu;
		}


		/**
		 * Add Website Scan fields
		 *
		 * This method is used to add website scan fields to an array of fields.
		 *
		 * @param array $fields The array of fields to add the website scan fields to.
		 * @return array The updated array of fields with the website scan fields added.
		 */
		public function add_website_scan_fields(array $fields): array
		{
			return array_merge(
				$fields,
				[
					// wsc fields
					[
						'id' => cmplz_wsc::WSC_EMAIL_OPTION_KEY,
						'menu_id' => 'settings-cd',
						'group_id' => 'settings-websitescan',
						'type' => 'email',
						'required' => false,
						'default' => $this->retrieve_default_email_address(),
						'label' => __("E-mail address", 'complianz-gdpr'),
					],
					[
						'id' => cmplz_wsc::WSC_CLIENT_ID_OPTION_KEY,
						'menu_id' => 'settings-cd',
						'group_id' => 'settings-websitescan',
						'type' => 'text',
						'required' => false,
						'default' => '',
						'label' => __("Client ID", 'complianz-gdpr'),
						'server_conditions' => [
							'relation' => 'AND',
							[
								'cmplz_wsc_is_enabled()' => true,
							]
						],
					],
					[
						'id' => cmplz_wsc::WSC_CLIENT_SECRET_OPTION_KEY,
						'menu_id' => 'settings-cd',
						'group_id' => 'settings-websitescan',
						'type' => 'password',
						'required' => false,
						'default' => '',
						'label' => __("Client Secret", 'complianz-gdpr'),
						'server_conditions' => [
							'relation' => 'AND',
							[
								'cmplz_wsc_is_enabled()' => true,
							]
						],
					],
					[
						'id' => cmplz_wsc::WSC_SITE_ID_OPTION_KEY,
						'type' => 'hidden',
						'required' => false,
						'visible' => false,
						'default' => '',
					],
					[
						'id' => 'websitescan_status',
						'menu_id' => 'settings-cd',
						'group_id' => 'settings-websitescan',
						'type' => 'websitescan_status',
						'required' => false,
						'default' => '',
					],
					[
						'id' => 'websitescan_actions',
						'menu_id' => 'settings-cd',
						'group_id' => 'settings-websitescan',
						'type' => 'websitescan_actions',
						'required' => false,
						'default' => '',
					],
				]
			);
		}

		/**
		 * Retrieve a default email address for the onboarding dialog
		 *
		 * This method retrieves a default email address for the onboarding dialog.
		 * It first tries to retrieve the email address from the "cmplz wsc email" option.
		 * If the option is empty or not set, it falls back to the WordPress admin email address.
		 *
		 * @return string The default email address for the onboarding dialog.
		 */
		public function retrieve_default_email_address(): string
		{
			// Retrieve the email address from cmplz wsc email option
			$email_address = cmplz_get_option(cmplz_wsc::WSC_EMAIL_OPTION_KEY);

			// If cmplz wsc email doesn't exists, fallback to admin_email
			if (empty($email_address) || $email_address === '') {
				$email_address = get_bloginfo('admin_email');
			}

			// Return the email address if it is valid, otherwise return an empty string
			return filter_var($email_address, FILTER_VALIDATE_EMAIL) ? $email_address : '';
		}


		/**
		 * This method is called before saving an option in the plugin.
		 *
		 * It performs various actions based on the field being saved, such as sending
		 * an authentication email if a user change the one associated to the wsc application,
		 * enabling/disabling the newsletter or signing up.
		 *
		 *
		 * @param string $field_id The ID of the field being saved.
		 * @param mixed $field_value The new value of the field being saved.
		 * @param mixed $prev_value The previous value of the field being saved.
		 * @return void
		 */
		public function cmplz_before_save_option($field_id, $field_value, $prev_value): void
		{
			if (!cmplz_user_can_manage()) {
				return;
			}

			// nothing change
			if ($field_value === $prev_value) {
				return;
			}

			switch ($field_id) {
				// check if the user changed the email used for the wsc activation
				case cmplz_wsc::WSC_EMAIL_OPTION_KEY:
					// prevent orphan site deleting the application
					$this->cmplz_wsc_reset_websitescan();
					// create a new application
					$email = sanitize_email($field_value);
					if ($email) {
						cmplz_wsc_auth::send_auth_email($email);
						// add an action to be executed after saving the fields
						// to reset the client_id and client_secret
						add_action("cmplz_after_saved_fields", array($this, 'after_saved_fields'), 100, 1);
					}
					break;

				case 'send_notifications_email': // switch true / false
					$is_enabled = $field_value;

					// if ($is_enabled) {
					// 	error_log('enable newsletter'); // wait for api
					// } else {
					// 	error_log('disable newsletter'); // wait for api
					// }
					break;

				case 'notifications_email_address':
					// newsletter signup the new address
					$email = sanitize_email($field_value);
					if ($email) {
						cmplz_wsc_auth::newsletter_sign_up($email);
					}
					break;

				default:
					return;
			}
		}


		/**
		 * This method is called after the fields are saved.
		 * If the email address has changed, it clears the values of 'wsc_client_id' and 'wsc_client_secret' fields.
		 *
		 * @param array $fields The array of fields.
		 * @return array The modified array of fields.
		 */
		public function after_saved_fields(array $fields): array
		{
			if (!cmplz_user_can_manage()) {
				return $fields;
			}

			//in $fields, find wsc_client_id and wsc_client_secret, and set the 'value' to ''
			foreach ($fields as $key => $field) {
				if (in_array($field['id'], array(cmplz_wsc::WSC_CLIENT_ID_OPTION_KEY, cmplz_wsc::WSC_CLIENT_SECRET_OPTION_KEY))) {
					$fields[$key]['value'] = '';
				}
			}

			return $fields;
		}


		/**
		 *
		 * Handle the website scan reset
		 * Delete values on cmplz_settings option, options and transients.
		 *
		 *
		 * @return bool
		 */
		public function cmplz_wsc_reset_websitescan(): bool
		{
			global $wpdb;

			$options = [
				// settings
				'cmplz_wsc_signup_status',
				'cmplz_wsc_signup_date',
				'cmplz_wsc_status',
				'cmplz_wsc_onboarding_complete',
				'cmplz_wsc_auth_completed',
				// notices
				'cmplz_wsc_error_email_mismatch',
				'cmplz_wsc_error_missing_token',
				'cmplz_wsc_error_email_auth_failed',
				'cmplz_wsc_error_email_not_sent',
				// token.
				'cmplz_wsc_error_token_api',
				'cmplz_wsc_connection_updated',
				'cmplz_wsc_onboarding_status',
				'cmplz_wsc_scan_id',
				'cmplz_wsc_scan_createdAt',
				'cmplz_wsc_scan_status',
				'cmplz_wsc_scan_iteration',
				'cmplz_wsc_scan_progress',
				'cmplz_wsc_scan_first_run',
				// onboarding date.
				cmplz_wsc::WSC_OPT_ONBOARDING_DATE,
			];

			$cmplz_options = [
				cmplz_wsc::WSC_EMAIL_OPTION_KEY,
				cmplz_wsc::WSC_CLIENT_ID_OPTION_KEY,
				cmplz_wsc::WSC_CLIENT_SECRET_OPTION_KEY,
				cmplz_wsc::WSC_SITE_ID_OPTION_KEY,
				cmplz_license::WSC_LICENSE_SYNC_ID,
				cmplz_license::WSC_LICENSE_SYNC_STATUS,
			];

			$cmplz_transients = [
				'cmplz_wsc_access_token'
			];

			$dynamic_options = [
				'cmplz_%_consentdata',
				'cmplz_consent_error_',
				'cmplz_wsc_user_deletion_%',
				'cmplz_consent_%'
			];

			try {
				$temp_credentials = get_option('cmplz_wsc_user_deletion_temp_credentials');
				$client_id = cmplz_get_option(cmplz_wsc::WSC_CLIENT_ID_OPTION_KEY);
				$client_secret = cmplz_get_option(cmplz_wsc::WSC_CLIENT_SECRET_OPTION_KEY);

				$current_user = [
					'client_id' => $client_id,
					'client_secret' => $client_secret
				];

				$temp_credentials[] = $current_user;
				update_option('cmplz_wsc_user_deletion_temp_credentials', $temp_credentials);

				$token = cmplz_wsc_auth::get_token(true);  // new token request, skip the one not expired stored into the db

				if (!$token) {
					$this->log_user_deletion_error('cmplz_wsc_user_deletion_token_error', 'Token not retrieved');
				} else {
					// Make the API request to delete the application
					$wsc_endpoint = base64_decode(cmplz_wsc_auth::WSC_ENDPOINT);
					$request = wp_remote_request($wsc_endpoint . '/api/lite/oauth_applications', array(
						'method' => 'DELETE',
						'headers' => array(
							'Content-Type' => 'application/json',
							'Authorization' => 'Bearer ' . $token,
						),
						'timeout' => 15,
						'sslverify' => true,
					));

					if (is_wp_error($request)) {
						$this->log_user_deletion_error('cmplz_wsc_user_deletion_error', $request->get_error_message());
					}

					$response_code = wp_remote_retrieve_response_code($request);

					if ($response_code === 204) {
						$options = array_merge($options, [
							'cmplz_wsc_user_deletion_temp_credentials'
						]);
					} else {
						$this->log_user_deletion_error('cmplz_wsc_user_deletion_error', 'Unexpected API response');
					}
				}

				// Proceed with cleanup
				foreach ($options as $option) {
					delete_option($option);
				}

				// remove dynamic options
				foreach ($dynamic_options as $option_name) {
					$wpdb->query(
						$wpdb->prepare(
							"DELETE FROM $wpdb->options WHERE option_name LIKE %s",
							$option_name
						)
					);
				}

				// remove client credentials
				foreach ($cmplz_options as $cmplz_option) {
					cmplz_update_option_no_hooks($cmplz_option, '');
				}

				foreach ($cmplz_transients as $cmplz_transient) {
					cmplz_delete_transient($cmplz_transient);
				}

				update_option('cmplz_wsc_reset_complete', true, false);

				return true;
			} catch (Exception $e) {
				$error = 'Exception during API request: ' . $e->getMessage();
				$this->log_user_deletion_error('cmplz_wsc_user_deletion_error', $error);
				if (WP_DEBUG) {
					error_log($error);
				}
				return false;
			}
		}


		/**
		 * Checks if there was an error during user deletion and triggers the reset of the website scan if necessary.
		 *
		 * This function checks if there was an error during user deletion by retrieving the values
		 * of the 'cmplz_wsc_user_deletion_error' and 'cmplz_wsc_user_deletion_token_error' options.
		 * If either of these options is set to true, the function triggers the reset of the website scan
		 * by calling the 'cmplz_wsc_reset_websitescan' method.
		 *
		 * @return void
		 */
		public function check_failed_user_deletion():void
		{
			$options = [
				'cmplz_wsc_user_deletion_error',
				'cmplz_wsc_user_deletion_error_message',
				'cmplz_wsc_user_deletion_error_timestamp',
				'cmplz_wsc_user_deletion_token_error',
				'cmplz_wsc_user_deletion_token_error_message',
				'cmplz_wsc_user_deletion_token_error_timestamp',
				'cmplz_wsc_user_deletion_temp_credentials'
			];

			$failed_deletion = get_option('cmplz_wsc_user_deletion_temp_credentials', false); // array
			if (!$failed_deletion) {
				foreach ($options as $option) {
					delete_option($option);
				}
			} else {
				foreach ($failed_deletion as $client_credentials) {
					if (!$client_credentials['client_id'] || !$client_credentials['client_secret']) continue;

					$token = cmplz_wsc_auth::get_token(true, true, $client_credentials);
					if (!$token) {
						continue;
					}

					$wsc_endpoint = base64_decode(cmplz_wsc_auth::WSC_ENDPOINT);
					$request = wp_remote_request($wsc_endpoint . '/api/lite/oauth_applications', array(
						'method' => 'DELETE',
						'headers' => array(
							'Content-Type' => 'application/json',
							'Authorization' => 'Bearer ' . $token,
						),
						'timeout' => 15,
						'sslverify' => true,
					));

					$response_code = wp_remote_retrieve_response_code($request);
					if ($response_code === 204) {
						// update the $failed_deletions array removing the current one
						$failed_deletion = array_filter($failed_deletion, function ($value) use ($client_credentials) {
							return $value !== $client_credentials;
						});
						update_option('cmplz_wsc_user_deletion_temp_credentials', $failed_deletion);
					}
				}
			}
		}


		/**
		 * Logs an error when a user deletion fails.
		 *
		 * @param string $option The option name to update.
		 * @param string $message The error message to log.
		 * @return void
		 */
		public function log_user_deletion_error(string $option, string $message): void
		{
			update_option($option, true, false);
			update_option($option . '_message', $message, false);
			update_option($option . '_timestamp', time(), false);
			if (WP_DEBUG) {
				error_log($message);
			}
		}

		/**
		 * Handles the newsletter onboarding consent.
		 *
		 * This static method is responsible for storing the consent given by the user
		 * during the onboarding process for the newsletter.
		 *
		 * @param string $type The type of consent being stored.
		 * @param array $posted_data The data associated with the consent.
		 * @return void
		 */
		public static function cmplz_store_newsletter_onboarding_consent_handler(string $type, array $posted_data): void
		{
			cmplz_wsc_auth::store_onboarding_consent($type, $posted_data);
		}
	}
}
