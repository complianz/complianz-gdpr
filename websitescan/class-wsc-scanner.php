<?php
defined('ABSPATH') or die();


if (!class_exists("cmplz_wsc_scanner")) {

	class cmplz_wsc_scanner
	{
		private static $_this;

		const WSC_SCANNER_ENDPOINT = 'https://scan.complianz.io';
		const WSC_SCANNER_WEBHOOK_PATH = 'complianz/v1/wsc-scan';
		const WSC_SCANNER_DETECTIONS_WEBHOOK_PATH = 'complianz/v1/wsc-checks';

		/**
		 * Class constructor for the WSC scanner class.
		 *
		 * Initializes the WSC scanner class and sets it as a singleton class.
		 */
		function __construct()
		{
			if (isset(self::$_this)) {
				wp_die(sprintf('%s is a singleton class and you cannot create a second instance.',
					get_class($this)));
			}
			self::$_this = $this;
			$this->init_hooks();
		}


		/**
		 * Retrieve the instance of the class.
		 *
		 * @return object The instance of the class.
		 */
		static function this(): object
		{
			return self::$_this;
		}


		/**
		 * Initialize the hooks for the WSC scanner class.
		 *
		 * This function initializes the hooks for the WSC scanner class by adding an action
		 * to the `cmplz_remote_cookie_scan` hook.
		 *
		 * @return void
		 */
		private function init_hooks(): void
		{
			add_action('cmplz_remote_cookie_scan', array($this, 'wsc_scan_process'));
			add_action('admin_init', array($this, 'wsc_scan_init'));
			add_action( 'cmplz_wsc_checks_retrieve_results', array( $this, 'wsc_checks_retrieve' ) );
		}


		/**
		 * Check if the WSC scan is enabled.
		 *
		 * This function verifies several conditions to determine if the WSC scan is enabled:
		 * - If the site URL is 'localhost', the scan is disabled.
		 * - If the server address is 'localhost', the scan is disabled.
		 * - If the WSC scan circuit breaker is open, the scan is disabled.
		 * - If the hybrid scan is disabled, the scan is disabled.
		 * - If there is no token, the scan is disabled.
		 * If all conditions are met, the scan is enabled.
		 *
		 * @return bool True if the WSC scan is enabled, false otherwise.
		 */
		public function wsc_scan_enabled(): bool
		{
			// if localhost, return false
			$site_url = site_url();
			$host = wp_parse_url( $site_url, PHP_URL_HOST );

			if ($host === 'localhost') {
				return false;
			}

			// if server addr is localhost, return false
			if (!empty($_SERVER['SERVER_ADDR']) && $_SERVER['SERVER_ADDR'] === '127.0.0.1') {
				return false;
			}

			// if the wsc scan is enabled by the user through the APIs settings
			if (get_option('cmplz_wsc_status') !== 'enabled') {
				return false;
			}

			// circuit breaker
			if (!$this->wsc_check_cb()) {
				return false;
			}

			// if no token, return false
			if (!cmplz_wsc_auth::get_token()) {
				return false;
			}

			return true;
		}


		/**
		 * Check if the WSC scan circuit breaker is open.
		 *
		 * This function checks the status of the WSC scan circuit breaker by retrieving the value from the transient cache.
		 * If the value is empty, it retrieves the status from the WSC API and stores it in the transient cache for 5 minutes.
		 *
		 * @return bool True if the WSC scan circuit breaker is open, false otherwise.
		 */
		public function wsc_check_cb(): bool
		{
			$cb = cmplz_get_transient('wsc_scanner_cb_enabled'); // check the status of the cb
			if (empty($cb)) {
				$cb = cmplz_wsc_auth::wsc_api_open('scanner');
				cmplz_set_transient('wsc_scanner_cb_enabled', $cb, 300); // store the status for 5 minutes
			}
			return $cb;
		}


		/**
		 * Start the WSC scan.
		 *
		 * This function initiates the WSC scan process by sending a request to the WSC API.
		 * It retrieves the scan URL, depth, visit, source, and token from the options and sends them in the request body.
		 * If the request is successful, it stores the scan ID, created at, and status in the wp options.
		 *
		 * @return void
		 */
		private function wsc_scan_start(): void
		{
			// retrieve the token
			$token = cmplz_wsc_auth::get_token(true); // get a new token

			if (!$token) {
				cmplz_wsc_logger::log_errors('wsc_scan_start', 'COMPLIANZ: no token');
				return;
			}

			$url = esc_url_raw($this->wsc_scan_get_site_url());

			$source = $this->wsc_get_scanner_source();

			if (!$source) {
				return;
			}

			$body = array(
				'url'                     => $url,
				'acceptBanner'            => 'true',
				'getTrackers'             => 'true',
				'source'                  => $source,
				'detectTechnologies'      => 'false',
				'detectTcf'               => 'false',
				'detectLanguages'         => 'false',
				'detectGoogleConsentMode' => 'false',
				'detectLegalDocuments'    => 'false',
			);

			// use the webhook only with ssl
			$webhook_endpoint = esc_url_raw( get_rest_url( null, self::WSC_SCANNER_WEBHOOK_PATH ) );

			if ( $this->wsc_use_webhook( $webhook_endpoint ) !== '' ) {
				$body['webhook'] = $webhook_endpoint;
			}

			$request = $this->wsc_scan_request( $token, $body );

			if (is_wp_error( $request )) {
				cmplz_wsc_logger::log_errors('wsc_scan_start', 'COMPLIANZ: scan request failed, error: ' . $request->get_error_message());
				return;
			}

			$response = json_decode(wp_remote_retrieve_body($request));

			if (!isset($response->id)) {
				cmplz_wsc_logger::log_errors('wsc_scan_start', 'COMPLIANZ: no id in response');
				return;
			}

			// use these options for the webhooks
			update_option('cmplz_wsc_scan_id', $response->id, false);
			update_option('cmplz_wsc_scan_createdAt', $response->createdAt, false);
			update_option('cmplz_wsc_scan_status', 'progress', false);
		}

		/**
		 * Validate and return the webhook endpoint if it uses HTTPS.
		 *
		 * This function parses the provided webhook endpoint URL and checks if the scheme is HTTPS.
		 * If the scheme is HTTPS, it returns the original webhook endpoint URL. Otherwise, it returns an empty string.
		 *
		 * @param string $webhook_endpoint The webhook endpoint URL to validate.
		 * @return string The validated webhook endpoint URL if it uses HTTPS, or an empty string otherwise.
		 */
		private function wsc_use_webhook( string $webhook_endpoint ): string {
			$parsed_webhook_endpoint = wp_parse_url( $webhook_endpoint );
			if ( isset( $parsed_webhook_endpoint['scheme'] ) && 'https' === $parsed_webhook_endpoint['scheme'] ) {
				return $webhook_endpoint;
			}
			return '';
		}

		/**
		 * Send a WSC scan request to the WSC API.
		 *
		 * This function sends a POST request to the WSC API to initiate a scan.
		 * It includes the necessary headers and body in the request.
		 *
		 * @param mixed $token The authorization token for the WSC API.
		 * @param array $body The body of the request, containing the scan parameters.
		 * @return array|WP_Error The response from the WSC API or a WP_Error on failure.
		 */
		private function wsc_scan_request( $token, array $body ) {

			$args = array(
				'headers'   => array(
					'Content-Type' => 'application/json',
				),
				'timeout'   => 15,
				'sslverify' => true,
				'body'      => wp_json_encode( $body ),
			);

			if ( $token ) {
				$args['headers']['Authorization'] = 'oauth-' . $token;
			}

			return wp_remote_post(
				self::WSC_SCANNER_ENDPOINT . '/api/v1/scan',
				$args
			);
		}


		/**
		 * Process the WSC scan.
		 *
		 * Initiates the WSC scan process by checking if the scan is enabled.
		 * If enabled, it retrieves the scan status and processes the scan accordingly.
		 * The process includes starting the scan, checking the scan status, and retrieving cookies.
		 * Updates the scan status and progress based on the scan results.
		 * Repeats the process until the scan is completed or the maximum number of iterations is reached.
		 * The process is triggered by the `cmplz_remote_cookie_scan` action and stops if the scan is not enabled,
		 * the maximum iterations are reached, or the scan status is 'failed'.
		 *
		 * @return void
		 */
		public function wsc_scan_process(): void
		{
			if (!$this->wsc_scan_enabled()) {
				cmplz_wsc_logger::log_errors('wsc_scan_process', 'COMPLIANZ: wsc scan is not enabled');
				return;
			}

			$status = 'not-started';
			$max_iterations = 25;
			$cookies = [];

			$iteration = (int)get_option('cmplz_wsc_scan_iteration');
			$iteration++;

			// reached the max iterations the scan is completed
			if ($iteration > $max_iterations) {
				update_option('cmplz_wsc_scan_status', 'completed', false);
				update_option('cmplz_wsc_scan_progress', 100);
				return;
			}

			update_option("cmplz_wsc_scan_iteration", $iteration, false);

			// if the scan is not yet started
			if (!get_option('cmplz_wsc_scan_id')) {
				$this->wsc_scan_start(); // start the scan and store the scan id and scan status
				update_option('cmplz_wsc_scan_progress', 25); // set the progress to 25%
			}

			// once we have the scan id, we can check the status
			if (get_option('cmplz_wsc_scan_id') !== false) {
				$sleep = 6;
				sleep($sleep);
				update_option('cmplz_wsc_scan_progress', 25 + $iteration * 5);
				$scan_id = get_option( 'cmplz_wsc_scan_id' );
				$status = $this->wsc_scan_get_status( $scan_id, $iteration, $max_iterations ); // check the status of the scan.
			}

			if ($status === 'completed') { // if the status is completed.

				// if is already completed by webhook and progress is 100.
				$cookies = get_transient( 'cmplz_wsc_last_cookies' );
				update_option( 'cmplz_wsc_scan_progress', 100 );
				// run the checks once the scan is completed.
				$this->wsc_scan_run_checks();
			}

			// if failed, stop scan and mark as completed for now
			if ($status === 'failed') {
				update_option('cmplz_wsc_scan_status', 'completed', false);
				update_option('cmplz_wsc_scan_progress', 100, false);
			}

			//check if we have results
			if ( is_array( $cookies ) && count( $cookies ) > 0 ) {
				// store the cookies.
				$this->wsc_scan_store_cookies( $cookies );
			}
		}


		/**
		 * Store cookies retrieved from the WSC scan.
		 *
		 * This function processes an array of cookies, filters out non-cookie and non-webStorage types,
		 * and stores the remaining cookies using the CMPLZ_COOKIE class.
		 *
		 * @param array $cookies An array of cookie objects retrieved from the WSC scan.
		 */
		public function wsc_scan_store_cookies(array $cookies): void
		{
			foreach ($cookies as $key => $c) {
				// Skip if the type is not 'webStorage' or 'cookie'
				if ($c->type !== 'webStorage' && $c->type !== 'cookie') {
					continue;
				}

				$cookie = new CMPLZ_COOKIE();
				// Set the cookie type to 'localstorage' if it's 'webStorage', otherwise 'cookie'
				$cookie->type = $c->type === 'webStorage' ? 'localstorage' : 'cookie';
				// Set the domain to 'self' if it's 'webStorage', otherwise use the cookie's domain
				$cookie->domain = $c->type === 'cookie' ? $c->domain : 'self';
				// Add the cookie name and supported languages to the cookie object
				$cookie->add($c->name, COMPLIANZ::$banner_loader->get_supported_languages());
				// Save the cookie object
				$cookie->save(true);
			}
		}


		/**
		 * Reset the WSC scan options.
		 *
		 * This function deletes the options related to the WSC scan, effectively resetting the scan state.
		 * It removes the scan ID, scan status, scan progress, and scan iteration count from the WordPress options.
		 *
		 * @return void
		 */
		public static function wsc_scan_reset(): void
		{
			delete_option('cmplz_wsc_scan_id');
			delete_option('cmplz_wsc_scan_status');
			delete_option('cmplz_wsc_scan_progress');
			delete_option('cmplz_wsc_scan_iteration');
			delete_option('cmplz_wsc_scan_createdAt');
		}


		/**
		 * Check if the WSC scan is completed.
		 *
		 * This function checks if the WSC scan is enabled and then verifies if the scan progress
		 * has reached 100%, indicating that the scan is completed.
		 *
		 * @return bool True if the WSC scan is completed, false otherwise.
		 */
		public function wsc_scan_completed(): bool
		{
			if (!$this->wsc_scan_enabled()) { // force true
				cmplz_wsc_logger::log_errors('wsc_scan_completed', 'COMPLIANZ: wsc scan not enabled');
				return true;
			}
			return get_option('cmplz_wsc_scan_progress') === 100;
		}


		/**
		 * Get the progress of the WSC scan.
		 *
		 * This function checks if the WSC scan is enabled. If it is not enabled, it returns 100,
		 * indicating that the scan is complete. Otherwise, it retrieves the current scan progress
		 * from the WordPress options.
		 *
		 * @return int The current progress of the WSC scan, or 100 if the scan is not enabled.
		 */
		public function wsc_scan_progress(): int
		{
			if (!$this->wsc_scan_enabled()) {
				return 100;
			}
			return (int)get_option('cmplz_wsc_scan_progress');
		}


		/**
		 * Get the URL for the WSC scan.
		 *
		 * This function returns the URL to be used for the WSC scan. If the `SCRIPT_DEBUG` constant is defined,
		 * it returns the Complianz URL. Otherwise, it returns the site URL.
		 * @return string The URL to be used for the WSC scan.
		 */
		private function wsc_scan_get_site_url(): string {
			return site_url();
		}


		/**
		 * Retrieve the status of the WSC scan.
		 *
		 * This function retrieves the status of the WSC scan by making a request to the WSC API.
		 * It returns the status if the request is successful, or the default status otherwise.
		 *
		 * @param string $scan_id The id of the scan.
		 * @param int $iteration The current iteration of the status retrieval process.
		 * @param int $max_iterations The maximum number of iterations to retrieve the status.
		 * @return string The status of the WSC scan.
		 */
		private function wsc_scan_get_status(string $scan_id, int $iteration, int $max_iterations): string
		{
			$default_status = get_option('cmplz_wsc_scan_status', 'not-started');

			if ($default_status === 'completed') {
				return 'completed';
			}

			$response = $this->wsc_scan_retrieve_scan( $scan_id );

			// Early exit if response is an error or if recurring and iteration is >= 2
			if (is_wp_error($response) || $iteration >= $max_iterations) {
				cmplz_wsc_logger::log_errors('wsc_scan_get_status', 'COMPLIANZ: error retrieving scan status');
				return $default_status;
			}

			// Decode the response body
			$data = json_decode(wp_remote_retrieve_body($response));

			// Check if there was an error in the scan process
			if (isset($data->is_processed) && $data->is_processed === 'error' && isset($data->skipped_urls) && is_array($data->skipped_urls)) {
				foreach ($data->skipped_urls as $skipped) {
					if ($skipped->reason === 'PageNotLoadedError' && $skipped->url === $this->wsc_scan_get_site_url()) {
						cmplz_wsc_logger::log_errors('wsc_scan_get_status', 'COMPLIANZ: error in scan process');
						return 'failed';
					}
				}
			}

			// If an error occurred in the response, restart the scan and retry.
			if (isset($data->error)) {
				$this->wsc_scan_start();
				return $this->wsc_scan_get_status($scan_id, $iteration + 1, $max_iterations); // Retry with incremented iteration
			}

			if (isset($data->status) && $data->status === 'progress') {
				// if is in progress but we already have the trackers results.
				if (isset($data->result->trackers) && count($data->result->trackers) > 0) {
					$this->wsc_complete_cookie_scan($data);
					return 'completed';
				}
				update_option('cmplz_wsc_scan_status', $data->status, false);
				return $data->status;
			}

			// If a status is provided and is completed, update it and return.
			if (isset($data->status) && $data->status === 'completed') {
				$this->wsc_complete_cookie_scan( $data );
				return $data->status;
			}

			return $default_status;
		}


		/**
		 * Complete the WSC scan.
		 * This function completes the WSC scan by storing the cookies found during the scan in a transient.
		 * It stores the cookies in a transient for 24 hours and updates the scan status to 'completed'.
		 *
		 * @param object $scan The scan object containing the scan results.
		 * @param bool   $webhook True if the scan is completed by a webhook, false otherwise.
		 * @return void
		 */
		public function wsc_complete_cookie_scan( object $scan, bool $webhook = false ): void {

			if ( $webhook ) {
				$cookies = isset($scan->data->result->trackers) ? $scan->data->result->trackers : [];
			} else {
				$cookies = isset($scan->result->trackers) ? $scan->result->trackers : [];
			}

			// Store the cookies on a transient.
			set_transient( 'cmplz_wsc_last_cookies', $cookies, 60 * 60 * 24 );
			update_option( 'cmplz_wsc_scan_status', 'completed', false );

			if ( $webhook ) {
				update_option( 'cmplz_wsc_scan_progress', 100 );
			}
		}


		/**
		 * Retrieve the WSC scan.
		 *
		 * This function retrieves the WSC scan data by making a request to the WSC API.
		 * It returns the response if the request is successful, or an empty array otherwise.
		 *
		 * @param string $scan_id The id of the scan.
		 * @return array|WP_Error The response from the WSC API.
		 */
		private function wsc_scan_retrieve_scan( string $scan_id ) {
			$id = sanitize_text_field( $scan_id );

			$endpoint = self::WSC_SCANNER_ENDPOINT . '/api/v1/scans/' . $id;
			$token    = cmplz_wsc_auth::get_token();

			$args = array(
				'headers'   => array(
					'Content-Type' => 'application/json',
				),
				'timeout'   => 15,
				'sslverify' => true,
			);

			if ( $token ) {
				$args['headers']['Authorization'] = 'oauth-' . $token;
			}

			return wp_remote_get( $endpoint, $args );
		}

		/**
		 * Initialize the WSC scan.
		 *
		 * This function resets the old scanner to start the WSC scan if the WSC scan status is already enabled.
		 *
		 * @return void
		 */
		public function wsc_scan_init(): void
		{
			if (get_option('cmplz_wsc_status') !== 'enabled') {
				return;
			}

			if (get_option('cmplz_wsc_scan_first_run', false)) {
				return;
			}

			$processed_pages_list = get_transient('cmplz_processed_pages_list');

			if (!is_array($processed_pages_list) || !in_array("remote", $processed_pages_list)) {
				return;
			}

			$processed_pages_list = array_diff($processed_pages_list, ["remote"]);
			set_transient('cmplz_processed_pages_list', $processed_pages_list, MONTH_IN_SECONDS);
			update_option('cmplz_wsc_scan_first_run', true, false);
		}


		/**
		 * Get the source of the scanner.
		 *
		 * This function retrieves the source of the scanner based on the defined constants.
		 *
		 * @return string The source of the scanner.
		 */
		private function wsc_get_scanner_source(): string {
			$version = $this->get_cmplz_version();
			if (!$version) {
				return '';
			}

			$is_auth = cmplz_wsc_auth::wsc_is_authenticated() ? 'auth' : 'no-auth';

			$source_map = array(
				'cmplz_free'      => array(
					'auth'    => 'complianz_free_scan',
					'no-auth' => 'complianz_u_free_scan',
				),
				'cmplz_premium'   => array(
					'auth'    => 'complianz_premium_scan',
					'no-auth' => 'complianz_u_premium_scan',
				),
				'cmplz_multisite' => array(
					'auth'    => 'complianz_multisite_scan',
					'no-auth' => 'complianz_u_multisite_scan',
				),
			);

			return $source_map[ $version ][ $is_auth ] ?? '';
		}


		/**
		 * Get the Complianz version.
		 *
		 * This function checks for the defined constants to determine the Complianz version.
		 * It returns 'cmplz_free', 'cmplz_premium', or 'cmplz_multisite' based on the defined constants.
		 * If none of the constants are defined, it returns null.
		 *
		 * @return string|null The Complianz version or null if no version is defined.
		 */
		public function get_cmplz_version(): ?string {
			if ( defined( 'cmplz_free' ) ) {
				return 'cmplz_free';
			} elseif ( defined( 'cmplz_premium' ) ) {
				return 'cmplz_premium';
			} elseif ( defined( 'cmplz_premium_multisite' ) ) {
				return 'cmplz_multisite';
			}

			return null;
		}

		/**
		 * Run checks for the WSC scan.
		 *
		 * This function performs periodic checks for the WSC scan. It retrieves the last check time and compares it
		 * with the current time to ensure that checks are not performed more frequently than every 30 days.
		 * If the checks are due, it updates the last check time, retrieves a new token, and sends a scan request
		 * to the WSC API with the necessary payload.
		 * If the request is successful, it schedules a polling request to retrieve the scan results.
		 * If the request fails, it logs the error and retries the scan request up to three times.
		 * If the maximum number of retries is reached, it logs the error and aborts the scan request.
		 *
		 * @param int $retry The number of retries for the scan request.
		 * @return void
		 */
		private function wsc_scan_run_checks( int $retry = 0 ): void {
			$max_retries = 3;
			$last_check  = get_option( 'cmplz_wsc_checks_scan_createdAt', false ); // timestamp or false.

			// Do not run the checks more frequently than every 30 days.
			if ( $last_check && time() - $last_check < ( 30 * DAY_IN_SECONDS ) ) {
				return;
			}

			if ( get_transient( 'cmplz_wsc_checks_scan_error' ) ) {
				return;
			}

			if ( $retry >= $max_retries ) {
				set_transient( 'cmplz_wsc_checks_scan_error', true, 60 * 60 * 24 ); // stop the checks for 24 hours in case of error.
				cmplz_wsc_logger::log_errors( 'wsc_scan_run_checks', 'COMPLIANZ: max retries reached, scan request aborted' );
				return;
			}

			// Check for the token and source.
			$token  = cmplz_wsc_auth::get_token( true ); // Get a new token.
			$source = $this->wsc_get_scanner_source();

			if ( ! $source ) {
				cmplz_wsc_logger::log_errors( 'wsc_scan_run_checks', 'COMPLIANZ: no token/source' );
				return;
			}

			// Get the last check to now.
			update_option( 'cmplz_wsc_checks_scan_createdAt', time(), false );

			$url = esc_url_raw( $this->wsc_scan_get_site_url() );

			// define the payloads.
			$body = array(
				'url'                     => $url,
				'acceptBanner'            => 'true',
				'getTrackers'             => 'false',
				'source'                  => $source,
				'detectTechnologies'      => 'true',
				'detectLanguages'         => 'true',
				'detectGoogleConsentMode' => 'true',
				'detectLegalDocuments'    => 'true',
			);

			$webhook_endpoint = esc_url_raw( get_rest_url( null, self::WSC_SCANNER_DETECTIONS_WEBHOOK_PATH ) );
			$is_webhook       = $this->wsc_use_webhook( $webhook_endpoint ) !== '';

			if ( $is_webhook ) {
				$body['webhook'] = $webhook_endpoint;
			}

			$request = $this->wsc_scan_request( $token, $body );

			if ( is_wp_error( $request ) ) {
				delete_option( 'cmplz_wsc_checks_scan_createdAt' ); // reset the last_check timestamp.
				$this->wsc_scan_run_checks( $retry + 1 ); // restart the checks.
				cmplz_wsc_logger::log_errors( 'wsc_scan_run_checks', 'COMPLIANZ: scan #' . $retry . ' request failed, error: ' . $request->get_error_message() );
				return;
			}

			$response = json_decode( wp_remote_retrieve_body( $request ) );

			if ( ! isset( $response->id ) ) {
				delete_option( 'cmplz_wsc_checks_scan_createdAt' );  // reset the last_check timestamp.
				$this->wsc_scan_run_checks( $retry + 1 ); // restart the checks.
				cmplz_wsc_logger::log_errors( 'wsc_scan_run_checks', 'COMPLIANZ: no id in response' );
				return;
			}

			// Schedule polling.
			// Schedule a request after 5mn to retrieve the scan results and then process it using wsc_scan_process_checks().
			if ( ! $is_webhook && ! wp_next_scheduled( 'cmplz_wsc_checks_retrieve_results' ) ) {
				update_option( 'cmplz_wsc_checks_scan_polling', true, false );
				wp_schedule_single_event( time() + ( 60 * 5 ), 'cmplz_wsc_checks_retrieve_results', array( $retry ) );
			}

			// store the wsc checks scan id.
			update_option( 'cmplz_wsc_checks_scan_id', $response->id, false );
			update_option( 'cmplz_wsc_checks_scan_createdAt', $response->createdAt, false ); // update the last check value to the createdAt value.
		}

		/**
		 * Forcefully run the WSC scan checks.
		 *
		 * This method forcefully initiates the WSC scan checks by calling the `wsc_scan_run_checks` method
		 * with the retry count set to 0 and the forced flag set to true.
		 *
		 * @return void
		 */
		public function wsc_scan_forced() {
			$this->wsc_scan_run_checks();
		}


		/**
		 * Retrieve the WSC checks.
		 *
		 * This function retrieves the WSC checks by making a request to the WSC API.
		 * It handles polling, retry logic, and processes the scan results.
		 *
		 * @param int $retry The number of retries for the scan request.
		 * @return void
		 */
		public function wsc_checks_retrieve( int $retry ) {
			$max_retries = 3;

			$is_polling = get_option( 'cmplz_wsc_checks_scan_polling' );
			$scan_id    = get_option( 'cmplz_wsc_checks_scan_id' );
			if ( ! $is_polling || ! $scan_id ) { // if polling mode and scan id are false, return.
				return;
			}

			if ( $retry >= $max_retries ) { // if the max retries are reached, log the error and schedule the request.
				cmplz_wsc_logger::log_errors( 'wsc_checks_retrieve', 'COMPLIANZ: max retries reached, scan request aborted' );
				if ( wp_next_scheduled( 'cmplz_wsc_checks_retrieve_results' ) ) { // check for old scheduled event and clear it.
					wp_clear_scheduled_hook( 'cmplz_wsc_checks_retrieve_results' );
				}
				wp_schedule_single_event( time() + ( 60 * 15 ), 'cmplz_wsc_checks_retrieve_results' ); // schedule a new event after 15mn.
				return;
			}

			$response = $this->wsc_scan_retrieve_scan( $scan_id ); // retrieve the scan by scan_id.

			// if the response is an error or the scan status is not yet completed > log the error and schedule another request.
			if ( is_wp_error( $response ) ) {
				cmplz_wsc_logger::log_errors( 'wsc_checks_retrieve', 'COMPLIANZ: error retrieving scan status' );
				$this->wsc_checks_retrieve( $retry + 1 );
				return;
			}

			if ( isset( $data->status ) && 'completed' === $data->status ) {
				$this->wsc_scan_process_checks( $data );
				delete_option( 'cmplz_wsc_checks_scan_polling' );
			} else {
				$this->wsc_checks_retrieve( $retry + 1 );
			}
		}


		/**
		 * Process the WSC scan checks.
		 *
		 * This function processes the results of the WSC scan checks. It logs the function call,
		 * checks if notifications are enabled, validates the notification email address, and stores
		 * the scan results temporarily. It then starts the detection process, checking for various
		 * conditions and adding corresponding blocks to the detections array. Finally, it generates
		 * and sends a notification email based on the detections.
		 *
		 * @param object $result The result object from the WSC scan.
		 * @return void
		 */
		public function wsc_scan_process_checks( object $result ): void {
			// Store temporarily the results.
			set_transient( 'cmplz_wsc_last_checks', $result, 60 * 60 * 24 );

			// Start the detection process.
			$result                  = $result->data->result;
			$meta                    = $result->meta;
			$additional_technologies = $result->additional_technologies;
			$perfect_matches         = $result->perfect_matches;

			// define the detections array.
			$detections = array();

			/**
			 * Check for the detections.
			*/

			// Block #1 | Consent Mode V2 (error) | Notice if you have google services but no GCM.
			$is_gcm_detected   = isset( $meta->googleConsentMode->found ) ? $meta->googleConsentMode->found : false;
			$services_detected = array();
			$gcm_services      = array(
				'Google Analytics (Universal Analytics)',
				'Google Analytics',
				'Google Analytics 4',
				'Google Tag Manager',
				'Google Ads Remarketing',
				'Google Conversion linker',
				'Google Ad Manager',
				'Google Ad Manager Audience Extension',
				'Google Ads Audience Expansion',
				'Google Ads conversion tracking',
				'Google Ads Customer Match',
				'Google Ads enhanced conversions',
				'Google Ads Optimized Targeting',
				'Google Ads Similar audiences',
				'Google Ads Smart Bidding',
				'Google AdSense',
				'Google Analytics Advertising Reporting Features',
				'Google Analytics Demographics and Interests reports',
				'Google Analytics Granular location and device data collection',
				'Google Campaign Manager 360',
				'Google Floodlight conversion tracking (Floodlight tag)',
				'Google Programmable Search Engine with AdSense',
				'Google Publisher Tag',
				'Google Search Ads 360',
				'Google Signals'
			);

			$is_cmplz_gcm_enabled = cmplz_consent_mode(); // on free return false as default.
			// look for additional technologies matching the services.
			foreach ( $additional_technologies as $technology ) {
				if ( in_array( $technology->name, $gcm_services, true ) ) {
					$services_detected[] = $technology->name;
				}
			}

			foreach ( $perfect_matches as $technology ) {
				if ( in_array( $technology->name, $gcm_services, true ) ) {
					$services_detected[] = $technology->name;
				}
			}

			if ( ! $is_cmplz_gcm_enabled && ! $is_gcm_detected && ! empty( $services_detected ) ) {
				// update the detections array adding the block.
				$detections[] = array(
					'block' => 'block_01',
					'args'  => array(
						'technology' => reset( $services_detected ), // return just the first element.
					),
				);
			}

			// Block #2 | TCF (error) | Notice if you have Google Advertising Product but no TCF.
			$is_tcf_installed = isset( $meta->tcf->installed ) ? $meta->tcf->installed : false;
			$cmp_services     = array(
				'Google AdSense',
				'Google Ad Manager',
				'Google Ads',
				'Google Marketing Platform',
				'Google Campaign Manager 360',
				'Google Floodlight',
				'Google Analytics Advertising Features',
				'Adobe Advertising Cloud',
				'Adobe Audience Manager',
				'Amazon Advertising',
				'Criteo',
				'The Trade Desk',
				'Index Exchange',
				'Magnite',
				'PubMatic',
				'OpenX',
				'TripleLift',
				'Xandr',
				'Taboola',
				'Outbrain',
				'Quantcast',
				'Teads',
				'LiveRamp',
				'ID5',
				'Lotame',
				'InMobi',
				'Smaato',
				'Adform',
				'AppNexus',
				'Media.net',
				'Ezoic',
				'Nexx360',
				'Mediavine',
				'Raptive'
			);

			$cmp_services_detected = array();

			foreach ( $additional_technologies as $technology ) {
				if ( in_array( $technology->name, $cmp_services, true ) ) {
					$cmp_services_detected[] = $technology->name;
				}
			}

			if ( ! $is_tcf_installed && ! empty( $cmp_services_detected ) ) {
				$detections[] = array(
					'block' => 'block_02',
					'args'  => array(),
				);
			}

			// Block #3 | Multiple regions | Notice if you have multiple regions detected.
			$languages         = $meta->languages;
			$cleaned_languages = array();
			$seen_prefixes     = array();
			if ( isset( $languages ) ) {
				foreach ($languages as $language) {
					$prefix = explode('-', $language)[0];
					if (!in_array($prefix, $seen_prefixes, true)) {
						$cleaned_languages[] = $language;
						$seen_prefixes[] = $prefix;
					}
				}

				if (count($cleaned_languages) > 1) {
					$detections[] = array(
						'block' => 'block_03',
						'args' => array(),
					);
				}
			}

			// #4/5 check
			if ( empty( $meta->detected_pp ) ) {
				// Block #5 | Privacy statement | Notice if no privacy statement is detected.
				$detections[] = array(
					'block' => 'block_05',
					'args'  => array(),
				);
			} else {
				// Block #4 | Record of consents.
				$detections[] = array(
					'block' => 'block_04',
					'args'  => array(),
				);
			}

			// #6 check
			// Block #6 | Woocommerce || Check if Woocommerce is installed.
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			$installed_plugins        = get_plugins();
			$is_woocommerce_installed = isset( $installed_plugins['woocommerce/woocommerce.php'] );

			// if woocommerce is installed, send the block.
			if ( $is_woocommerce_installed ) {
				$detections[] = array(
					'block' => 'block_06',
					'args'  => array(),
				);
			}

			// #7 check
			// Block #7 | Cmp | Check if a CMP is detected.
			if ( isset( $meta->detected_competitor ) && 'Google' === $meta->detected_competitor ) {
				$detections[] = array(
					'block' => 'block_07',
					'args'  => array(),
				);
			}

			if ( 0 === count( $detections ) ) {
				return;
			}

			// Store the detections result.
			set_transient( 'cmplz_wsc_checks_last_detections', $detections, 0 );

			// Reset the previously dismissed warnings.
			$this->reset_wsc_dismissed_warnings( $detections );

			// Create and send the notification.
 			$disable_automatic_cookiescan = (bool) cmplz_get_option( 'disable_automatic_cookiescan' );
			if ( $disable_automatic_cookiescan ) {
				return;
			}

			// If the email address is not valid, return.
			$notification_email_address =  empty(cmplz_get_option('cmplz_wsc_email')) ? get_bloginfo('admin_email') : cmplz_get_option('cmplz_wsc_email');
			if ( ! filter_var( $notification_email_address, FILTER_VALIDATE_EMAIL ) ) {
				return;
			}

			$website = wp_parse_url( site_url(), PHP_URL_HOST );
			// translators: %s: website name.
			$title             = sprintf( __( '[Complianz] Compliance report for %s', 'complianz-gdpr' ), $website );
			$notification      = $this->wsc_checks_create_mail_notification( $detections, $title );
			$send_notification = $this->wsc_send_notification( $notification, $title, $notification_email_address );

			// Send the email.
			if ( $send_notification ) {
				update_option( 'cmplz_wsc_checks_last_mail_sent', time(), false );
			} else {
				update_option( 'cmplz_wsc_checks_last_mail_sent_error', time(), false );
			}
			// Remove the scan_id when the flow is completed, to prevent the wsc api from being called again.
			delete_option( 'cmplz_wsc_checks_scan_id' );
		}


		/**
		 * Send a notification email.
		 *
		 * This function sends a notification email using the provided notification content, title, and recipient email address.
		 * It sets the email headers, including the sender and content type, and uses the `cmplz_mailer` class to send the email.
		 *
		 * @param string $notification The content of the notification email.
		 * @param string $title The title of the notification email.
		 * @param string $notification_email_address The recipient email address for the notification.
		 * @return bool True if the email was sent successfully, false otherwise.
		 */
		private function wsc_send_notification( string $notification, string $title, string $notification_email_address ): bool {
			// define mailer.
			$headers = "Content-Type: text/html; charset=UTF-8";

			$mailer          = new cmplz_mailer();
			$mailer->to      = $notification_email_address;
			$mailer->subject = $title;
			$mailer->body    = $notification;
			$mailer->headers = $headers;

			return $mailer->send_basic_mail();
		}


		/**
		 * Create a notification message.
		 *
		 * This function generates a notification message based on the provided detections.
		 * It retrieves the notification blocks, maps the detections to the blocks, and formats the message.
		 *
		 * @param array  $detections An array of detections to process.
		 * @param string $title The title of the notification message.
		 * @return string The generated notification message.
		 */
		private function wsc_checks_create_mail_notification( array $detections, string $title ): string {
			// retrieve the blocks.
			$blocks = $this->wsc_checks_notification_blocks();
			// map detections to blocks.
			$notifications = $this->wsc_checks_notification_generate_mail_blocks( $detections, $blocks );

			// if no notifications, return.
			if ( count( $notifications ) === 0 ) {
				return '';
			}

			$website  = wp_parse_url( site_url(), PHP_URL_HOST );
			$site_url = site_url();

			// creating message.
			$message  = '<!DOCTYPE html>';
			$message .= '<html>';
			$message .= '<head>';
			$message .= '<meta charset="UTF-8">';
			$message .= '<title>' . esc_html( $title ) . '</title>';
			$message .= '</head>';
			$message .= '<body>';
			$message .= '<p>' . sprintf( // translators: %1$s: site url, %2$s: website name.
				__( 'This compliance report was sent from your site <a href="%1$s" target="_blank">%2$s</a> by Complianz', 'complianz-gdpr' ),
				esc_url( $site_url ),
				esc_html( $website )
			) . '</p>';
			$message .= '<p>' . __( 'You can find the most important takeaways below:', 'complianz-gdpr' ) . '</p>';
			// Add formatted blocks.
			foreach ( $notifications as $notification ) {
				$message .= '<strong>' . esc_html( $notification['label'] ) . '</strong>';
				$message .= '<p>' . $notification['description'] . '</p>';
			}
			$message .= '<hr>';
			$message .= '<p>';
			$message .= sprintf( // translators: %1$s: website name.
				__( 'Are you no longer the website administrator? <a href="%1$s" target="_blank">Click here</a> to dismiss notifications.', 'complianz-gdpr' ),
				esc_url( 'https://complianz.io/instructions/about-email-notifications/' )
			);
			$message .= '</p>';
			$message .= '</body>';
			$message .= '</html>';

			return $message;
		}


		/**
		 * Generate mail blocks for notifications.
		 *
		 * This function generates an array of notification mail blocks based on the provided detections,
		 * blocks, and Complianz version. It filters the blocks based on the plugin version, generates
		 * descriptions for each block, and sorts the notifications by priority.
		 *
		 * @param array $detections An array of detections to process.
		 * @param array $blocks An array of notification blocks.
		 * @return array An array of generated notification mail blocks.
		 */
		private function wsc_checks_notification_generate_mail_blocks( array $detections, array $blocks ): array {
			$cmplz_version = $this->get_cmplz_version();
			$notifications = array();

			foreach ( $detections as $detection ) {
				// Skip if the block is not enabled for the current cmplz version.
				if ( ! isset( $blocks[ $detection['block'] ][ $cmplz_version ] ) ) {
					continue;
				}

				$block           = $blocks[ $detection['block'] ][ $cmplz_version ]; // get the block.
				$priority        = $block['priority']; // Get the priority.
				$notifications[] = array(  // Create an array with the priority as key so can be sorted.
					'block'       => $detection['block'],
					'label'       => $block['label'],
					'description' => $this->wsc_checks_notification_generate_block_description( $block, $detection, false ),
					'type'        => $block['type'],
					'priority'    => $priority,
				);
			}

			// sort the notifications by priority.
			usort(
				$notifications,
				function ( $a, $b ) {
					return $b['priority'] <=> $a['priority'];
				}
			);

			// if the $notifications array is empty, return.
			if ( empty( $notifications ) ) {
				return array();
			}

			// if detections are more than 3, just return the first 3.
			return count( $notifications ) > 3 ? array_slice( $notifications, 0, 3, true ) : $notifications;
		}


		/**
		 * Generate a notification description.
		 *
		 * This function generates a description for a notification by replacing placeholders
		 * in the block's description with specific values from the block and detection arguments.
		 *
		 * @param array $block The block array containing the description and other details.
		 * @param array $detection The detection array containing arguments to replace in the description.
		 * @param bool  $is_warning True if the notification is a warning, false otherwise.
		 * @return string The generated description with placeholders replaced by actual values.
		 */
		public function wsc_checks_notification_generate_block_description( array $block, array $detection, bool $is_warning ): string {

			$description = $block['description'];

			// Replace placeholders from block description.
			if ( isset( $block['admin_url'] ) ) {
				$description = str_replace( '{admin_url}', esc_url( $block['admin_url'] ), $description );

				if ( $is_warning ) {
					$description = str_replace( 'target="_blank"', 'target="_self"', $description );
				}
			}

			if ( isset( $block['read_more'] ) && ! $is_warning ) {
				$read_more_link = sprintf(
					'<a target="_blank" href="%s"><strong>%s</strong></a>',
					esc_url( $block['read_more'] ),
					__( 'Read more', 'complianz-gdpr' )
				);

				$description .= ' ' . $read_more_link;
			}

			// Replace other placeholders from detection args if provided.
			if ( ! empty( $detection['args'] ) && is_array( $detection['args'] ) ) {
				foreach ( $detection['args'] as $key => $value ) {
					$placeholder = '{' . $key . '}';
					if ( strpos( $description, $placeholder ) !== false ) {
						$description = str_replace( $placeholder, $value, $description );
					}
				}
			}

			return $description;
		}


		/**
		 * Retrieve the notification blocks.
		 *
		 * This function returns an array of notification blocks, each containing details such as
		 * label, description, type, admin URL, read more URL, plugin version, and priority.
		 *
		 *
		 * Block structure:
		 * - cmplz_free: array | The block for the free version
		 * - cmplz_premium: array | The block for the premium version
		 * - cmplz_multisite: array | The block for the multisite version
		 *
		 * Sub-block structure:
		 * - default: bool | If no blocks are set, this block will be used
		 * - label: string | The label/title of the block
		 * - description: string | The description of the block, with placeholders for dynamic values
		 * - type: string | The type of the block (error, warning, info)
		 * - admin_url: string | The admin URL to link to, used if the description contains an admin URL placeholder '{admin_url}'
		 * - read_more: string | The read more URL to link to, if setted will be added to the description
		 * - plugin_version: array | The plugin versions for which the block is enabled
		 * - priority: int | The priority of the block
		 *
		 * @return array The array of notification blocks.
		 */
		public function wsc_checks_notification_blocks(): array {
			return array(
				'block_01' => array(
					'cmplz_free'      => array(
						'default'     => false,
						'label'       => __( 'Consent mode', 'complianz-gdpr' ),
						'description' => __( 'We have found {technology} on your site and recommend <a target="_blank" href="{admin_url}">enabling</a> Google Consent Mode V2 to optimize your analytics implementation.', 'complianz-gdpr' ),
						'type'        => 'urgent',
						'admin_url'   => $this->wsc_generate_admin_url( 'complianz#wizard/statistics-configuration' ),
						'read_more'   => 'https://complianz.link/cob01fr',
						'priority'    => 10,
					),
					'cmplz_premium'   => array(
						'default'     => false,
						'label'       => __( 'Consent mode', 'complianz-gdpr' ),
						'description' => __( 'We have found {technology} on your site and recommend <a target="_blank" href="{admin_url}">enabling</a> Google Consent Mode V2 to optimize your analytics implementation.', 'complianz-gdpr' ),
						'type'        => 'urgent',
						'admin_url'   => $this->wsc_generate_admin_url( 'complianz#wizard/statistics-configuration' ),
						'read_more'   => 'https://complianz.link/cob01pr',
						'priority'    => 10,
					),
					'cmplz_multisite' => array(
						'default'     => false,
						'label'       => __( 'Consent mode', 'complianz-gdpr' ),
						'description' => __( 'We have found {technology} on your site and recommend <a target="_blank" href="{admin_url}">enabling</a> Google Consent Mode V2 to optimize your analytics implementation.', 'complianz-gdpr' ),
						'type'        => 'urgent',
						'admin_url'   => $this->wsc_generate_admin_url( 'complianz#wizard/statistics-configuration' ),
						'read_more'   => 'https://complianz.link/cob01mu',
						'priority'    => 10,
					),
				),
				'block_02' => array(
					'cmplz_free'      => array(
						'default'     => false,
						'label'       => __( 'Advertising', 'complianz-gdpr' ),
						'description' => __( 'If you’re showing ads on your website it’s likely you need a Google certified CMP to make sure your ads are shown correctly. With Complianz you can <a target="_blank" href="{admin_url}">enable TCF</a> with our Google certified CMP.', 'complianz-gdpr' ),
						'type'        => 'urgent',
						'admin_url'   => $this->wsc_generate_admin_url( 'complianz#wizard/services' ),
						'read_more'   => 'https://complianz.link/adb02fr',
						'priority'    => 20,
					),
					'cmplz_premium'   => array(
						'default'     => false,
						'label'       => __( 'Advertising', 'complianz-gdpr' ),
						'description' => __( 'If you’re showing ads on your website it’s likely you need a Google certified CMP to make sure your ads are shown correctly. With Complianz you can <a target="_blank" href="{admin_url}">enable TCF</a> with our Google certified CMP.', 'complianz-gdpr' ),
						'type'        => 'urgent',
						'admin_url'   => $this->wsc_generate_admin_url( 'complianz#wizard/services' ),
						'read_more'   => 'https://complianz.link/adb02pr',
						'priority'    => 20,
					),
					'cmplz_multisite' => array(
						'default'     => false,
						'label'       => __( 'Advertising', 'complianz-gdpr' ),
						'description' => __( 'If you’re showing ads on your website it’s likely you need a Google certified CMP to make sure your ads are shown correctly. With Complianz you can <a target="_blank" href="{admin_url}">enable TCF</a> with our Google certified CMP.', 'complianz-gdpr' ),
						'type'        => 'urgent',
						'admin_url'   => $this->wsc_generate_admin_url( 'complianz#wizard/services' ),
						'read_more'   => 'https://complianz.link/adb02mu',
						'priority'    => 20,
					),
				),
				'block_03' => array(
					'cmplz_free'      => array(
						'default'     => false,
						'label'       => __( 'Privacy laws', 'complianz-gdpr' ),
						'description' => __( 'On websites with multiple languages you need to consider that your visitors might be from various regions, and therefore different privacy laws might apply. Double-check and see if you’re complying with all relevant regions.', 'complianz-gdpr' ),
						'type'        => 'open',
						'read_more'   => 'https://complianz.link/prb03fr',
						'priority'    => 30,
					),
					'cmplz_premium'   => array(
						'default'     => false,
						'label'       => __( 'Privacy laws', 'complianz-gdpr' ),
						'description' => __( 'On websites with multiple languages you need to consider that your visitors might be from various regions, and therefore different privacy laws might apply. Double-check and see if you’re complying with all relevant regions.', 'complianz-gdpr' ),
						'type'        => 'open',
						'read_more'   => 'https://complianz.link/prb03pr',
						'priority'    => 30,
					),
					'cmplz_multisite' => array(
						'default'     => false,
						'label'       => __( 'Privacy laws', 'complianz-gdpr' ),
						'description' => __( 'On websites with multiple languages you need to consider that your visitors might be from various regions, and therefore different privacy laws might apply. Double-check and see if you’re complying with all relevant regions.', 'complianz-gdpr' ),
						'type'        => 'open',
						'read_more'   => 'https://complianz.link/prb03mu',
						'priority'    => 30,
					),
				),
				'block_04' => array(
					'cmplz_free'      => array(
						'default'     => false,
						'label'       => __( 'Collecting data', 'complianz-gdpr' ),
						'description' => __( 'We found a privacy statement! Please double-check if your privacy statement has all the needed elements expected from a privacy statement. Make sure you also allow for data request forms and records of consent to support your privacy statement.', 'complianz-gdpr' ),
						'type'        => 'open',
						'read_more'   => 'https://complianz.link/cob04fr',
						'priority'    => 40,
					),
					'cmplz_premium'   => array(
						'default'     => false,
						'label'       => __( 'Collecting data', 'complianz-gdpr' ),
						'description' => __( 'We found a privacy statement! Please double-check if your privacy statement has all the needed elements expected from a privacy statement. Make sure you also allow for data request forms and records of consent to support your privacy statement.', 'complianz-gdpr' ),
						'read_more'   => 'https://complianz.link/cob04pr',
						'type'        => 'open',
						'priority'    => 40,
					),
					'cmplz_multisite' => array(
						'default'     => false,
						'label'       => __( 'Collecting data', 'complianz-gdpr' ),
						'description' => __( 'We found a privacy statement! Please double-check if your privacy statement has all the needed elements expected from a privacy statement. Make sure you also allow for data request forms and records of consent to support your privacy statement.', 'complianz-gdpr' ),
						'type'        => 'open',
						'read_more'   => 'https://complianz.link/cob04mu',
						'priority'    => 40,
					),
				),
				'block_05' => array(
					'cmplz_free'      => array(
						'default'     => false,
						'label'       => __( 'Privacy statement', 'complianz-gdpr' ),
						'description' => __( 'We didn’t find a privacy statement. Please <a target="_blank" href="{admin_url}">add a privacy statement</a> that has all the needed elements expected from a privacy statement. Make sure you allow for DSR and Consent Records that respect the data minimization principle as well.', 'complianz-gdpr' ),
						'type'        => 'open',
						'admin_url'   => $this->wsc_generate_admin_url( 'complianz#wizard/documents' ),
						'read_more'   => 'https://complianz.link/prb05fr',
						'priority'    => 50,
					),
					'cmplz_premium'   => array(
						'default'     => false,
						'label'       => __( 'Privacy statement', 'complianz-gdpr' ),
						'description' => __( 'We didn’t find a privacy statement. Please <a target="_blank" href="{admin_url}">add a privacy statement</a> that has all the needed elements expected from a privacy statement. Make sure you allow for DSR and Consent Records that respect the data minimization principle as well.', 'complianz-gdpr' ),
						'type'        => 'open',
						'admin_url'   => $this->wsc_generate_admin_url( 'complianz#wizard/documents' ),
						'read_more'   => 'https://complianz.link/prb05pr',
						'priority'    => 50,
					),
					'cmplz_multisite' => array(
						'default'     => false,
						'label'       => __( 'Privacy statement', 'complianz-gdpr' ),
						'description' => __( 'We didn’t find a privacy statement. Please <a target="_blank" href="{admin_url}">add a privacy statement</a> that has all the needed elements expected from a privacy statement. Make sure you allow for DSR and Consent Records that respect the data minimization principle as well.', 'complianz-gdpr' ),
						'type'        => 'open',
						'admin_url'   => $this->wsc_generate_admin_url( 'complianz#wizard/documents' ),
						'read_more'   => 'https://complianz.link/prb05mu',
						'priority'    => 50,
					),
				),
				'block_06' => array(
					'cmplz_free'      => array(
						'default'     => false,
						'label'       => __( 'WooCommerce', 'complianz-gdpr' ),
						'description' => __( 'When selling with WooCommerce, compliance with privacy laws and customer rights is essential. Complianz simplifies this by generating required documents and managing privacy obligations effectively.', 'complianz-gdpr' ),
						'type'        => 'open',
						'read_more'   => 'https://complianz.link/wob06fr',
						'priority'    => 60,
					),
					'cmplz_premium'   => array(
						'default'     => false,
						'label'       => __( 'WooCommerce', 'complianz-gdpr' ),
						'description' => __( 'When selling with WooCommerce, compliance with privacy laws and customer rights is essential. Complianz simplifies this by generating required documents and managing privacy obligations effectively.', 'complianz-gdpr' ),
						'type'        => 'open',
						'read_more'   => 'https://complianz.link/wob06pr',
						'priority'    => 60,
					),
					'cmplz_multisite' => array(
						'default'     => false,
						'label'       => __( 'WooCommerce', 'complianz-gdpr' ),
						'description' => __( 'When selling with WooCommerce, compliance with privacy laws and customer rights is essential. Complianz simplifies this by generating required documents and managing privacy obligations effectively.', 'complianz-gdpr' ),
						'type'        => 'open',
						'read_more'   => 'https://complianz.link/wob06mu',
						'priority'    => 60,
					),
				),
				'block_07' => array(
					'cmplz_free'      => array(
						'default'     => false,
						'label'       => __( 'Funding choices', 'complianz-gdpr' ),
						'description' => __( 'Are you using the Google consent banner for advertising and Complianz for everything else? Why not remove one banner by combining everything with our Google Certified CMP that enablesd TCF and Consent Mode for Google products, without the need of multiple consent banners.', 'complianz-gdpr' ),
						'type'        => 'open',
						'read_more'   => 'https://complianz.link/fub07fr',
						'priority'    => 70,
					),
					'cmplz_premium'   => array(
						'default'     => false,
						'label'       => __( 'Funding choices', 'complianz-gdpr' ),
						'description' => __( 'Are you using the Google consent banner for advertising and Complianz for everything else? Why not remove one banner by combining everything with our Google Certified CMP that enablesd TCF and Consent Mode for Google products, without the need of multiple consent banners.', 'complianz-gdpr' ),
						'type'        => 'open',
						'read_more'   => 'https://complianz.link/fub07pr',
						'priority'    => 70,
					),
					'cmplz_multisite' => array(
						'default'     => false,
						'label'       => __( 'Funding choices', 'complianz-gdpr' ),
						'description' => __( 'Are you using the Google consent banner for advertising and Complianz for everything else? Why not remove one banner by combining everything with our Google Certified CMP that enablesd TCF and Consent Mode for Google products, without the need of multiple consent banners.', 'complianz-gdpr' ),
						'type'        => 'open',
						'read_more'   => 'https://complianz.link/fub07mu',
						'priority'    => 70,
					),
				),
			);
		}


		/**
		 * Retrieve the last detections from the WSC checks.
		 *
		 * This function retrieves the last detections from the WSC checks by fetching the data
		 * stored in the transient cache with the key 'cmplz_wsc_checks_last_detections'.
		 *
		 * @return array The array of detections retrieved from the transient cache.
		 */
		public function wsc_checks_retrieve_detections(): array {
			$detections = get_transient( 'cmplz_wsc_checks_last_detections' );
			return is_array( $detections ) ? $detections : array();
		}

		/**
		 * Generate the admin URL for Complianz.
		 *
		 * This function generates an admin URL for Complianz by adding the specified page
		 * to the admin URL using the `add_query_arg` function.
		 *
		 * @param string $url The Complianz admin URL to be added as a query argument.
		 * @return string The generated admin URL.
		 */
		private function wsc_generate_admin_url( string $url ): string {
			return add_query_arg( array( 'page' => $url ), admin_url( 'admin.php' ) );
		}


		/**
		 * Reset the dismissed warnings for a specific WSC scan block.
		 *
		 * This method resets the dismissed warnings for a given WSC scan block by removing the block's
		 * entry from the 'cmplz_dismissed_warnings' option in the WordPress database.
		 *
		 * @param array $detections The block identifier for which the dismissed warnings should be reset.
		 * @return void
		 */
		public function reset_wsc_dismissed_warnings( array $detections ): void {
			if ( ! $detections ) {
				return;
			}

			$dismissed_warnings = get_option( 'cmplz_dismissed_warnings', false );

			if ( ! $dismissed_warnings || ! is_array( $dismissed_warnings ) ) {
				return;
			}

			foreach ( $detections as $detection ) {
				$block_id = 'wsc-scan_' . $detection['block'];

				// Delete the dismissed warning.
				if ( isset( $dismissed_warnings[ $block_id ] ) ) {
					unset( $dismissed_warnings[ $block_id ] );
					update_option( 'cmplz_dismissed_warnings', $dismissed_warnings );
				}
			}
		}
	}
}