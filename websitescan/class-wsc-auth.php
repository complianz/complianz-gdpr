<?php

defined('ABSPATH') or die();

if (!class_exists("cmplz_wsc_auth")) {

	class cmplz_wsc_auth
	{

		const WSC_ENDPOINT = 'aHR0cHM6Ly9hcGkuY29tcGxpYW56Lmlv';
		const WSC_CB_ENDPOINT = 'aHR0cHM6Ly9leHRlcm5hbC1wdWJsaWMtZ2VuZXJhbC5zMy5ldS13ZXN0LTEuYW1hem9uYXdzLmNvbS9zdGF0dXMuanNvbg==';
		const WSC_TERMS_ENDPOINT = 'aHR0cHM6Ly9jb29raWVkYXRhYmFzZS5vcmcvd3AtanNvbi93c2MvdjEvdGVybXM=';
		const NEWSLETTER_TERMS_ENDPOINT = 'aHR0cHM6Ly9jb29raWVkYXRhYmFzZS5vcmcvd3AtanNvbi9uZXdzbGV0dGVyL3YxL3Rlcm1z';
		const NEWSLETTER_SIGNUP_ENDPOINT = 'aHR0cHM6Ly9tYWlsaW5nbGlzdC5jb21wbGlhbnouaW8=';
		const CONS_ENDPOINT = 'aHR0cHM6Ly9jb25zZW50LmNvbXBsaWFuei5pby9wdWJsaWMvY29uc2VudA==';
		const CONS_ENDPOINT_PK = 'qw0Jv5legvI9fQdn5OvNedpG4zibaTNT';
		const WSC_ENDPOINT_AUTH_HEADER = 'QmFzaWMgZEdWaGJXSnNkV1ZmYzNSaFoybHVaenBGYm05bVJHZzRjV0Y2YVhCemFUWkxSM05FVlE9PQ==';
		const PARTNER_ID = 'NjQ1MTc4NjMtM2YzMS00NDA3LWJjMWUtMjc4MjNlOTJhNThl';
		const CONS_IDENTIFIERS = [
			'wsc_consent' => 'terms',
			'newsletter_consent' => 'newsletter',
		];


		public function init_hooks()
		{
			add_action("admin_init", array($this, 'confirm_email_auth'), 10, 3); // Verify the authentication link in the email
			add_action('cmplz_every_day_hook', array($this, 'check_failed_consent_onboarding'));
			add_action('cmplz_every_day_hook', array($this, 'check_failed_newsletter_signup'));
			// Set the hooks to create the site id for the WSC.
			add_action( 'cmplz_every_day_hook', array( $this, 'maybe_sync_wsc_site_id' ) );
			add_action( 'cmplz_maybe_sync_wsc_site_id', array( $this, 'maybe_sync_wsc_site_id' ), 0 );
			add_action( 'cmplz_schedule_create_wsc_site_id', array( $this, 'schedule_create_wsc_site_id' ), 0 );
		}


		/**
		 * Sends an authentication email.
		 *
		 * This function sends an authentication email to the specified email address.
		 * It first checks if the user has the capability to manage the plugin.
		 * If the email is not a valid email address, it updates an option to indicate that the email was not sent.
		 * If the email is valid, it makes a POST request to the WSC endpoint to send the email.
		 * If the request is successful, it sets various options to indicate that the email was sent and updates the signup status.
		 * If the request fails, it updates an option to indicate that the email was not sent.
		 *
		 * @param string $email The email address to send the authentication email to.
		 * @return void
		 */
		public static function send_auth_email(string $email): void
		{
			if (!cmplz_user_can_manage() || empty($email)) {
				return;
			}

			if (!is_email($email)) {
				update_option('cmplz_wsc_error_email_not_sent', true, false);
				return;
			}

			$wsc_endpoint = base64_decode(self::WSC_ENDPOINT);
			$wsc_endpoint_auth_header = base64_decode(self::WSC_ENDPOINT_AUTH_HEADER);
			$partner_id = base64_decode(self::PARTNER_ID);

			$request = wp_remote_post(
				$wsc_endpoint . '/api/lite/users',
				array(
					'headers' => array(
						'Content-Type' => 'application/json',
						'Authorization' => $wsc_endpoint_auth_header,
					),
					'timeout' => 15,
					'sslverify' => true,
					'body' => json_encode(array(
						'email' => sanitize_email($email),
						"base_url" => esc_url_raw(admin_url()),
						"partner" => $partner_id
					))
				)
			);

			if (is_wp_error($request)) {
				$error_message = $request->get_error_message();
				if (WP_DEBUG) {
					error_log('COMPLIANZ: cannot send email, request failed');
					if ($error_message) {
						error_log('COMPLIANZ: ' . $error_message);
					}
				}
				update_option('cmplz_wsc_error_email_not_sent', true, false);
			} else {
				$response_code = wp_remote_retrieve_response_code($request);
				if ($response_code === 200) {
					cmplz_update_option_no_hooks(cmplz_wsc::WSC_EMAIL_OPTION_KEY, $email);
					update_option('cmplz_wsc_signup_status', 'pending', false);
					update_option('cmplz_wsc_status', 'pending', false);
					update_option('cmplz_wsc_signup_date', time(), false);
					delete_option('cmplz_wsc_error_email_not_sent');
					delete_option( cmplz_wsc::WSC_OPT_ONBOARDING_DATE );
				} else {
					$response_message = wp_remote_retrieve_response_message($request);
					if (WP_DEBUG) {
						error_log('COMPLIANZ: cannot send email, request failed');
						if ($response_message) {
							error_log('COMPLIANZ: ' . $response_message);
						}
					}
					update_option('cmplz_wsc_error_email_not_sent', true, false);
				}
			}
		}


		/**
		 * Handles the confirmation of email authentication for the Website Scan Feature.
		 *
		 * This function is responsible for confirming the email authentication for the Complianz plugin.
		 * It checks if the user has the necessary permissions, if the page is the Complianz page,
		 * and if the lite-user-confirmation parameter is set. It then verifies the email and token,
		 * makes a request to the WSC endpoint, and updates the necessary options accordingly.
		 * Finally, it redirects the user to the Complianz settings page.
		 *
		 * @return void
		 */
		public function confirm_email_auth(): void
		{
			if (!cmplz_user_can_manage()) {
				return;
			}

			if (!isset($_GET['page']) || $_GET['page'] !== 'complianz') {
				return;
			}

			if (!isset($_GET['lite-user-confirmation'])) {
				return;
			}

			$stored_email = cmplz_get_option(cmplz_wsc::WSC_EMAIL_OPTION_KEY);

			if (!isset($_GET['email']) || $_GET['email'] !== $stored_email) {
				update_option('cmplz_wsc_error_email_mismatch', true, false);
				if (WP_DEBUG) {
					error_log('COMPLIANZ: email does not match the stored email');
				}
				return;
			}
			if (!isset($_GET['token'])) {
				update_option('cmplz_wsc_error_missing_token', true, false);
				if (WP_DEBUG) {
					error_log('COMPLIANZ: token not found in the authentication url');
				}
				return;
			}

			$token = sanitize_text_field($_GET['token']);
			$wsc_endpoint = base64_decode(self::WSC_ENDPOINT);
			$wsc_endpoint_auth_header = base64_decode(self::WSC_ENDPOINT_AUTH_HEADER);

			$request = wp_remote_post($wsc_endpoint . '/api/lite/oauth_applications', array(
				'headers' => array(
					'Content-Type' => 'application/json',
					'Authorization' => $wsc_endpoint_auth_header,
				),
				'timeout' => 15,
				'sslverify' => true,
				'body' => json_encode(
					array(
						'email' => sanitize_title($stored_email),
						'token' => $token,
					)
				),
			));

			if (is_wp_error($request)) {
				$error_message = $request->get_error_message();
				if (WP_DEBUG) {
					error_log('COMPLIANZ: cannot confirm email, request failed');
					if ($error_message) {
						error_log('COMPLIANZ: ' . $error_message);
					}
				}
				update_option('cmplz_wsc_error_email_auth_failed', true, false);
			} else {
				$response_code = wp_remote_retrieve_response_code($request);
				if ($response_code === 201) {
					$response_body = json_decode(wp_remote_retrieve_body($request));
					if (isset($response_body->client_id) && isset($response_body->client_secret)) {
						cmplz_update_option_no_hooks(cmplz_wsc::WSC_CLIENT_ID_OPTION_KEY, $response_body->client_id);
						cmplz_update_option_no_hooks(cmplz_wsc::WSC_CLIENT_SECRET_OPTION_KEY, $response_body->client_secret);
						update_option('cmplz_wsc_signup_status', 'enabled', false);
						update_option('cmplz_wsc_status', 'enabled', false);
						update_option('cmplz_wsc_auth_completed', true, false);
						cmplz_wsc_onboarding::update_onboarding_status('terms', true);
						delete_option('cmplz_wsc_error_email_auth_failed');
						delete_option('cmplz_wsc_error_email_mismatch');
						delete_option('cmplz_wsc_error_missing_token');
						// reset the processed pages
						delete_transient('cmplz_processed_pages_list');
						// Since client_id and client_secret are stored, we can trigger the site creation.
						do_action( 'cmplz_maybe_sync_wsc_site_id' );
					} else {
						if (WP_DEBUG) {
							error_log('COMPLIANZ: cannot confirm email, client id or secret not found in response');
						}
						update_option('cmplz_wsc_error_email_auth_failed', true, false);
					}
				} else {
					if (WP_DEBUG) {
						error_log('COMPLIANZ: cannot confirm email, request failed');
					}
					update_option('cmplz_wsc_error_email_auth_failed', true, false);
				}
			}

			wp_redirect(cmplz_admin_url('#settings/settings-cd'));

			exit;
		}


		/**
		 * Retrieves the access token for the Website Scan feature.
		 *
		 * This function checks the WSC signup status and retrieves the access token
		 * if it is available. If the token is not found, it tries to retrieve a fresh one
		 * using the provided email, client ID, and client secret.
		 *
		 * @param bool $new Whether to retrieve a new token.
		 * @param bool $no_store Whether to store the token.
		 * @param array|false $client_credentials The client credentials.
		 *
		 * @return string|bool The access token if available, 'pending' if the WSC signup status is pending,
		 *                     false if the email, client ID, or client secret is not found, or false if there
		 *                     was an error retrieving the token.
		 *
		 */
		public static function get_token($new = false, $no_store = false, $client_credentials = false)
		{
			// emulating the union type  array|false $client_credentials
			if (!is_bool($new)) {
				throw new InvalidArgumentException('$new needs to be of type bool');
			}
			if (!is_bool($no_store)) {
				throw new InvalidArgumentException('$no_store needs to be of type bool');
			}
			if ($client_credentials !== false && !is_array($client_credentials)) {
				throw new InvalidArgumentException('$client_credentials must be an array or false');
			}

			// clear stored token
			if ($new) cmplz_delete_transient('cmplz_wsc_access_token');

			$token = cmplz_get_transient('cmplz_wsc_access_token');
			if ($token) {
				return $token;
			}

			//if no token found, try retrieving a fresh one
			$email = (string)cmplz_get_option(cmplz_wsc::WSC_EMAIL_OPTION_KEY);
			$client_id = (string)cmplz_get_option(cmplz_wsc::WSC_CLIENT_ID_OPTION_KEY);
			$client_secret = (string)cmplz_get_option(cmplz_wsc::WSC_CLIENT_SECRET_OPTION_KEY);

			// if client credentials are provided, use them
			if ($client_credentials) {
				$client_id = $client_credentials['client_id'];
				$client_secret = $client_credentials['client_secret'];
			} else {
				if ($email === '' || $client_id === '' || $client_secret === '') {
					// if (WP_DEBUG) {
					// 	error_log('COMPLIANZ: cannot retrieve token, email or client id or secret not found');
					// }
					return false;
				}
			}

			$wsc_endpoint = base64_decode(self::WSC_ENDPOINT);
			$wsc_endpoint_auth_header = base64_decode(self::WSC_ENDPOINT_AUTH_HEADER);

			$request = wp_remote_post(
				$wsc_endpoint . '/oauth/token',
				array(
					'headers' => array(
						'Content-Type' => 'application/json',
						'Authorization' => $wsc_endpoint_auth_header,
					),
					'timeout' => 15,
					'sslverify' => true,
					'body' => json_encode(
						array(
							'grant_type' => "client_credentials",
							"client_id" => $client_id,
							"client_secret" => $client_secret,
							"scope" => "write"
						)
					)
				)
			);

			if (!is_wp_error($request)) { // request success true

				$request = json_decode(wp_remote_retrieve_body($request));

				if (isset($request->access_token)) { // if there's an access token
					if ($no_store) return $request->access_token;

					delete_option('cmplz_wsc_error_token_api');
					update_option('cmplz_wsc_connection_updated', time(), false);

					$token = $request->access_token;
					$expires = $request->expires_in ?? 7200;
					cmplz_set_transient('cmplz_wsc_access_token', $token, $expires - 10);

					return $token;
				} else {
					if ($no_store) return false;

					update_option('cmplz_wsc_error_token_api', true, false);
					if (WP_DEBUG && $request->error) {
						error_log('COMPLIANZ: cannot retrieve token, token not found in response');
					}
					return false;
				}
			} else {
				if (!$no_store) update_option('cmplz_wsc_error_token_api', true, false);
				$error_message = $request->get_error_message();
				if (WP_DEBUG) {
					error_log('COMPLIANZ: cannot retrieve token, request failed');
					if ($error_message) {
						error_log('COMPLIANZ: ' . $error_message);
					}
				}
				return false;
			}
		}


		/**
		 * Store the consent when user signs up for the WSC Feature
		 * or when the user signs up for the newsletter
		 *
		 * The method is triggered once using the cron job during the onboarding process
		 * or using check_failed_consent_onboarding() method ($retry = true)
		 *
		 * @param string $type || Could be wsc terms, wsc newsletter 'wsc_consent' // 'newsletter_consent'
		 * @param array $posted_data
		 * @param bool $retry
		 * @param string $consent_data
		 * @return void
		 */
		public static function store_onboarding_consent(string $type, array $posted_data, bool $retry = false, string $consent_data = ''): void
		{
			// Check if the given type exists in the cons_identifiers array.
			if (!array_key_exists($type, self::CONS_IDENTIFIERS)) {
				return;
			}
			// Check if the posted_data array is empty.
			if (empty($posted_data)) {
				return;
			}

			// if it's a retry because there's an old failed store attempts
			// set the old consent
			if ($retry) {
				$consent = $consent_data;
			} else {
				// else let's create a new consent data
				// check posted data
				$email = sanitize_email($posted_data['email']);
				if (!is_email($email)) {
					return;
				}

				// Create a subject id
				$site_url = site_url();
				$encodedSiteUrl = base64_encode($site_url);
				$encodedEmail = base64_encode($email);
				$consent_subject_id = sprintf('cmplz-%s#%s', $encodedSiteUrl, $encodedEmail);

				// Check for timestamp and url
				$timestamp = isset($posted_data['timestamp']) // check if timestamp is set
					? (int)$posted_data['timestamp'] / (strlen($posted_data['timestamp']) > 10 ? 1000 : 1) // if timestamp is in milliseconds, convert to seconds
					: time(); // if $posted_data['timestamp'] is not set, use the current time

				$url = esc_url_raw($posted_data['url']) ?? site_url();

				// Generate the consent
				$consent = json_encode([
					'timestamp' => date('c', $timestamp),
					'subject' => [
						'id' => $consent_subject_id,
						'email' => $email,
					],
					'preferences' => [
						$type => true
					],
					'legal_notices' => [
						[
							'identifier' => self::CONS_IDENTIFIERS[$type] // terms or newsletter
						]
					],
					'proofs' => [
						[
							// pass all $posted_data as content
							'content' => json_encode([
								'email' => $email,
								'timestamp' => $timestamp,
								'url' => $url,
							]),
							'form' => 'complianz-onboarding__' . $type, // complianz onboarding form ??
						]
					],
				]);
			}

			// safe store the consent locally
			update_option('cmplz_' . $type . '_consentdata', $consent, false);

			$cons_endpoint = base64_decode(self::CONS_ENDPOINT);
			// Send the request
			$request = wp_remote_post(
				$cons_endpoint,
				array(
					'headers' => array(
						'Content-Type' => 'application/json',
						'ApiKey' => self::CONS_ENDPOINT_PK,
					),
					'timeout' => 15,
					'sslverify' => true,
					'body' => $consent
				)
			);

			if (is_wp_error($request)) {
				$error_message = $request->get_error_message();
				if (WP_DEBUG) {
					error_log('COMPLIANZ: cannot store consent, request failed for identifier: ' . $type);
					if ($error_message) {
						error_log('COMPLIANZ: ' . $error_message);
					}
				}
				// 	// define an error into the options
				update_option('cmplz_consent_error_timestamp_' . $type, time());
				// store the consent for the time we can resend the request
				update_option('cmplz_consent_error_consentdata_' . $type, $consent);
			} else {
				$response_code = wp_remote_retrieve_response_code($request);
				if ($response_code == 200) {
					delete_option('cmplz_consent_' . $type);
					// delete possible consent errors
					delete_option('cmplz_consent_error_timestamp_' . $type);
					delete_option('cmplz_consent_error_consentdata_' . $type);

					$body = json_decode(wp_remote_retrieve_body($request));
					// store the consent locally
					update_option('cmplz_consent_' . $type, $body);
				} else {
					$response_message = wp_remote_retrieve_response_message($request);
					if (WP_DEBUG) {
						error_log('COMPLIANZ: cannot store consent, request failed for identifier: ' . $type);
						if ($response_message) {
							error_log('COMPLIANZ: ' . $response_message);
						}
					}
					// 	// define an error into the options
					update_option('cmplz_consent_error_timestamp_' . $type, time());
					// store the consent for the time we can resend the rquest
					update_option('cmplz_consent_error_consentdata_' . $type, $consent);
				}
			}
		}


		/**
		 * Subscribes a user to the newsletter.
		 *
		 * @param string $email The email address of the user.
		 * @param bool $retry Whether to retry the subscription if it fails.
		 * @return void
		 */
		public static function newsletter_sign_up(string $email, bool $retry = false): void
		{
			$license_key = '';

			if (defined('rsssl_pro_version')) {
				$license_key = COMPLIANZ::$license->license_key();
				$license_key = COMPLIANZ::$license->maybe_decode($license_key);
			}

			$api_params = array(
				'has_premium' => defined('cmplz_premium'), // not required
				'license' => $license_key, // not required
				'email' => sanitize_email($email),
				'domain' => esc_url_raw(site_url()),
			);

			$newsletter_signup_endpoint = base64_decode(self::NEWSLETTER_SIGNUP_ENDPOINT);

			$request = wp_remote_post($newsletter_signup_endpoint, array('timeout' => 15, 'sslverify' => true, 'body' => $api_params));

			if (is_wp_error($request)) {
				update_option('cmplz_newsletter_signup_error_email', $email, false); // save the email in an option
				update_option('cmplz_newsletter_signup_error', true, false); // save the failed attempt
				update_option('cmplz_newsletter_signup_error_timestamp', time(), false); // set an error with the timestamp
				// log the error
				if (WP_DEBUG) {
					$error_message = $request->get_error_message();
					if ($error_message) {
						error_log('COMPLIANZ: ' . $error_message);
					}
				}
			} else {
				$response_code = wp_remote_retrieve_response_code($request); // 200 ok
				if ($response_code === 200) {
					// if the method is called by the cron or there's a mismatch between the emails clean the options
					if ($retry || $email !== get_option('cmplz_newsletter_signup_error_email')) {
						// remove any failed attempts
						delete_option('cmplz_newsletter_signup_error_email');
						delete_option('cmplz_newsletter_signup_error');
						delete_option('cmplz_newsletter_signup_error_timestamp');
					}
					// save the email in the options
					cmplz_update_option_no_hooks('notifications_email_address', $email); // save the email in the options
					cmplz_update_option_no_hooks('send_notifications_email', 1); // enable the notifications
					cmplz_wsc_onboarding::update_onboarding_status('newsletter', true);
				}
			}
		}


		/**
		 * Website Scan Circuit Breaker
		 *
		 * This function checks if the Website scan endpoint accepts user signups and radar scans
		 * passing auth or scanner as $service.
		 *
		 * @param string $service The service to check | signup or scanner.
		 * @return bool Returns true if the Website scan endpoint accepts user signups, false otherwise.
		 *
		 */
		public static function wsc_api_open(string $service): bool
		{
			$wsc_cb_endpoint = base64_decode(self::WSC_CB_ENDPOINT);
			$request = wp_remote_get($wsc_cb_endpoint);

			if (is_wp_error($request)) {
				cmplz_wsc_logger::log_errors( 'wsc_api_open', $request->get_error_message() );
				return false;
			}

			$service = sprintf('%s_enabled', $service);

			$response_body = json_decode(wp_remote_retrieve_body($request));

			if (isset($response_body->$service) && $response_body->$service === 'true') {
				return true;
			}

			return false;
		}


		/**
		 * Check for failed onboarding consent store attempts
		 * If there's a failed attempt, try to store it again
		 *
		 *
		 * @return void
		 */
		public function check_failed_consent_onboarding(): void
		{
			$identifiers = self::CONS_IDENTIFIERS;

			foreach ($identifiers as $key => $type) {
				// check for the errors
				$error_timestamp = get_option('cmplz_consent_error_timestamp_' . $key, false);
				// store the consent for the time we can resend the rquest
				$error_consentdata = get_option('cmplz_consent_error_consentdata_' . $key, false);

				if ($error_consentdata && $error_timestamp < time() - 68400) {
					$this->store_onboarding_consent($key, [], true, $error_consentdata);
				}
			}
		}


		/**
		 * Check for failed newsletter signups
		 * If there's a failed attempt, try to sign up again
		 *
		 * @return bool
		 */
		public function check_failed_newsletter_signup(): bool
		{
			$failed = get_option('cmplz_newsletter_signup_error');
			$timestamp = get_option('cmplz_newsletter_signup_error_timestamp');
			$email = get_option('cmplz_newsletter_signup_error_email');
			if ($failed && $timestamp && $email) {
				// check if the error is older than 24 hours
				if ($timestamp < time() - 86400) {
					// try to sign up again
					self::newsletter_sign_up($email, true);
				}
			}
			return true;
		}


		/**
		 * Checks if the WSC (Website Scan) is authenticated.
		 *
		 * This method verifies if the WSC is authenticated by checking if the client ID and client secret
		 * are stored in the options. If either the client ID or client secret is empty, it returns false.
		 * Otherwise, it returns true indicating that the WSC is authenticated.
		 *
		 * @return bool Returns true if the WSC is authenticated, false otherwise.
		 */
		public static function wsc_is_authenticated(): bool {
			$client_id     = (string) cmplz_get_option( cmplz_wsc::WSC_CLIENT_ID_OPTION_KEY );
			$client_secret = (string) cmplz_get_option( cmplz_wsc::WSC_CLIENT_SECRET_OPTION_KEY );

			if ( empty( $client_id ) || empty( $client_secret ) ) {
				return false;
			}

			return true;
		}


		/**
		 * Schedules the synchronization of the WSC site ID.
		 *
		 * This function checks if the site ID is already set. If not, it schedules a cron job
		 * to create the site ID after 10 minutes.
		 *
		 * @return void
		 */
		public function maybe_sync_wsc_site_id(): void {
			// If the site id is already set, return.
			if ( $this->retrieve_wsc_site_id() > 0 ) {
				return;
			}

			// Use the cron to schedule the site_id creation.
			if ( ! wp_next_scheduled( 'cmplz_schedule_create_wsc_site_id' ) ) {
				wp_schedule_single_event( time() + 300, 'cmplz_schedule_create_wsc_site_id' );
			}
		}


		/**
		 * Schedules the creation of a site ID for the Website Scan.
		 *
		 * This function calls the `wsc_create_site_id` method to create a site ID.
		 *
		 * @return void
		 */
		public function schedule_create_wsc_site_id(): void {
			$this->wsc_create_site_id();
		}


		/**
		 * Creates a site ID for the Website Scan.
		 *
		 * This function performs several checks and actions to create a site ID:
		 * 1. Checks if the site ID is already set.
		 * 2. Verifies if the user is authenticated.
		 * 3. Checks if the API is open for signups.
		 * 4. Retrieves an access token.
		 * 5. Retrieves the site language.
		 * 6. Defines the request body and sends a POST request to create the site ID.
		 * 7. Handles the response and stores the site ID if successful.
		 *
		 * @return void
		 */
		private function wsc_create_site_id(): void {
			// If the site id is already set, return.
			if ( $this->retrieve_wsc_site_id() > 0 ) {
				return;
			}

			// If the user is not authenticated, return.
			if ( ! self::wsc_is_authenticated() ) {
				return;
			}

			// If the api is not open, return.
			if ( ! self::wsc_api_open( 'signup' ) ) {
				return;
			}

			// Retrieve the token.
			$token = self::get_token( true );

			if ( ! $token ) {
				return;
			}

			// Retrieve the site language.
			$language = substr( get_locale(), 0, 2 ) ?? '';

			// Define the request body.
			$body = array(
				'data' => array(
					'type'       => 'sites',
					'attributes' => array(
						'url'       => esc_url_raw( home_url() ),
						'language' => $language,
						'site_type' => 'web',
					),
				),
			);

			$wsc_endpoint = base64_decode(self::WSC_ENDPOINT);

			// Send the request.
			$response = wp_remote_post(
				$wsc_endpoint . '/api/v2/sites',
				array(
					'headers'   => array(
						'Content-Type'  => 'application/json',
						'Authorization' => 'Bearer ' . $token,
					),
					'timeout'   => 15,
					'sslverify' => true,
					'body'      => wp_json_encode( $body ),
				)
			);

			// Handle the response.
			if ( is_wp_error( $response ) ) {
				$error_message = $response->get_error_message();
				cmplz_wsc_logger::log_errors( __FUNCTION__, $error_message );
				return;
			}

			// Check the response code.
			$response_code = wp_remote_retrieve_response_code( $response );

			if ( 201 !== $response_code ) {
				cmplz_wsc_logger::log_errors( __FUNCTION__, 'Unexpected response code in ' . __FUNCTION__ . ' request: ' . $response_code );
				return;
			}

			// Decode the response body.
			$response_body = json_decode( wp_remote_retrieve_body( $response ) );

			// Check if the site id is found in the response.
			if ( ! isset( $response_body->data ) || ! isset( $response_body->data->id ) ) {
				cmplz_wsc_logger::log_errors( __FUNCTION__, 'No site id found in response' );
				return;
			}

			// Store the site_id.
			$site_id = (int) $response_body->data->id;
			cmplz_update_option( cmplz_wsc::WSC_SITE_ID_OPTION_KEY, $site_id );
			do_action( 'cmplz_maybe_sync_wsc_license' );
		}


		/**
		 * Retrieves the WSC site ID.
		 *
		 * This function fetches the site ID for the Website Scan feature from the stored options.
		 *
		 * @return int The site ID.
		 */
		public static function retrieve_wsc_site_id(): int {
			return (int) cmplz_get_option( cmplz_wsc::WSC_SITE_ID_OPTION_KEY );
		}
	}
}
