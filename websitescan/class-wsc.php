<?php

/**
 * Website Scan Integration
 */

defined('ABSPATH') or die();

if (!class_exists("cmplz_wsc")) {

	class cmplz_wsc
	{
		private static $_this;

		protected $onboarding;
		protected $notices;
		protected $settings;
		protected $auth;
		protected $logger;

		// any changes on this constants should be reflected on the react application
		const WSC_EMAIL_OPTION_KEY = 'cmplz_wsc_email';
		const WSC_CLIENT_ID_OPTION_KEY = 'cmplz_wsc_client_id';
		const WSC_CLIENT_SECRET_OPTION_KEY = 'cmplz_wsc_client_secret';
		const WSC_SITE_ID_OPTION_KEY = 'cmplz_wsc_site_id';
		const WSC_OPT_ONBOARDING_DATE = 'cmplz_wsc_onboarding_start';
		const WSC_ONBOARDING_STAGED_END = 'april 30 2025';


		/**
		 * Class constructor for the WSC class.
		 *
		 * Initializes the WSC class and its dependencies, and runs the class.
		 *
		 */
		public function __construct()
		{
			if (isset(self::$_this)) {
				wp_die(sprintf('%s is a singleton class and you cannot create a second instance.', get_class($this)));
			}

			self::$_this = $this;

			$this->load_dependencies();
			$this->initialize_classes();
			$this->run();
		}


		/**
		 * Retrieve the instance of the class.
		 *
		 * @return object The instance of the class.
		 */
		public static function this()
		{
			return self::$_this;
		}

		/**
		 * Load the dependencies for the WSC (Website Scan) class.
		 *
		 * This method is responsible for including the necessary files and classes
		 * required for the proper functioning of the WSC class.
		 *
		 * @access private
		 * @return void
		 */

		private function load_dependencies()
		{
			require_once plugin_dir_path(__FILE__) . 'class-wsc-onboarding.php';
			require_once plugin_dir_path(__FILE__) . 'class-wsc-notices.php';
			require_once plugin_dir_path(__FILE__) . 'class-wsc-settings.php';
			require_once plugin_dir_path(__FILE__) . 'class-wsc-auth.php';
			require_once plugin_dir_path(__FILE__) . 'class-wsc-logger.php';
		}


		/**
		 * Initializes the classes required for the website scan functionality.
		 *
		 * This method creates instances of the following classes:
		 * - cmplz_wsc_onboarding: Handles the onboarding process for the website scan.
		 * - cmplz_wsc_notices: Manages the notices related to the website scan.
		 * - cmplz_wsc_settings: Handles the settings for the website scan.
		 * - cmplz_wsc_auth: Manages the authentication process for the website scan.
		 *
		 * @access private
		 * @return void
		 */
		private function initialize_classes()
		{
			$this->onboarding = new cmplz_wsc_onboarding();
			$this->notices = new cmplz_wsc_notices();
			$this->settings = new cmplz_wsc_settings();
			$this->auth = new cmplz_wsc_auth();
			$this->logger = new cmplz_wsc_logger();
		}


		/**
		 * Runs the necessary initialization hooks for the website scan.
		 *
		 * This method initializes the hooks for the onboarding, notices, settings, and authentication components of the website scan.
		 * It ensures that the necessary actions and filters are set up for these components to function properly.
		 */
		private function run()
		{
			$this->onboarding->init_hooks();
			$this->notices->init_hooks();
			$this->settings->init_hooks();
			$this->auth->init_hooks();
			$this->logger->init_hooks();
		}
	}
}
