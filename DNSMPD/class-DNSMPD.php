<?php
defined( 'ABSPATH' ) or die( 'you do not have access to this page!' );

if ( ! class_exists( 'cmplz_DNSMPD' ) ) {
	class cmplz_DNSMPD {
		private static $_this;

		function __construct() {
			if ( isset( self::$_this ) ) {
				wp_die(
					sprintf(
						'%s is a singleton class and you cannot create a second instance.',
						get_class( $this )
					)
				);
			}

			self::$_this = $this;

			if ( cmplz_has_region( 'us' ) ) {
				add_shortcode( 'cmplz-dnsmpi-request', array( $this, 'datarequest_form' ) );
			}

			add_action( 'rest_api_init', array( $this, 'register_rest_route' ) );
			add_filter( 'cmplz_datarequest_options', array( $this, 'datarequest_options' ), 20 );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
			// Data-request mail is dispatched off the request path (see process_restapi_datarequest).
			add_action( 'cmplz_process_datarequest_mail', array( $this, 'process_datarequest_mail' ) );
		}

		static function this() {
			return self::$_this;
		}

		/**
		 * Enqueue front-end assets
		 *
		 * @param $hook
		 */
		public function enqueue_assets( $hook ) {

			global $post;
			if ( $post && isset( $post->ID ) && ! COMPLIANZ::$document->is_complianz_page( $post->ID ) ) {
				return;
			}

			if ( ! cmplz_has_region( 'us' ) && ! cmplz_datarequests_active() ) {
				return;
			}
			$v = filemtime( CMPLZ_PATH . 'DNSMPD/script.min.js' );
			wp_enqueue_script( 'cmplz-dnsmpd', CMPLZ_URL . 'DNSMPD/script.min.js', array(), $v, true );
			wp_localize_script(
				'cmplz-dnsmpd',
				'cmplz_datarequests',
				array(
					'url' => get_rest_url( null, 'complianz/v1/datarequests' ),
				)
			);
		}

		/**
		 * Extend options with generic options
		 *
		 * @param array $options
		 *
		 * @return array
		 */
		public function datarequest_options( array $options = array() ): array {
			$options += array(
				'global_optout'             => array(
					'slug'  => 'definition/what-is-global-opt-out',
					'short' => __( 'Global opt-out', 'complianz-gdpr' ),
					'long'  => __( 'Global opt-out from selling and sharing my personal information and limiting the use or disclosure of sensitive personal information.', 'complianz-gdpr' ),
				),
				'cross_context'             => array(
					'slug'  => 'definition/what-is-cross-context-behavioral-advertising/',
					'short' => __( 'Do not sell my info', 'complianz-gdpr' ),
					'long'  => __( 'Do not sell my personal information for cross-context behavioral advertising', 'complianz-gdpr' ),
				),
				'limit_sensitive'           => array(
					'slug'  => 'definition/what-is-limit-sensitive-data/',
					'short' => __( 'Limit sensitive data', 'complianz-gdpr' ),
					'long'  => __( 'Limit the use of my sensitive personal information', 'complianz-gdpr' ),
				),
				'request_for_access'        => array(
					'slug'  => 'definition/what-is-the-right-to-access/',
					'short' => __( 'Request for access', 'complianz-gdpr' ),
					'long'  => __( 'Request for access', 'complianz-gdpr' ),
				),
				'right_to_be_forgotten'     => array(
					'slug'  => 'definition/right-to-be-forgotten/',
					'short' => __( 'Right to be Forgotten', 'complianz-gdpr' ),
					'long'  => __( 'Right to be Forgotten', 'complianz-gdpr' ),
				),
				'right_to_data_portability' => array(
					'slug'  => 'definition/right-to-data-portability/',
					'short' => __( 'Right to Data Portability', 'complianz-gdpr' ),
					'long'  => __( 'Right to Data Portability', 'complianz-gdpr' ),
				),

			);

			return $options;
		}

		/**
		 * Send confirmation mail
		 *
		 * @param string $email
		 * @param string $name
		 *
		 * @return void
		 */
		private function send_confirmation_mail( $email, $name ) {
			$message = cmplz_get_option( 'notification_email_content' );
			$subject = cmplz_get_option( 'notification_email_subject' );
			$message = str_replace( '{name}', $name, $message );
			$message = str_replace( '{blogname}', get_bloginfo( 'name' ), $message );
			$this->send_mail( $email, $subject, $message );
		}

		/**
		 * Send confirmation mail
		 *
		 * @return void
		 */
		private function send_notification_mail() {
			$email   = sanitize_email( apply_filters( 'cmplz_datarequest_email', get_option( 'admin_email' ) ) );
			$subject = cmplz_sprintf( __( 'You have received a new data request on %s', 'complianz-gdpr' ), get_bloginfo( 'name' ) );
			$message = $subject . '<br />' . cmplz_sprintf( __( 'Please check the data request on %s', 'complianz-gdpr' ), '<a href="' . site_url() . '" target="_blank">' . site_url() . '</a>' );
			$this->send_mail( $email, $subject, $message );
		}

		/**
		 * Send an email
		 *
		 * @param string $email
		 * @param string $subject
		 * @param string $message
		 *
		 * @return bool
		 */
		private function send_mail( $email, $subject, $message ) {
			$headers    = array();
			$from_name  = get_bloginfo( 'name' );
			$from_email = cmplz_get_option( 'notification_from_email' );
			add_filter(
				'wp_mail_content_type',
				function ( $content_type ) {
					return 'text/html';
				}
			);

			if ( ! empty( $from_email ) ) {
				$headers[] = 'From: ' . $from_name . ' <' . $from_email . '>'
							. "\r\n";
			}

			$success = true;
			if ( wp_mail( $email, $subject, $message, $headers ) === false ) {
				$success = false;
			}

			// Reset content-type to avoid conflicts -- http://core.trac.wordpress.org/ticket/23578
			remove_filter( 'wp_mail_content_type', 'set_html_content_type' );
			return $success;
		}

		/**
		 * Register the rest route
		 *
		 * @return void
		 */
		public function register_rest_route() {
			register_rest_route(
				'complianz/v1',
				'datarequests/',
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'process_restapi_datarequest' ),
					'args'                => array(),
					'permission_callback' => '__return_true',
				)
			);
		}

		/**
		 * Process the form submit
		 *
		 * @param WP_REST_Request $request
		 *
		 * @return array
		 */
		public function process_restapi_datarequest( WP_REST_Request $request ) {

			// This endpoint is public (permission_callback __return_true) and backs the
			// unauthenticated data-request form. Rate-limit per client IP BEFORE parsing
			// the body, touching the DB, or sending any email, so a remote flood cannot
			// spam arbitrary inboxes / the admin inbox or pollute wp_cmplz_dnsmpd.
			if ( ! $this->within_rate_limit() ) {
				return $this->rate_limited_response( $this->rate_limit_window() );
			}

			$params      = $request->get_json_params();
			$new_request = false;
			$error       = false;
			$message     = '';

			// check honeypot
			if ( isset( $params['cmplz_datarequest_firstname'] ) && ! empty( $params['cmplz_datarequest_firstname'] ) ) {
				$error   = true;
				$message = __( "Sorry, it looks like you're a bot", 'complianz-gdpr' );
			}

			if ( ! isset( $params['cmplz_datarequest_email'] ) || ! is_email( $params['cmplz_datarequest_email'] ) ) {
				$error   = true;
				$message = __( 'Please enter a valid email address.', 'complianz-gdpr' );
			}

			if ( ! isset( $params['cmplz_datarequest_name'] ) || empty( $params['cmplz_datarequest_name'] ) ) {
				$error   = true;
				$message = __( 'Please enter your name', 'complianz-gdpr' );
			}

			if ( strlen( $params['cmplz_datarequest_name'] ) > 100 ) {
				$error   = true;
				$message = __( "That's a long name you got there. Please try to shorten the name.", 'complianz-gdpr' );
			}

			if ( ! isset( $params['cmplz_datarequest_region'] ) || empty( $params['cmplz_datarequest_region'] ) ) {
				$region = 'us';
			}

			if ( ! $error ) {
				// Normalise the address once (lower-cased) and use the same value for the
				// cap, the dedup lookup and storage — no normalisation differential.
				$email = strtolower( sanitize_email( $params['cmplz_datarequest_email'] ) );

				$name   = sanitize_text_field( $params['cmplz_datarequest_name'] );
				$region = sanitize_title( $params['cmplz_datarequest_region'] );
				// check if this email address is already registered:
				global $wpdb;
				$submitted_any = false;
				$mail_row_id   = 0;
				$options       = apply_filters( 'cmplz_datarequest_options', array() );
				// $fieldname is used below as a DB column identifier, which cannot be bound
				// via $wpdb->prepare(); restrict it to the known request-type columns so a
				// filtered-in key can never reach the query as an identifier.
				$request_type_columns = array( 'global_optout', 'cross_context', 'limit_sensitive', 'request_for_access', 'right_to_be_forgotten', 'right_to_data_portability' );
				foreach ( $options as $fieldname => $label ) {
					if ( ! in_array( $fieldname, $request_type_columns, true ) ) {
						continue;
					}
					$value = isset( $params[ 'cmplz_datarequest_' . $fieldname ] ) ? intval( $params[ 'cmplz_datarequest_' . $fieldname ] ) : false;
					if ( $value === 1 ) {
						$submitted_any = true;
						// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $fieldname is validated against the $request_type_columns allowlist above; column identifiers cannot be parameterised.
						$count = $wpdb->get_var( $wpdb->prepare( "SELECT count(*) from {$wpdb->prefix}cmplz_dnsmpd WHERE email = %s and $fieldname=1", $email ) );
						if ( $count == 0 ) {
							$new_request = true;
							$wpdb->insert(
								$wpdb->prefix . 'cmplz_dnsmpd',
								array(
									'name'         => $name,
									'email'        => $email,
									'region'       => $region,
									$fieldname     => $value,
									'request_date' => time(),
								)
							);
							// Reference one stored row so the deferred mail job can read the
							// recipient (name/email) from the DB — no PII travels in the cron args.
							if ( ! $mail_row_id ) {
								$mail_row_id = (int) $wpdb->insert_id;
							}
						}
					}
				}

				if ( ! $submitted_any ) {
					// No request type selected: a completeness error about THIS request's
					// payload (client-side knowable) — not an enumeration signal.
					$error   = true;
					$message = __( 'Please select at least one request type.', 'complianz-gdpr' );
				} else {
					// The per-email cap gates the CONFIRMATION MAIL only — never the record
					// stored above. A genuine request whose email an attacker pre-exhausted
					// the cap for is still recorded (not suppressed) and still notifies the
					// admin; only the confirmation mail is withheld once the address is over
					// its cap, which bounds mail harassment. Keyed only on the email, so it
					// never changes the response (no enumeration oracle).
					if ( $new_request && $mail_row_id && $this->within_email_cap( $email ) ) {
						// Send mail off the request path (cron): keeps the response time
						// independent of whether mail was sent (closes the timing side channel)
						// and passes only the row ID (no PII) to the scheduled job.
						wp_schedule_single_event( time(), 'cmplz_process_datarequest_mail', array( $mail_row_id ) );
					}

					// Uniform response whether the request was newly stored or already on
					// file: never reveal ('a request is already in progress') that a given
					// (email, request-type) exists — that is a data-subject enumeration oracle.
					$message = __( 'Your request has been processed successfully!', 'complianz-gdpr' );
				}
			}

			return array(
				'message' => $message,
				'success' => ! $error,
			);
		}

		/**
		 * Increment and check the best-effort per-client rate limit for the public
		 * data-request endpoint.
		 *
		 * Uses WordPress core transients (object-cache friendly, not the option-backed
		 * cmplz_transients store) so a flood of distinct IPs cannot inflate wp_options.
		 *
		 * @return bool True while the client is within the limit.
		 */
		private function within_rate_limit(): bool {
			return $this->hit_counter( $this->client_key( 'cmplz_dr_rl_' ), $this->rate_limit_window(), $this->rate_limit_max() );
		}

		/**
		 * Best-effort client IP used to key the rate limit.
		 *
		 * Uses REMOTE_ADDR (never the spoofable X-Forwarded-For); a site behind a
		 * CDN/proxy can supply the real client IP via the filter so a shared edge IP
		 * does not bucket every visitor together.
		 *
		 * @return string The client IP, or '' when unavailable.
		 */
		private function client_ip(): string {
			$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
			return (string) apply_filters( 'cmplz_datarequest_client_ip', $ip );
		}

		/**
		 * Per-client transient key: the client IP is hashed with a site salt so no raw
		 * IP is stored and the key cannot be forged.
		 *
		 * @param string $prefix Transient key prefix.
		 * @return string The prefixed, client-IP-derived key.
		 */
		private function client_key( string $prefix ): string {
			return $prefix . md5( $this->client_ip() . '|' . wp_salt( 'nonce' ) );
		}

		/**
		 * Increment a windowed transient counter and report whether it is within the max.
		 *
		 * @param string $key    Transient key.
		 * @param int    $window Window in seconds.
		 * @param int    $max    Maximum hits allowed within the window.
		 * @return bool True while the counter is at or below the max.
		 */
		private function hit_counter( string $key, int $window, int $max ): bool {
			$count = (int) get_transient( $key ) + 1;
			set_transient( $key, $count, $window );
			return $count <= $max;
		}

		/**
		 * Maximum data-request posts allowed per client within the rate-limit window.
		 *
		 * @return int
		 */
		private function rate_limit_max(): int {
			return (int) apply_filters( 'cmplz_datarequest_rate_limit_max', 5 );
		}

		/**
		 * Rate-limit window in seconds.
		 *
		 * @return int
		 */
		private function rate_limit_window(): int {
			return (int) apply_filters( 'cmplz_datarequest_rate_limit_window', HOUR_IN_SECONDS );
		}

		/**
		 * Increment and check the per-email cap (independent of the per-IP limit).
		 * Keyed on a hashed+salted email so no address is stored in plain text; bounds
		 * how often one address can be targeted by the confirmation mail. The caller
		 * passes an already-normalised (lower-cased) address.
		 *
		 * @param string $email The submitted, normalised email address.
		 * @return bool True while the address is within its cap.
		 */
		private function within_email_cap( string $email ): bool {
			$key = 'cmplz_dr_ec_' . md5( $email . '|' . wp_salt( 'nonce' ) );
			return $this->hit_counter( $key, $this->email_cap_window(), $this->email_cap_max() );
		}

		/**
		 * Maximum data-requests accepted per email address within the cap window.
		 *
		 * @return int
		 */
		private function email_cap_max(): int {
			return (int) apply_filters( 'cmplz_datarequest_email_cap_max', 3 );
		}

		/**
		 * Per-email cap window in seconds.
		 *
		 * @return int
		 */
		private function email_cap_window(): int {
			return (int) apply_filters( 'cmplz_datarequest_email_cap_window', DAY_IN_SECONDS );
		}

		/**
		 * Build the 429 Too Many Requests response for a tripped rate limit.
		 * Body keeps the {message,success} shape the front-end reads; the 429 status
		 * and Retry-After header give correct semantics for caches/WAF/monitoring.
		 *
		 * @param int $retry_after Seconds after which the client may retry.
		 * @return WP_REST_Response
		 */
		private function rate_limited_response( int $retry_after ): WP_REST_Response {
			$response = new WP_REST_Response(
				array(
					'message' => __( 'Too many requests. Please try again later.', 'complianz-gdpr' ),
					'success' => false,
				),
				429
			);
			$response->header( 'Retry-After', (string) $retry_after );
			return $response;
		}

		/**
		 * Minimum interval between admin data-request notifications.
		 *
		 * @return int
		 */
		private function admin_notify_window(): int {
			return (int) apply_filters( 'cmplz_datarequest_admin_notify_window', 15 * MINUTE_IN_SECONDS );
		}

		/**
		 * Deferred (cron) mail dispatch for a stored data request. Reads the recipient
		 * from the DB row (no PII is carried in the cron args), sends the requester
		 * confirmation, and sends the admin notification at most once per window so a
		 * flood cannot turn the admin inbox into a denial-of-service target.
		 *
		 * @param int $row_id The wp_cmplz_dnsmpd row ID to notify about.
		 * @return void
		 */
		public function process_datarequest_mail( $row_id ): void {
			global $wpdb;
			$row = $wpdb->get_row( $wpdb->prepare( "SELECT name, email FROM {$wpdb->prefix}cmplz_dnsmpd WHERE ID = %d", (int) $row_id ) );
			if ( ! $row || ! is_email( $row->email ) ) {
				return;
			}

			$this->send_confirmation_mail( $row->email, $row->name );

			// Throttle admin notifications: at most one per window regardless of volume.
			if ( ! get_transient( 'cmplz_dr_admin_notified' ) ) {
				$this->send_notification_mail();
				set_transient( 'cmplz_dr_admin_notified', 1, $this->admin_notify_window() );
			}
		}

		/**
		 * Render the form in the shortcode
		 *
		 * @return false|string
		 */
		public function datarequest_form() {
			ob_start();
			?>
			<div class="cmplz-datarequest cmplz-alert">
				<span class="cmplz-close">&times;</span>
				<span id="cmplz-message"></span>
			</div>
			<form id="cmplz-datarequest-form">
				<input type="hidden" required value="us" name="cmplz_datarequest_region" id="cmplz_datarequest_region">

				<label for="cmplz_datarequest_firstname" class="cmplz-first-name"><?php echo esc_html__( 'Name', 'complianz-gdpr' ); ?><input type="search" class="dnsmpd-firstname" value="" placeholder="your first name" id="cmplz_datarequest_firstname" name="cmplz_datarequest_firstname"></label>
				<div>
					<label for="cmplz_datarequest_name"><?php esc_html_e( 'Name', 'complianz-gdpr' ); ?></label>
					<input type="text" required value="" placeholder="<?php echo esc_attr__( 'Your name', 'complianz-gdpr' ); ?>" id="cmplz_datarequest_name" name="cmplz_datarequest_name">
				</div>
				<div>
					<label for="cmplz_datarequest_email"><?php esc_html_e( 'Email', 'complianz-gdpr' ); ?></label>
					<input type="email" required value="" placeholder="email@email.com" id="cmplz_datarequest_email" name="cmplz_datarequest_email">
				</div>

				<?php
				$options = $this->datarequest_options();
				foreach ( $options as $id => $label ) {
					?>
					<div class="cmplz_datarequest cmplz_datarequest_<?php echo esc_attr( $id ); ?>">
						<label for="cmplz_datarequest_<?php echo esc_attr( $id ); ?>">
							<input type="checkbox" value="1" name="cmplz_datarequest_<?php echo esc_attr( $id ); ?>" id="cmplz_datarequest_<?php echo esc_attr( $id ); ?>"/>
							<?php echo esc_html( $label['long'] ); ?>
						</label>
					</div>
				<?php } ?>
				<input type="button" id="cmplz-datarequest-submit" name="cmplz-datarequest-submit" value="<?php esc_html_e( 'Send', 'complianz-gdpr' ); ?>">
			</form>

			<style>
				/* first-name is honeypot */
				.cmplz-first-name {
					position: absolute !important;
					left: -5000px !important;
				}
			</style>
			<?php
			return ob_get_clean();
		}
	} //class closure
}
