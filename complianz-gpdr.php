<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Plugin Name: Complianz | GDPR/CCPA Cookie Consent
 * Plugin URI: https://www.wordpress.org/plugins/complianz-gdpr
 * Description: Complianz Privacy Suite for GDPR, CaCPA, DSVGO, AVG with a conditional cookie warning and customized cookie policy
 * Version: 7.4.2
 * Requires at least: 5.9
 * Requires PHP: 7.4
 * Text Domain: complianz-gdpr
 * Domain Path: /languages
 * Author: Complianz
 * Author URI: https://www.complianz.io
 */

/*
	Copyright 2025 Complianz BV (email : support@complianz.io)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

defined( 'ABSPATH' ) || die( 'You do not have access to this page!' );

define( 'cmplz_free', true );

if ( ! function_exists( 'cmplz_activation_check' ) ) {
	/**
	 * Checks if the plugin can safely be activated, at least php 5.6 and wp 4.6
	 *
	 * @since 2.1.5
	 */
	function cmplz_activation_check() {
		if ( version_compare( PHP_VERSION, '7.2', '<' ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die(
				__( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					'Complianz GDPR cannot be activated. The plugin requires PHP 7.2 or higher',
					'complianz-gdpr'
				)
			);
		}

		global $wp_version;
		if ( version_compare( $wp_version, '4.9', '<' ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die(
				__( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					'Complianz GDPR cannot be activated. The plugin requires WordPress 4.9 or higher',
					'complianz-gdpr'
				)
			);
		}
	}

	register_activation_hook( __FILE__, 'cmplz_activation_check' );
}

require_once plugin_dir_path( __FILE__ ) . 'functions.php';

/**
 * Instantiate plugin
 */
if ( ! class_exists( 'COMPLIANZ' ) ) {

	/**
	 * Class COMPLIANZ
	 */
	class COMPLIANZ { // phpcs:ignore Universal.Files.SeparateFunctionsFromOO.Mixed, Generic.Classes.OpeningBraceSameLine.ContentAfterBrace

		/**
		 * Instance.
		 *
		 * @var COMPLIANZ
		 */
		public static $instance;

		/**
		 * Config instance.
		 *
		 * @var cmplz_config
		 */
		public static $config;

		/**
		 * Company instance.
		 *
		 * @var cmplz_company
		 */
		public static $company;

		/**
		 * Review instance.
		 *
		 * @var cmplz_review
		 */
		public static $review;

		/**
		 * Admin instance.
		 *
		 * @var cmplz_admin
		 */
		public static $admin;

		/**
		 * Scan instance.
		 *
		 * @var cmplz_scan
		 */
		public static $scan;

		/**
		 * Sync instance.
		 *
		 * @var cmplz_sync
		 */
		public static $sync;

		/**
		 * Wizard instance.
		 *
		 * @var cmplz_wizard
		 */
		public static $wizard;

		/**
		 * Export settings instance.
		 *
		 * @var cmplz_export_settings
		 */
		public static $export_settings;

		/**
		 * Upgrade to pro instance.
		 *
		 * @var cmplz_upgrade_to_pro
		 */
		public static $rsp_upgrade_to_pro;

		/**
		 * Banner loader instance.
		 *
		 * @var cmplz_banner_loader
		 */
		public static $banner_loader;

		/**
		 * Document instance.
		 *
		 * @var cmplz_document
		 */
		public static $document;

		/**
		 * Cookie blocker instance.
		 *
		 * @var cmplz_cookie_blocker
		 */
		public static $cookie_blocker;

		/**
		 * Progress instance.
		 *
		 * @var cmplz_progress
		 */
		public static $progress;

		/**
		 * DNSMPD instance.
		 *
		 * @var cmplz_DNSMPD
		 */
		public static $DNSMPD; // phpcs:ignore WordPress.NamingConventions.ValidVariableName

		/**
		 * Admin DNSMPD instance.
		 *
		 * @var cmplz_admin_DNSMPD
		 */
		public static $admin_DNSMPD; // phpcs:ignore WordPress.NamingConventions.ValidVariableName

		/**
		 * Proof of consent instance.
		 *
		 * @var cmplz_proof_of_consent
		 */
		public static $support;

		/**
		 * Proof of consent instance.
		 *
		 * @var cmplz_proof_of_consent
		 */
		public static $proof_of_consent;

		/**
		 * Documents admin instance.
		 *
		 * @var cmplz_documents_admin
		 */
		public static $documents_admin;

		/**
		 * Websitescan instance.
		 *
		 * @var cmplz_wsc
		 */
		public static $websitescan;

		/**
		 * Websitescan onboarding instance.
		 *
		 * @var cmplz_wsc_onboarding
		 */
		public static $wsc_onboarding;

		/**
		 * Websitescan API instance.
		 *
		 * @var cmplz_wsc_api
		 */
		public static $wsc_api;

		/**
		 * Websitescan scanner instance.
		 *
		 * @var cmplz_wsc_scanner
		 */
		public static $wsc_scanner;


		/**
		 * COMPLIANZ constructor.
		 */
		private function __construct() {
			$this->run();
		}


		/**
		 * Run the plugin.
		 *
		 * @return void
		 */
		public function run(): void {
			$this->define_constants();
			$this->includes();
			$this->instantiate_classes();
			$this->hooks();
		}


		/**
		 * Instantiate the class.
		 *
		 * @return COMPLIANZ
		 * @since 1.0.0
		 */
		public static function get_instance() {
			if ( ! isset( self::$instance )
				&& ! ( self::$instance instanceof COMPLIANZ )
			) {
				self::$instance = new self();
			}

			return self::$instance;
		}


		/**
		 * Define plugin constants.
		 *
		 * @return void
		 */
		private function define_constants() {
			define( 'CMPLZ_COOKIEDATABASE_URL', 'https://cookiedatabase.org/wp-json/cookiedatabase/' );
			define( 'CMPLZ_MAIN_MENU_POSITION', 40 );

			// default region code.
			if ( ! defined( 'CMPLZ_DEFAULT_REGION' ) ) {
				define( 'CMPLZ_DEFAULT_REGION', 'us' );
			}

			/*statistics*/
			if ( ! defined( 'CMPLZ_AB_TESTING_DURATION' ) ) {
				define( 'CMPLZ_AB_TESTING_DURATION', 30 );
			} //Days

			define( 'CMPLZ_URL', plugin_dir_url( __FILE__ ) );
			define( 'CMPLZ_PATH', plugin_dir_path( __FILE__ ) );
			define( 'CMPLZ_PLUGIN', plugin_basename( __FILE__ ) );
			// for auto upgrade functionality.
			define( 'CMPLZ_PLUGIN_FREE', plugin_basename( __FILE__ ) );
			$debug = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '#' . time() : '';
			define( 'CMPLZ_VERSION', '7.4.2' . $debug );
			define( 'CMPLZ_PLUGIN_FILE', __FILE__ );
		}


		/**
		 * Include required files.
		 *
		 * @return void
		 */
		private function includes() {
			require_once CMPLZ_PATH . 'documents/class-document.php';
			require_once CMPLZ_PATH . 'cookie/class-cookie.php';
			require_once CMPLZ_PATH . 'cookie/class-service.php';
			require_once CMPLZ_PATH . 'integrations/integrations.php';
			require_once CMPLZ_PATH . 'cron/cron.php';

			/* Gutenberg block */
			if ( cmplz_uses_gutenberg() ) {
				require_once plugin_dir_path( __FILE__ ) . 'gutenberg/block.php';
			}
			require_once plugin_dir_path( __FILE__ ) . 'rest-api/rest-api.php';

			if ( cmplz_admin_logged_in() ) {
				require_once CMPLZ_PATH . 'config/warnings.php';
				require_once CMPLZ_PATH . 'settings/settings.php';
				require_once CMPLZ_PATH . 'class-admin.php';
				require_once CMPLZ_PATH . 'class-review.php';
				require_once CMPLZ_PATH . 'progress/class-progress.php';
				require_once CMPLZ_PATH . 'cookiebanner/admin/cookiebanner.php';
				require_once CMPLZ_PATH . 'class-export.php';
				require_once CMPLZ_PATH . 'documents/admin-class-documents.php';
				require_once CMPLZ_PATH . 'settings/wizard.php';
				require_once CMPLZ_PATH . 'placeholders/class-placeholders.php';

				if ( isset( $_GET['install_pro'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					require_once CMPLZ_PATH . 'upgrade/upgrade-to-pro.php';
				}

				require_once CMPLZ_PATH . 'upgrade.php';
				require_once CMPLZ_PATH . 'DNSMPD/class-admin-DNSMPD.php';
				require_once CMPLZ_PATH . 'cookie/class-sync.php';
				/* Website Scan */
				require_once CMPLZ_PATH . 'websitescan/class-wsc.php';
			}

			if ( cmplz_admin_logged_in() || cmplz_scan_in_progress() ) {
				require_once CMPLZ_PATH . 'cookie/class-scan.php';
			}

			require_once CMPLZ_PATH . 'proof-of-consent/class-proof-of-consent.php';
			require_once CMPLZ_PATH . 'cookiebanner/class-cookiebanner.php';
			require_once CMPLZ_PATH . 'cookiebanner/class-banner-loader.php';

			require_once CMPLZ_PATH . 'class-company.php';
			require_once CMPLZ_PATH . 'DNSMPD/class-DNSMPD.php';
			require_once CMPLZ_PATH . 'config/class-config.php';
			require_once CMPLZ_PATH . 'class-cookie-blocker.php';
			require_once CMPLZ_PATH . 'websitescan/class-wsc-api.php';
			require_once CMPLZ_PATH . 'websitescan/class-wsc-scanner.php';
			require_once CMPLZ_PATH . 'mailer/class-mail.php';
		}


		/**
		 * Instantiate classes.
		 *
		 * @return void
		 */
		private function instantiate_classes(): void {
			self::$config  = new cmplz_config();
			self::$company = new cmplz_company();
			self::$DNSMPD  = new cmplz_DNSMPD(); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

			if ( cmplz_admin_logged_in() ) {
				self::$admin_DNSMPD    = new cmplz_admin_DNSMPD(); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				self::$review          = new cmplz_review();
				self::$admin           = new cmplz_admin();
				self::$export_settings = new cmplz_export_settings();
				self::$progress        = new cmplz_progress();
				self::$documents_admin = new cmplz_documents_admin();
				self::$wizard          = new cmplz_wizard();
				self::$sync            = new cmplz_sync();
				self::$websitescan     = new cmplz_wsc();
				self::$wsc_onboarding  = new cmplz_wsc_onboarding();
			}

			if ( cmplz_admin_logged_in() || cmplz_scan_in_progress() ) {
				self::$scan = new cmplz_scan();
			}

			self::$proof_of_consent = new cmplz_proof_of_consent();
			self::$cookie_blocker   = new cmplz_cookie_blocker();
			self::$banner_loader    = new cmplz_banner_loader();
			self::$document         = new cmplz_document();
			self::$wsc_api          = new cmplz_wsc_api();
			self::$wsc_scanner      = new cmplz_wsc_scanner();
		}


		/**
		 * Add hooks.
		 *
		 * @return void
		 */
		private function hooks() {
			add_action( 'init', array( $this, 'load_textdomain' ), 0 );

			if ( wp_doing_ajax() ) {
				// using init on ajax calls, as wp is not running.
				add_action( 'init', 'cmplz_init_cookie_blocker' );
			} else {
				// has to be wp for all non ajax calls, because of AMP plugin.
				add_action( 'wp', 'cmplz_init_cookie_blocker' );
			}
		}


		/**
		 * Load plugin translations.
		 *
		 * @since 7.4.0
		 *
		 * @return void
		 */
		public function load_textdomain(): void {
			load_plugin_textdomain( 'complianz-gdpr' );
		}
	}

	/**
	 * Load the plugins main class.
	 */
	add_action(
		'plugins_loaded',
		function () {
			COMPLIANZ::get_instance();
		},
		9
	);
}

if ( ! function_exists( 'cmplz_set_activation_time_stamp' ) ) {
	/**
	 * Set an activation time stamp.
	 *
	 * @param bool $networkwide Whether the plugin is being activated network-wide.
	 * @return void
	 */
	function cmplz_set_activation_time_stamp( $networkwide ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- We need this parameter for the activation hook
		update_option( 'cmplz_activation_time', time() );
		update_option( 'cmplz_run_activation', true, false );
		set_transient( 'cmplz_redirect_to_settings_page', true, HOUR_IN_SECONDS );
	}

	register_activation_hook( __FILE__, 'cmplz_set_activation_time_stamp' );
}

if ( ! function_exists( 'cmplz_activation_check' ) ) {
	/**
	 * Start the tour of the plugin on activation
	 */
	function cmplz_activation_check() {
		do_action( 'cmplz_activation' );
	}

	register_activation_hook( __FILE__, 'cmplz_activation_check' );
}

if ( ! function_exists( 'cmplz_is_logged_in_rest' ) ) {
	/**
	 * Check if a user is logged in for Complianz REST API requests.
	 *
	 * This function determines whether the current request is related to
	 * the Complianz REST API (`/complianz/v1/`) and, if so, checks if a user
	 * is logged in.
	 *
	 * @return bool True if the request is for the Complianz REST API and the user is logged in, false otherwise.
	 */
	function cmplz_is_logged_in_rest() {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$is_settings_page_request = isset( $_SERVER['REQUEST_URI'] ) && strpos( $_SERVER['REQUEST_URI'], '/complianz/v1/' ) !== false;
		if ( ! $is_settings_page_request ) {
			return false;
		}

		return is_user_logged_in();
	}
}

if ( ! function_exists( 'cmplz_admin_logged_in' ) ) {
	/**
	 * Check if an admin user is logged in.
	 */ function cmplz_admin_logged_in() {
		$wpcli = defined( 'WP_CLI' ) && WP_CLI;
		return ( is_admin() && cmplz_user_can_manage() ) || cmplz_is_logged_in_rest() || wp_doing_cron() || $wpcli || defined( 'CMPLZ_DOING_SYSTEM_STATUS' );
	}
}

if ( ! function_exists( 'cmplz_add_manage_privacy_capability' ) ) {
	/**
	 * Assign the 'manage_privacy' capability to administrators.
	 *
	 * This function ensures that the 'administrator' role has the 'manage_privacy' capability.
	 * If the site is part of a multisite network and `$handle_subsites` is true, the capability
	 * is also added to all subsites by recursively applying the function to each subsite.
	 *
	 * @param bool $handle_subsites Whether to apply the capability to all subsites in a multisite network.
	 *                              Default is true.
	 *
	 * @return void
	 */
	function cmplz_add_manage_privacy_capability( $handle_subsites = true ) {
		$capability = 'manage_privacy';
		$role       = get_role( 'administrator' );
		if ( $role && ! $role->has_cap( $capability ) ) {
			$role->add_cap( $capability );
		}

		// we need to add this role across subsites as well.
		if ( $handle_subsites && is_multisite() ) {
			$sites = get_sites();
			if ( count( $sites ) > 0 ) {
				foreach ( $sites as $site ) {
					switch_to_blog( $site->blog_id );
					cmplz_add_manage_privacy_capability( false );
					restore_current_blog();
				}
			}
		}
	}
	register_activation_hook( __FILE__, 'cmplz_add_manage_privacy_capability' );

	/**
	 * Assign Complianz management capabilities to a new subsite in a multisite network.
	 *
	 * @param WP_Site $site The newly created WordPress site object.
	 *
	 * @return void
	 */
	function cmplz_add_role_to_subsite( $site ) {
		switch_to_blog( $site->blog_id );
		cmplz_add_manage_privacy_capability( false );
		restore_current_blog();
	}
	add_action( 'wp_initialize_site', 'cmplz_add_role_to_subsite', 10, 1 );
}
