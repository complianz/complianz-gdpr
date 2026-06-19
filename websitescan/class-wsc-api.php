<?php
defined('ABSPATH') or die("you do not have access to this page!");

if (!class_exists("cmplz_wsc_api")) {
	class cmplz_wsc_api
	{
		private static $_this;

		function __construct()
		{
			if (isset(self::$_this))
				wp_die(sprintf('%s is a singleton class and you cannot create a second instance.', get_class($this)));

			self::$_this = $this;
			add_action('rest_api_init', array($this, 'wsc_scan_enable_webhook_api'));
		}

		static function this()
		{
			return self::$_this;
		}

		/**
		 * Register the REST API route for the WSC scan.
		 *
		 * This function registers a custom REST API route for the WSC scan. The route
		 * accepts only POST requests and uses the `wsc_scan_callback` method as the
		 * callback function.
		 *
		 * @return void
		 */
		public function wsc_scan_enable_webhook_api(): void
		{
			register_rest_route('complianz/v1', 'wsc-scan', array(
				'methods' => 'POST', // Accept only POST requests
				'callback' => array($this, 'wsc_scan_webhook_callback'),
				'permission_callback' => '__return_true',
			));
			register_rest_route(
				'complianz/v1',
				'wsc-checks',
				array(
					'methods'             => 'POST', // Accept only POST requests.
					'callback'            => array( $this, 'wsc_scan_webhook_checks_callback' ),
					'permission_callback' => '__return_true',
				)
			);
		}


		/**
		 * Handle the WSC scan webhook checks callback.
		 *
		 * This function processes the WSC scan webhook checks callback. It validates the request
		 * and then processes the scan checks. If the request is invalid, an error is returned.
		 *
		 * @param WP_REST_Request $request The REST API request object.
		 * @return WP_REST_Response|WP_Error The REST API response object or an error object.
		 */
		public function wsc_scan_webhook_checks_callback( WP_REST_Request $request ) {
			$error            = self::wsc_scan_validate_request( $request, 'checks' );
			$is_valid_request = empty( $error );

			if ( ! $is_valid_request ) { // if the array is not empty, contains an error and the request is invalid.
				return new WP_Error(
					$error['code'],
					$error['message'],
					array( 'status' => $error['status'] )
				);
			}

			$result = json_decode( $request->get_body() );

			COMPLIANZ::$wsc_scanner->wsc_scan_process_checks( $result );

			return new WP_REST_Response( 'Checks updated!', 200 );
		}


		/**
		 * Process the WSC scan webhook callback.
		 *
		 * This function processes the WSC scan webhook callback. It validates the request
		 * and then processes the scan results. If the request is invalid, an error is returned.
		 *
		 * @param WP_REST_Request $request The REST API request object.
		 * @return WP_REST_Response|WP_Error The REST API response object or an error object.
		 */
		public function wsc_scan_webhook_callback(WP_REST_Request $request)
		{
			// Return 200 silently so the WSC API does not retry, but skip processing.
			// Keeps in-flight scans from stalling if WSC is disabled mid-cycle.
			// Cheap option check only: wsc_scan_enabled() is unsafe here — it reaches
			// the admin-only cmplz_wsc class via get_token() and performs a REST
			// loopback, neither of which belongs in the unauthenticated webhook path.
			if ( get_option( 'cmplz_wsc_status' ) !== 'enabled' ) {
				return new WP_REST_Response( 'OK', 200 );
			}

			$error = self::wsc_scan_validate_request( $request,'scan' );
			$is_valid_request = empty($error); // if the array is empty, the request is valid

			if (!$is_valid_request) { // if the array is not empty, contains an error and the request is invalid
				return new WP_Error(
					$error['code'],
					$error['message'],
					array('status' => $error['status'])
				);
			}

			/**
			 * Filters WSC scan webhook handling.
			 *
			 * Return a WP_REST_Response to short-circuit default site-level processing.
			 * Return null to fall through to default handling below.
			 *
			 * @param WP_REST_Response|null $handled null = fall through.
			 * @param WP_REST_Request       $request The incoming REST request.
			 */
			$handled = apply_filters( 'cmplz_wsc_scan_webhook_handled', null, $request );
			if ( $handled !== null ) {
				return $handled;
			}

			// start the processing of the request
			$result = json_decode($request->get_body());

			$current_wsc_status = get_option('cmplz_wsc_scan_status');
			// if the scan is already completed, exit
			if ($current_wsc_status === 'completed') {
				return new WP_REST_Response('Scan already completed.', 200);
			}

			if (!isset($result->data->result->trackers) || !is_array($result->data->result->trackers) || count($result->data->result->trackers) === 0) {
				// Mark the scan as completed even when no cookies were found — otherwise
				// the status stays 'progress' forever and the admin scan UI keeps
				// polling get_scan_progress indefinitely.
				COMPLIANZ::$wsc_scanner->wsc_complete_cookie_scan( $result, true );
				return new WP_REST_Response('No cookies found in the result.', 200);
			}

			COMPLIANZ::$wsc_scanner->wsc_complete_cookie_scan( $result, true );

			return new WP_REST_Response('Cookies updated!', 200);

		}

		/**
		 * Validate the WSC scan webhook request.
		 *
		 * This function validates the WSC scan webhook request. It checks if the request
		 * is valid and contains the necessary information to process the scan results.
		 *
		 * @param WP_REST_Request $request The REST API request object.
		 * @return array If the request is invalid an array containing the error details, otherwise an empty array.
		 */
		public static function wsc_scan_validate_request(WP_REST_Request $request, $type): array
		{
			// check the body
			if (empty($request->get_body())) {
				return [
					'code' => 'invalid_request',
					'message' => 'Request blocked: missing request.',
					'status' => 400
				];
			}

			/**
			 * Filters WSC scan request validation.
			 *
			 * Return [] to mark the request as valid and skip default validation.
			 * Return an error array (keys: code, message, status) to reject.
			 * Return null to fall through to default validation below.
			 *
			 * @param array|null      $result  null = fall through.
			 * @param WP_REST_Request $request The incoming REST request.
			 * @param string          $type    'scan' or 'checks'.
			 */
			$override = apply_filters( 'cmplz_wsc_scan_validate_request', null, $request, $type );
			if ( $override !== null ) {
				return $override;
			}

			// Get options for permission check
			$scan_id = $type === 'scan' ? get_option('cmplz_wsc_scan_id', false) : get_option('cmplz_wsc_checks_scan_id',false);
			$scan_created_at = $type === 'scan' ? get_option('cmplz_wsc_scan_createdAt', false) : get_option('cmplz_wsc_checks_scan_createdAt',false);
			// Check if there is an active scan
			if (!$scan_id || !$scan_created_at) {
				return [
					'code' => 'invalid_wsc_scan',
					'message' => 'No active scan found.',
					'status' => 400
				];
			}

			$header_error = self::validate_scan_headers( $request );
			if ( ! empty( $header_error ) ) {
				return $header_error;
			}

			// Return the errors array if any errors are found, or an empty array if all checks pass
			return [];
		}

		/**
		 * Validate User-Agent and event fields shared by all scan webhook types.
		 *
		 * @param WP_REST_Request $request Incoming REST request.
		 * @return array Empty on success; error array (code, message, status) on failure.
		 */
		public static function validate_scan_headers( WP_REST_Request $request ): array {
			$user_agent = (string) $request->get_header( 'User-Agent' );
			if ( false === strpos( $user_agent, 'radar' ) ) {
				return array(
					'code'    => 'invalid_user_agent',
					'message' => 'Request blocked: unauthorized User-Agent.',
					'status'  => 400,
				);
			}

			$data = json_decode( $request->get_body() );
			if ( ! isset( $data->event ) || 'scan-completed' !== $data->event ) {
				return array(
					'code'    => 'invalid_event',
					'message' => 'Request blocked: missing or invalid scan status.',
					'status'  => 400,
				);
			}

			return array();
		}
	}
}
