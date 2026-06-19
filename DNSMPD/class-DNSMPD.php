<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

if ( ! class_exists( "cmplz_DNSMPD" ) ) {
	class cmplz_DNSMPD {
		private static $_this;

		function __construct() {
			if ( isset( self::$_this ) ) {
				wp_die( sprintf( '%s is a singleton class and you cannot create a second instance.',
					get_class( $this ) ) );
			}

			self::$_this = $this;

			if ( cmplz_has_region('us') ) {
				add_shortcode( 'cmplz-dnsmpi-request', array($this, 'datarequest_form') );
			}

			add_action( 'rest_api_init', array($this, 'register_rest_route') );
			add_filter( 'cmplz_datarequest_options', array( $this, 'datarequest_options' ), 20 );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		}

		static function this() {
			return self::$_this;
		}

		/**
		 * Enqueue front-end assets
		 * @param $hook
		 */

		public function enqueue_assets( $hook ) {

			global $post;
			if ( $post && isset($post->ID) && !COMPLIANZ::$document->is_complianz_page($post->ID ) ) {
				return;
			}

			if ( !cmplz_has_region('us') && !cmplz_datarequests_active() ) {
				return;
			}
			$v = filemtime(CMPLZ_PATH . "DNSMPD/script.min.js");
			wp_enqueue_script( 'cmplz-dnsmpd', CMPLZ_URL . "DNSMPD/script.min.js", array(), $v, true );
			wp_localize_script(
					'cmplz-dnsmpd',
					'cmplz_datarequests',
					array(
							'url' => get_rest_url(null, 'complianz/v1/datarequests'),
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

		public function datarequest_options( array $options = [] ): array {
			$options += [
				"global_optout"   => [
					'slug' => 'definition/what-is-global-opt-out',
					'short' => __( 'Global opt-out', 'complianz-gdpr' ),
					'long' => __( 'Global opt-out from selling and sharing my personal information and limiting the use or disclosure of sensitive personal information.', 'complianz-gdpr' ),
				],
				"cross_context"   => [
					'slug' => 'definition/what-is-cross-context-behavioral-advertising/',
					'short'  => __( 'Do not sell my info', 'complianz-gdpr' ),
					'long' => __( 'Do not sell my personal information for cross-context behavioral advertising', 'complianz-gdpr' ),
				],
				"limit_sensitive" => [
					'slug' => 'definition/what-is-limit-sensitive-data/',
					'short' => __( 'Limit sensitive data', 'complianz-gdpr' ),
					'long' => __( 'Limit the use of my sensitive personal information', 'complianz-gdpr' ),
				],
				"request_for_access" => [
					'slug' => 'definition/what-is-the-right-to-access/',
					'short' => __( 'Request for access', 'complianz-gdpr' ),
					'long' => __( 'Request for access', 'complianz-gdpr' ),
				],
				"right_to_be_forgotten" => [
					'slug' => 'definition/right-to-be-forgotten/',
					'short' => __( 'Right to be Forgotten', 'complianz-gdpr' ),
					'long' => __( 'Right to be Forgotten', 'complianz-gdpr' ),
				],
				"right_to_data_portability" => [
					'slug' => 'definition/right-to-data-portability/',
					'short' => __( 'Right to Data Portability', 'complianz-gdpr' ),
					'long' => __( 'Right to Data Portability', 'complianz-gdpr' ),
				],

			];

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

		private function send_notification_mail(  ) {
			$email     = sanitize_email( apply_filters('cmplz_datarequest_email',get_option( 'admin_email' )) );
			$subject = cmplz_sprintf(__("You have received a new data request on %s", "complianz-gdpr") , get_bloginfo( 'name' ) );
			$message = $subject.'<br />'.cmplz_sprintf(__("Please check the data request on %s", "complianz-gdpr"), '<a href="'.site_url().'" target="_blank">'.site_url().'</a>');
			$this->send_mail( $email, $subject, $message );
		}

		/**
		 * Send an email
		 * @param string $email
		 * @param string $subject
		 * @param string $message
		 *
		 * @return bool
		 */
		private function send_mail( $email, $subject, $message ) {
			$headers = [];
			$from_name  = get_bloginfo( 'name' );
			$from_email = cmplz_get_option( 'notification_from_email' );
			add_filter( 'wp_mail_content_type', function ( $content_type ) {
				return 'text/html';
			} );

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
		public function register_rest_route()
		{
			register_rest_route('complianz/v1', 'datarequests/', array(
				'methods' => 'POST',
				'callback' => array($this, 'process_restapi_datarequest'),
				'args' => array(),
				'permission_callback' => '__return_true',
			));
		}

		/**
		 * Process the form submit
		 *
		 * @param WP_REST_Request $request
		 *
		 * @return array
		 */
		public function process_restapi_datarequest( WP_REST_Request $request ) {

			$params = $request->get_json_params();
			$new_request = false;
			$error = false;
			$message = "";

			//check honeypot
			if ( isset($params['cmplz_datarequest_firstname']) && ! empty( $params['cmplz_datarequest_firstname'] ) ) {
				$error   = true;
				$message = __( "Sorry, it looks like you're a bot", 'complianz-gdpr' );
			}

			if ( ! isset($params['cmplz_datarequest_email']) || ! is_email( $params['cmplz_datarequest_email'] ) ) {
				$error   = true;
				$message = __( "Please enter a valid email address.", 'complianz-gdpr' );
			}

			if ( ! isset($params['cmplz_datarequest_name']) || empty( $params['cmplz_datarequest_name'] ) ) {
				$error   = true;
				$message = __( "Please enter your name", 'complianz-gdpr' );
			}

			if ( strlen( $params['cmplz_datarequest_name'] ) > 100 ) {
				$error = true;
				$message = __( "That's a long name you got there. Please try to shorten the name.", 'complianz-gdpr' );
			}

			if ( ! isset($params['cmplz_datarequest_region']) || empty( $params['cmplz_datarequest_region'] ) ) {
				$region = 'us';
			}

			if ( ! $error ) {
				$email = sanitize_email( $params['cmplz_datarequest_email'] );
				$name  = sanitize_text_field( $params['cmplz_datarequest_name'] );
				$region  = sanitize_title( $params['cmplz_datarequest_region'] );
				//check if this email address is already registered:
				global $wpdb;
				$options = apply_filters( 'cmplz_datarequest_options', [] );
				foreach ( $options as $fieldname => $label ) {
					$value = isset( $params['cmplz_datarequest_'.$fieldname] ) ? intval( $params['cmplz_datarequest_'.$fieldname] ) : false;
					if ( $value === 1 ) {
						$count = $wpdb->get_var( $wpdb->prepare( "SELECT count(*) from {$wpdb->prefix}cmplz_dnsmpd WHERE email = %s and $fieldname=1", $email ) );
						if ( $count == 0 ) {
							$new_request = true;
							$wpdb->insert( $wpdb->prefix . 'cmplz_dnsmpd',
									array(
											'name'         => $name,
											'email'        => $email,
											'region'        => $region,
											$fieldname     => $value,
											'request_date' => time()
									)
							);
						}
					}
				}

				if ( $new_request ) {
					$this->send_confirmation_mail( $email, $name );
					$this->send_notification_mail();
					$message = __( "Your request has been processed successfully!", 'complianz-gdpr' );
				} else {
					$message = __( "Your request could not be processed. A request is already in progress for this email address or the form is not complete.", 'complianz-gdpr' );
					$error = true;
				}
			}

			return array(
					'message' => $message,
					'success' => ! $error,
			);
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

				<label for="cmplz_datarequest_firstname" class="cmplz-first-name"><?php echo esc_html__( 'Name', 'complianz-gdpr' )?><input type="search" class="dnsmpd-firstname" value="" placeholder="your first name" id="cmplz_datarequest_firstname" name="cmplz_datarequest_firstname"></label>
				<div>
					<label for="cmplz_datarequest_name"><?php esc_html_e( 'Name', 'complianz-gdpr' )?></label>
					<input type="text" required value="" placeholder="<?php echo esc_attr__( 'Your name', 'complianz-gdpr' )?>" id="cmplz_datarequest_name" name="cmplz_datarequest_name">
				</div>
				<div>
					<label for="cmplz_datarequest_email"><?php esc_html_e( 'Email', 'complianz-gdpr' )?></label>
					<input type="email" required value="" placeholder="email@email.com" id="cmplz_datarequest_email" name="cmplz_datarequest_email">
				</div>

				<?php
				$options = $this->datarequest_options();
				foreach ( $options as $id => $label ) { ?>
					<div class="cmplz_datarequest cmplz_datarequest_<?php echo esc_attr($id)?>">
						<label for="cmplz_datarequest_<?php echo esc_attr($id)?>">
							<input type="checkbox" value="1" name="cmplz_datarequest_<?php echo esc_attr($id)?>" id="cmplz_datarequest_<?php echo esc_attr($id)?>"/>
							<?php echo esc_html($label['long'])?>
						</label>
					</div>
				<?php } ?>
				<input type="button" id="cmplz-datarequest-submit" name="cmplz-datarequest-submit" value="<?php esc_html_e( 'Send', 'complianz-gdpr' )?>">
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
