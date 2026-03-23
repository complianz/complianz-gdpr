<?php

defined('ABSPATH') or die();


if (!class_exists("cmplz_wsc_onboarding")) {

	class cmplz_wsc_onboarding
	{

		const WSC_MAX_DISMISS   = 2;
		const CMPLZ_PLUGIN_SLUG = 'complianz-gdpr';

		public function init_hooks()
		{
			add_action("cmplz_do_action", array($this, 'handle_onboarding_action'), 10, 3);
			add_action("admin_init", array($this, 'check_onboarding_status'), 10);
			add_action("admin_init", array($this, 'maybe_show_onboarding_modal'), 20);
			add_action("cmplz_every_week_hook", array($this, 'check_wsc_consent'), 20);
			// Check if the option already exists, if not, set it up
			if (!get_option('cmplz_wsc_onboarding_status')) {
				$this->set_onboarding_status_option();
			}
			add_action('cmplz_store_wsc_onboarding_consent', array($this, 'cmplz_store_wsc_onboarding_consent_handler'), 10, 2);
			add_action( 'upgrader_process_complete', array( $this, 'handle_onboarding_dismiss_on_upgrade' ), 10, 2 );
			add_action( 'automatic_updates_complete', array( $this, 'handle_onboarding_dismiss_on_autoupdate' ) );
		}


		/**
		 * Sets the onboarding status option.
		 *
		 * This method initializes the onboarding status option with default values for 'terms', 'newsletter', and 'plugins'.
		 * It updates the 'cmplz_wsc_onboarding_status' option in the WordPress database with these default values.
		 *
		 * @return void
		 */
		private function set_onboarding_status_option(): void
		{
			$cmplz_wsc_onboarding_status = [
				'terms' => false,
				'newsletter' => false,
				'plugins' => false,
			];
			update_option('cmplz_wsc_onboarding_status', $cmplz_wsc_onboarding_status, false);
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
		public function handle_onboarding_action(array $data, string $action, WP_REST_Request $request): array
		{
			if (!cmplz_user_can_manage()) {
				return [];
			}
			switch ($action) {
				// used on onboarding to sign up
				case 'signup_wsc':
					$posted_data = $request->get_json_params();
					$email = strtolower(sanitize_email($posted_data['email']));
					if (is_email($email)) {
						cmplz_wsc_auth::send_auth_email($email);
						// Schedule storing onboarding consent asynchronously
						self::schedule_store_onboarding_consent('cmplz_store_wsc_onboarding_consent', 1000, ['wsc_consent', $posted_data]);
					}
					break;
				case 'get_wsc_terms':
					$data = $this->get_onboarding_doc('wsc_terms');
					break;
				case 'dismiss_wsc_onboarding':
					$posted_data = $request->get_json_params();
					$step        = sanitize_text_field( $posted_data['step'] );
					$this->store_onboarding_dismiss( $step );
					$new_date = time() + wp_rand( 120, 14 * DAY_IN_SECONDS );
					$this->set_onboarding_date( $new_date );
					break;
				case 'get_newsletter_terms':
					$data = $this->get_onboarding_doc('newsletter');
					break;
				case 'get_recommended_plugins_status':
					$posted_data = $request->get_json_params();
					$plugins = $posted_data['plugins'] ?? [];
					$data = [
						'plugins' => $this->get_recommended_plugins_status($plugins),
						'isUpgrade' => get_option('cmplz_upgraded_to_7', false)
					];
					break;
				case 'install_plugin':
				case 'activate_plugin':
					$posted_data = $request->get_json_params();
					$data = [
						'plugins' => $this->process_plugin_action($action, $posted_data),
					];
					break;
			}
			return $data;
		}


		/**
		 * Check if the user should get the onboarding modal, for the signup process.
		 *
		 * @return void
		 */
		public function maybe_show_onboarding_modal(): void
		{
			if (!cmplz_user_can_manage()) {
				return;
			}

			if ( ! isset( $_GET['websitescan'] ) && isset( $_GET['page'] ) && strpos( $_GET['page'], 'complianz' ) !== false && $this->should_onboard() ) {
				wp_redirect( add_query_arg( ['websitescan' => ''], cmplz_admin_url() ) );
				exit;
			}
		}


		/**
		 * Determines whether the user should be onboarded.
		 *
		 * This function checks various conditions to determine if the user should be onboarded.
		 * If the user can't manage, or if the 'cmplz_force_signup' parameter is set in the URL,
		 * the function returns true. If the onboarding process is already complete or if the
		 * WSC API is not open, the function returns false. Otherwise, it checks if the onboarding
		 * start date is set and if it is earlier than the current time, and returns the result.
		 *
		 * @return bool Returns true if the user should be onboarded, false otherwise.
		 */
		private function should_onboard(): bool
		{
			if (!cmplz_user_can_manage()) {
				return false;
			}

			// Force the signup, even if the user is already onboarded
			if (isset($_GET['cmplz_force_signup'])) {
				$cb_wsc_signup_status = cmplz_wsc_auth::wsc_api_open('signup');
				if (!$cb_wsc_signup_status) {
					cmplz_wsc_logger::log_errors('wsc_api_open', 'COMPLIANZ: WSC API is not open');
					return false;
				}
				return true;
			}

			// if already onboarded.
			if ( cmplz_wsc_auth::wsc_is_authenticated() ) {
				return false;
			}

			// If the WSC has been either reset or dismissed during the onboarding.
			if ( get_option( 'cmplz_wsc_reset_complete', false ) ) {
				return false;
			}

			$is_dismissed = $this->wsc_is_dismissed();
			if ( $is_dismissed ) {
				COMPLIANZ::$wsc_scanner->wsc_scan_forced();
				return false;
			}

			$onboarding_date = get_option( cmplz_wsc::WSC_OPT_ONBOARDING_DATE, false );

			if ( ! $onboarding_date ) {
				return false;
			}

			if ( $onboarding_date < time() ) { // If the onboarding date is in the past, show the onboarding modal.
				$cb_wsc_signup_status = cmplz_wsc_auth::wsc_api_open( 'signup' );
				if ( ! $cb_wsc_signup_status ) {
					cmplz_wsc_logger::log_errors( 'wsc_api_open', 'COMPLIANZ: the user can onboard but WSC API is not open' );
					return false;
				}
				return true;
			}
			return false;
		}


		/**
		 * Check and update the onboarding status.
		 *
		 * This method checks if the user is already onboarded or if the onboarding process has been dismissed.
		 * If the user is already onboarded, it logs a message and returns.
		 * If the onboarding process has been dismissed twice, it locks all scans and returns.
		 * Otherwise, it sets an onboarding date if it doesn't exist, or adjusts the date based on various conditions.
		 *
		 * @return void
		 */
		public function check_onboarding_status(): void {
			// Return if already onboarded.
			$is_onboarded = cmplz_wsc_auth::wsc_is_authenticated();
			if ( $is_onboarded ) {
				return;
			}

			$is_dismissed = $this->wsc_is_dismissed();
			if ( $is_dismissed ) { // if reached the max attempts do not set onboarding_start.
				return;
			}

			$onboarding_date = (int) get_option( cmplz_wsc::WSC_OPT_ONBOARDING_DATE, false );
			$now             = time();
			$staged_end      = strtotime( cmplz_wsc::WSC_ONBOARDING_STAGED_END );

			// Set an onboarding date if it doesn't exist.
			if ( ! $onboarding_date ) {
				// New installation: no signup_date, no dismissals.
				$signup_date = get_option( 'cmplz_wsc_signup_date', false );
				$onboarding_dismissed = get_option( 'cmplz_wsc_onboarding_dismissed', false );
				$websitescan_dismissed = get_option( 'cmplz_wsc_websitescan_dismissed', false );
				$is_new_installation = ! $signup_date && ! $onboarding_dismissed && ! $websitescan_dismissed;
				
				if ( $is_new_installation ) {
					// New installation: force immediate onboarding.
					$this->set_onboarding_date( $now - 10 );
				} else {
					// Dismissed scenario: use random delay between 120 seconds and 14 days
					$new_date = $now + wp_rand( 120, 14 * DAY_IN_SECONDS );
					$this->set_onboarding_date( $new_date );
				}
				return;
			}

			// Here the onboarding date is already set.
			// If the onboarding date is in the past or within the staged rollout period, do nothing.
			if ( $onboarding_date < $now || $onboarding_date <= $staged_end ) {
				return;
			}

			// Here the onboarding date is > $staged_end.
			// Calculate the time difference between onboarding_date and staged_end.
			$time_to_onboard = $onboarding_date - $staged_end;

			// If $now is past $staged_end and the difference is less than 15 days, keep the existing date.
			if ( $now > $staged_end && $time_to_onboard < 15 * DAY_IN_SECONDS ) {
				return;
			}

			// If $now is past $staged_end and the difference is more than 30 days, randomize within the next 2 weeks.
			if ( $now > $staged_end && $time_to_onboard > 30 * DAY_IN_SECONDS ) {
				$new_date = $now + wp_rand( 1, 14 ) * DAY_IN_SECONDS;
				$this->set_onboarding_date( $new_date );
				return;
			}

			// If $now is past $staged_end and the difference is between 15 and 30 days, randomize within the next week.
			if ( $now > $staged_end && $time_to_onboard <= 30 * DAY_IN_SECONDS ) {
				$new_date = $now + wp_rand( 1, 7 ) * DAY_IN_SECONDS;
				$this->set_onboarding_date( $new_date );
				return;
			}

			// Fallback | Default case: Randomize within the range of $time_to_onboard.
			$new_date = $now + wp_rand( 120, $time_to_onboard );
			$this->set_onboarding_date( $new_date );
		}

		/**
		 * Sets the onboarding date.
		 *
		 * This method updates the CMPLZ_OPT_ONBOARDING_DATE option with the provided date
		 * and returns the updated date.
		 *
		 * @param int $date The onboarding date timestamp to be set.
		 * @return void
		 */
		private function set_onboarding_date( int $date ): void {
			update_option( cmplz_wsc::WSC_OPT_ONBOARDING_DATE, $date, false );
		}


		/**
		 * Retrieves the onboarding docs (terms and conditions or newsletter policy) from the cookiedatabase endpoint.
		 *
		 * @param string $type The type of document to retrieve (wsc_terms or newsletter_terms).
		 * @return array An array containing the terms and conditions.
		 */
		private function get_onboarding_doc(string $type): array
		{
			$current_user_locale = get_user_locale();
			$param = str_replace('_', '-', $current_user_locale);
			$endpoint = $type === 'wsc_terms' ? cmplz_wsc_auth::WSC_TERMS_ENDPOINT : cmplz_wsc_auth::NEWSLETTER_TERMS_ENDPOINT;
			$endpoint = base64_decode($endpoint);

			$request = wp_remote_get($endpoint . '/' . $param, array(
				'timeout' => 15,
				'sslverify' => true,
				'headers' => [
					'Accept' => 'application/json',
					'Content-Type' => 'application/json; charset=utf-8',
				],
			));

			// Check for errors
			if (is_wp_error($request)) {
				// If there's an error, get the error message
				$error_message = $request->get_error_message();
				return [
					'doc' => false,
					'error' => $error_message
				];
			}
			// Check for valid response code
			$response_code = wp_remote_retrieve_response_code($request);

			if ($response_code !== 200) {
				return [
					'doc' => false,
					'error' => 'COMPLIANZ: error retrieving terms and conditions'
				];
			}

			// Get the body of the response
			$body = wp_remote_retrieve_body($request);
			$decoded_body = json_decode($body);

			if (json_last_error() !== JSON_ERROR_NONE || !isset($decoded_body->data)) {
				return [
					'doc' => false,
					'error' => 'COMPLIANZ: error processing the response'
				];
			}

			$output = json_decode($body)->data;

			return [
				'doc' => $output
			];
		}


		/**
		 * Processes the plugin actions.
		 *
		 * This method processes the plugin action, such as installing or activating a plugin.
		 * It downloads the plugin if the action is to install the plugin, or activates the plugin if the action is to activate the plugin.
		 *
		 * @param string $action The action to be performed.
		 * @param array $posted_data The data associated with the action.
		 * @return array The updated list of recommended plugins with their status.
		 */
		private function process_plugin_action(string $action, array $posted_data): array
		{
			require_once(CMPLZ_PATH . 'class-installer.php');

			$slug = $posted_data['slug'] ?? [];
			$plugins = $posted_data['plugins'] ?? [];

			$plugin = new cmplz_installer($slug);

			if ($action === 'install_plugin') {
				$plugin->download_plugin();
			} elseif ($action === 'activate_plugin') {
				$plugin->activate_plugin();
			}

			return $this->get_recommended_plugins_status($plugins);
		}


		/**
		 * Retrieves the status of the recommended plugins.
		 *
		 * This method retrieves the status of the recommended plugins, such as Complianz and its add-ons.
		 * It checks if the plugin is downloaded, activated, or installed, and returns the status.
		 *
		 * @param array $plugins The list of recommended plugins.
		 * @return array The updated list of recommended plugins with their status.
		 */
		public function get_recommended_plugins_status(array $plugins): array
		{
			require_once(CMPLZ_PATH . 'class-installer.php');

			$plugins_left = 0;

			foreach ($plugins as $index => $plugin) {
				$slug = sanitize_title($plugin['slug']);
				$premium = $plugin['premium'] ?? false;
				$premium = $premium ? sanitize_title($premium) : false;
				//check if plugin is downloaded
				$installer = new cmplz_installer($slug);
				if (!$installer->plugin_is_downloaded()) {
					// check for plugins to download/install
					$plugins[$index]['status'] = 'not-installed';
					$plugins_left++;
				} else if ($installer->plugin_is_activated()) {
					$plugins[$index]['status'] = 'activated';
				} else {
					$plugins[$index]['status'] = 'installed';
				}

				//If not found, check for premium
				//if free is activated, skip this step
				//don't update is the premium status is not-installed. Then we leave it as it is.
				if ($premium && $plugins[$index]['status'] !== 'activated') {
					$installer = new cmplz_installer($premium);
					if ($installer->plugin_is_activated()) {
						$plugins[$index]['status'] = 'activated';
					} else if ($installer->plugin_is_downloaded()) {
						$plugins[$index]['status'] = 'installed';
					}
				}
			}

			if (!$plugins_left) {
				$this->update_onboarding_status('plugins', true);
			}

			return $plugins;
		}


		/**
		 * Updates the onboarding status for a specific step.
		 *
		 * This method updates the onboarding status for the given step with the provided value.
		 * It retrieves the current onboarding status from the WordPress options, updates the status
		 * for the specified step, and saves the updated status back to the options.
		 * If the 'terms', 'newsletter', and 'plugins' steps are all marked as true, it sets the
		 * onboarding complete flag.
		 *
		 * @param string $step The step for which the onboarding status is being updated.
		 * @param bool  $value The value to set for the specified step.
		 * @return void
		 */
		public static function update_onboarding_status(string $step, bool $value): void {
			$cmplz_wsc_onboarding_status = get_option('cmplz_wsc_onboarding_status', []);
			$cmplz_wsc_onboarding_status[$step] = $value;
			update_option('cmplz_wsc_onboarding_status', $cmplz_wsc_onboarding_status, false);

			// check the cmplz_wsc_onboarding_status array if 'terms', 'newsletter' and 'plugins' are true, set the onboarding complete flag
			if ($cmplz_wsc_onboarding_status['terms'] && $cmplz_wsc_onboarding_status['newsletter'] && $cmplz_wsc_onboarding_status['plugins']) {
				update_option('cmplz_wsc_onboarding_complete', true, false);
			}
		}


		/**
		 * Schedule a store onboarding consent event if not already scheduled.
		 *
		 * @param string $hook The action hook name.
		 * @param int $delay The delay in seconds for scheduling the event.
		 * @param array $posted_data The arguments to pass to the event.
		 * @return void
		 */
		public static function schedule_store_onboarding_consent(string $hook, int $delay, array $posted_data): void
		{
			if (wp_next_scheduled($hook, $posted_data)) {
				cmplz_wsc_logger::log_errors($hook, "COMPLIANZ: event '$hook' already scheduled");
				return;
			}

			$event = wp_schedule_single_event(time() + $delay, $hook, $posted_data);

			if (is_wp_error($event)) {
				cmplz_wsc_logger::log_errors($hook, "COMPLIANZ: error scheduling event '$hook': " . $event->get_error_message());
			}
		}


		/**
		 * Handles the wsc onboarding consent.
		 *
		 * This static method is responsible for storing the consent given by the user
		 * during the onboarding process for wsc.
		 *
		 * @param string $type The type of consent being stored.
		 * @param array $posted_data The data associated with the consent.
		 * @return void
		 */
		public static function cmplz_store_wsc_onboarding_consent_handler(string $type, array $posted_data): void
		{
			cmplz_wsc_auth::store_onboarding_consent($type, $posted_data);
		}

		/**
		 * Check and handle WSC consent.
		 *
		 * This static method checks if the user has executed the onboarding/authentication process.
		 * If the onboarding is complete but the consent is missing, it schedules an event to send the consent again.
		 *
		 * @return void
		 */
		public function check_wsc_consent(): void
		{
			$hook = 'cmplz_store_wsc_onboarding_consent';

			if ($this->cmplz_retrieve_scheduled_event_by_hook($hook)) { // If the wsc consent is already scheduled, exit
				return;
			}

			$signup_date = get_option('cmplz_wsc_signup_date');
			if (!$signup_date) { // exit if the onboarding/authentication is not complete
				return;
			}

			$consent = get_option('cmplz_consent_wsc_consent');
			if ($consent) { // exit if the consent already exists
				return;
			}

			$email_address = cmplz_get_option(cmplz_wsc::WSC_EMAIL_OPTION_KEY);
			if (!is_email($email_address)) { // exit if the email is not set
				return;
			}

			$timestamp = $signup_date * 1000; // convert seconds to milliseconds to match the javascript timestamp of the react app
			$url = add_query_arg('retry', 'true', site_url()); // pass the site_url adding retry=true to identify this is a missed consent

			$posted_data = [
				'email' => $email_address,
				'timestamp' => $timestamp,
				'url' => esc_url($url),
			];

			// schedule a single event to send the consent after 1000 seconds
			self::schedule_store_onboarding_consent($hook, 1000, ['wsc_consent', $posted_data]);
			cmplz_wsc_logger::log_errors('check_wsc_consent', 'COMPLIANZ: missed wsc_consent scheduled');
		}

		/**
		 * Retrieve the already scheduled event.
		 *
		 * This method retrieves the already scheduled event from the cron array.
		 *
		 * @param string $hook The action hook name.
		 * @return bool Returns true if the event is already scheduled, false otherwise.
		 */
		public function cmplz_retrieve_scheduled_event_by_hook($hook): bool {
			$cron_array = _get_cron_array();

			foreach ($cron_array as $timestamp => $events) {
				foreach ($events as $key => $event) {
					if ($key === $hook) {
						return true;
					}
				}
			}
			return false;
		}

		/**
		 * Handles the onboarding dismiss action.
		 *
		 * This method handles the onboarding dismiss action for the specified step.
		 * It increments the dismissed count for the specified step and updates the option accordingly.
		 * The method also deletes the onboarding date option to reset the onboarding process.
		 *
		 * @param string $step The step for which the onboarding dismiss action is being handled.
		 * @return void
		 */
		private function store_onboarding_dismiss( string $step ): void {
			if ( ! in_array( $step, array( 'websitescan', 'newsletter', 'onboarding' ), true ) ) {
				return;
			}

			delete_option( cmplz_wsc::WSC_OPT_ONBOARDING_DATE );

			switch ( $step ) {
				case 'websitescan':
					$dismiss = (int) get_option( 'cmplz_wsc_websitescan_dismissed', 0 );
					++$dismiss;
					update_option( 'cmplz_wsc_websitescan_dismissed', $dismiss, false );
					break;
				case 'newsletter':
					update_option( 'cmplz_wsc_newsletter_dismissed', true, false );
					break;
				case 'onboarding':
					$dismiss = (int) get_option( 'cmplz_wsc_onboarding_dismissed', 0 );
					++$dismiss;
					update_option( 'cmplz_wsc_onboarding_dismissed', $dismiss, false );
					break;
				default:
					break;
			}
			if ( $this->wsc_is_dismissed() ) {
				COMPLIANZ::$wsc_scanner->wsc_scan_forced();
			}
		}


		/**
		 * Handles the onboarding dismiss action on plugin auto-update.
		 *
		 * This method checks if the Complianz GDPR plugin is being auto-updated.
		 * If the plugin is found in the list of updated plugins, it calls the handle_onboarding_dismiss method.
		 *
		 * @param array $results The results of the auto-update process.
		 * @return void
		 */
		public function handle_onboarding_dismiss_on_autoupdate( array $results ): void {
			if ( empty( $results['plugin'] ) || ! is_array( $results['plugin'] ) ) {
				return;
			}

			$plugin_slug = self::CMPLZ_PLUGIN_SLUG;

			foreach ( $results['plugin'] as $plugin ) {
				if ( ! empty( $plugin->item->slug ) && strpos( $plugin->item->slug, $plugin_slug ) !== false ) {
					$this->check_onboarding_status();
					break;
				}
			}
		}


		/**
		 * Checks if the onboarding or websitescan has been dismissed.
		 *
		 * This method retrieves the dismissal counts for both onboarding and websitescan,
		 * sums them up, and returns true if the total dismissals are greater than or equal to 2.
		 *
		 * @return bool True if the total dismissals are greater than or equal to 2, false otherwise.
		 */
		public function wsc_is_dismissed(): bool {
			$onboarding_dismissed  = (int) get_option( 'cmplz_wsc_onboarding_dismissed', false );
			$websitescan_dismissed = (int) get_option( 'cmplz_wsc_websitescan_dismissed', false );
			$total_dismissed       = $onboarding_dismissed + $websitescan_dismissed;
			$is_dismissed          = $total_dismissed >= self::WSC_MAX_DISMISS;

			if ( $is_dismissed ) {
				delete_option( cmplz_wsc::WSC_OPT_ONBOARDING_DATE );
			}

			return $is_dismissed;
		}


		/**
		 * Checks if the WSC (Website Scan) is locked.
		 *
		 * This method checks if the WSC is locked by verifying if the user is already authenticated.
		 * If the user is authenticated, it returns false indicating that the WSC is not locked.
		 * If the user is not authenticated, it checks if the WSC has been dismissed and returns the result.
		 *
		 * @return bool Returns true if the WSC is locked, false otherwise.
		 */
		public function wsc_locked(): bool {
			$is_already_authenticated = cmplz_wsc_auth::wsc_is_authenticated();

			if ( $is_already_authenticated ) {
				return false;
			}

			return $this->wsc_is_dismissed();
		}


		/**
		 * Handles the onboarding dismiss action on plugin upgrade.
		 *
		 * This method checks if the Complianz GDPR plugin is being updated.
		 * If the plugin is found in the list of updated plugins, it calls the handle_onboarding_dismiss method.
		 *
		 * @param WP_Upgrader $upgrader The upgrader instance.
		 * @param array       $hook_extra Additional information about the upgrade process.
		 * @return void
		 */
		public function handle_onboarding_dismiss_on_upgrade( WP_Upgrader $upgrader, array $hook_extra ): void {
			$plugin_slug = self::CMPLZ_PLUGIN_SLUG;
			if ( 'update' === $hook_extra['action'] && 'plugin' === $hook_extra['type'] && isset( $hook_extra['plugins'] ) ) {
				foreach ( $hook_extra['plugins'] as $plugin ) {
					if ( strpos( $plugin, $plugin_slug ) !== false ) {
						$this->check_onboarding_status();
						break;
					}
				}
			}
		}
	}
}
