<?php
defined('ABSPATH') or die();

/**
 * Enqueue Gutenberg block assets for backend editor.
 *
 * @since 1.0.0
 */
require_once( CMPLZ_PATH . 'settings/config/menu.php' );
require_once( CMPLZ_PATH . 'settings/config/blocks.php' );
require_once( CMPLZ_PATH . 'settings/wizard.php' );
require_once( CMPLZ_PATH . 'settings/config/fields-notices.php' );
require_once( CMPLZ_PATH . 'settings/media/wp_enqueue_media_override.php');
/**
 * Fix for WPML issue where WPML breaks the rest api by adding a language locale in the url
 *
 * @param $url
 * @param $path
 * @param $blog_id
 * @param $scheme
 *
 * @return string
 */
function cmplz_fix_rest_url_for_wpml( $url, $path, $blog_id, $scheme)  {
	if ( strpos($url, 'complianz/v')===false ) {
		return $url;
	}

	$current_language = false;
	if ( function_exists( 'icl_register_string' ) ) {
		$current_language = apply_filters( 'wpml_current_language', null );
	}

	if ( function_exists('qtranxf_getLanguage') ){
		$current_language = qtranxf_getLanguage();
	}

	if ( $current_language ) {
		if ( strpos($url, '/'.$current_language.'/wp-json/') ) {
			$url = str_replace( '/'.$current_language.'/wp-json/', '/wp-json/', $url);
		}
	}
	return $url;
}
add_filter( 'rest_url', 'cmplz_fix_rest_url_for_wpml', 10, 4 );

/**
 * @return void
* as we can't append #dashboard to the first menu item, we leave it at 'complianz', and replace the text. This is a bit hacky, but it works.
 */

function cmplz_fix_duplicate_menu_item() {
	?>
	<script>
		window.addEventListener("load", () => {
			let cmplzMain = document.querySelector('li.wp-has-submenu.toplevel_page_complianz a.wp-first-item');
			if (cmplzMain) {
				cmplzMain.innerHTML = cmplzMain.innerHTML.replace('Complianz', '<?php esc_html_e(__( 'Dashboard', 'complianz-gdpr'))?>');
			}
		});
	</script>

	<?php
	/**
	 * Ensure the items are selected in sync with the complianz react menu.
	 */
	if(isset($_GET['page']) && $_GET['page']==='complianz') {
		?>
			<script>
				const cmplzSetActive = (obj) => {
					obj.classList.add('current');
					obj.parentNode.classList.add('current');
				}

				window.addEventListener("load", () => {
					let cmplzMain = document.querySelector('li.wp-has-submenu.toplevel_page_complianz a.wp-first-item');
					if (cmplzMain) {
						cmplzMain.href = '#';
					}
				});
				//get the hash from the current url
				let cmplzHash = window.location.hash;
				//strip off anything after a /
				if ( cmplzHash.indexOf('/') !== -1 ) {
					cmplzHash = cmplzHash.substring(0, cmplzHash.indexOf('/'));
				}
				if ( !cmplzHash ) {
					let cmplzMain = document.querySelector('li.wp-has-submenu.toplevel_page_complianz a.wp-first-item');
					cmplzSetActive(cmplzMain);
				} else {
					let cmplzMenuItems = document.querySelector('li.wp-has-submenu.toplevel_page_complianz').querySelectorAll('a');
					for (const link of cmplzMenuItems) {
						if (cmplzHash && link.href.indexOf(cmplzHash) !== -1) {
							cmplzSetActive(link);
						} else {
							link.classList.remove('current');
							link.parentNode.classList.remove('current');
						}
					}
				}

				window.addEventListener('click', (e) => {
					const cmplzTargetHref = e.target && e.target.href;
					let cmplzIsMainMenu = false;
					let cmplzIsWpMenu = false;
					if (cmplzTargetHref && e.target.classList.contains('cmplz-main')) {
						cmplzIsMainMenu = true;
					} else if (cmplzTargetHref && cmplzTargetHref.indexOf('admin.php')!==-1) {
						cmplzIsWpMenu = true;
					}
					if (!cmplzIsWpMenu && !cmplzIsMainMenu) {
						return;
					}
					if (cmplzIsWpMenu) {
						if (cmplzTargetHref && cmplzTargetHref.indexOf('page=complianz') !== -1) {
							const parentElement = e.target.parentNode.parentNode;
							const childLinks = parentElement.querySelectorAll('li, a');
							// Loop through each 'a' element and add the class
							for (const link of childLinks) {
								link.classList.remove('current');
							}
							e.target.classList.add('current');
							e.target.parentNode.classList.add('current');
						}
					} else {
						//find cmplzTargetHref in wordpress menu
						let cmplzMenuItems = document.querySelector('li.wp-has-submenu.toplevel_page_complianz').querySelectorAll('a');
						for (const link of cmplzMenuItems) {
							//check if last character of link.href is '#'
							if (cmplzTargetHref.indexOf('dashboard')!==-1 && link.href.charAt(link.href.length - 1) === '#'){
								cmplzSetActive(link);
							} else if (cmplzTargetHref && link.href.indexOf(cmplzTargetHref) !== -1) {
								cmplzSetActive(link);
							} else {
								link.classList.remove('current');
								link.parentNode.classList.remove('current');
							}
						}
					}
				});
			</script>
		<?php
	}
}
add_action('admin_footer', 'cmplz_fix_duplicate_menu_item');
/**
 * WordPress doesn't allow for translation of chunks resulting of code splitting.
 * Several workarounds have popped up in JetPack and Woocommerce: https://developer.wordpress.com/2022/01/06/wordpress-plugin-i18n-webpack-and-composer/
 * Below is mainly based on the Woocommerce solution, which seems to be the most simple approach. Simplicity is king here.
 *
 * @return array
 */
function cmplz_get_chunk_translations() {
	//get all files from the settings/build folder
	$files = scandir(CMPLZ_PATH . 'settings/build');
	$json_translations = [];
	foreach ($files as $file) {
		if (strpos($file, '.js') === false) {
			continue;
		}

		$chunk_handle = str_replace('.js', '', $file );
		//temporarily register the script, so we can get a translations object.
		wp_register_script( $chunk_handle, plugins_url('build/'.$file, __FILE__), [], true );
		$path = defined('cmplz_premium') ? CMPLZ_PATH . 'languages' : false;
		$localeData = load_script_textdomain( $chunk_handle, 'complianz-gdpr', $path );
		if ( !empty($localeData) ){
			$json_translations[] = $localeData;
		}
		wp_deregister_script( $chunk_handle );
	}
	return $json_translations;
}

function cmplz_plugin_admin_scripts() {

	// replace with the actual path to your build directory
	$buildDirPath = plugin_dir_path( __FILE__ ) . '/build';

	// get the filenames in the build directory
	$filenames = scandir( $buildDirPath );

	// filter the filenames to get the JavaScript and asset filenames
	$jsFilename    = '';
	$assetFilename = '';
	foreach ( $filenames as $filename ) {
		if ( strpos( $filename, 'index.' ) === 0 ) {
			if ( substr( $filename, - 3 ) === '.js' ) {
				$jsFilename = $filename;
			} elseif ( substr( $filename, - 10 ) === '.asset.php' ) {
				$assetFilename = $filename;
			}
		}
	}

	/**
	 * Register polyfill for React JSX runtime for version lower than 6.6.
	 *
	 * @TODO: Remove this once the minimum required version of WordPress is 6.6.
	 */
	global $wp_version;

	if ( version_compare( $wp_version, '6.6', '<' ) && ! wp_script_is( 'react-jsx-runtime', 'registered' ) ) {
		wp_enqueue_script(
			'react-jsx-runtime',
			plugins_url( 'assets/js/react-jsx-runtime.js', __FILE__ ),
			[ 'react'], // it is a polyfill for react JSX runtime, it depends on react
            CMPLZ_VERSION,
		);
	}

	// check if the necessary files are found
	if ( $jsFilename !== '' && $assetFilename !== '' ) {
		$assetFilePath     = $buildDirPath . '/' . $assetFilename;
		$assetFile         = require( $assetFilePath );
		$handle            = 'cmplz-settings';
		cmplz_wp_enqueue_media();
		wp_enqueue_script( $handle);
		wp_enqueue_script(
				$handle,
				plugins_url( 'build/' . $jsFilename, __FILE__ ),
				$assetFile['dependencies'],
				$assetFile['version'],
				true
		);
		wp_set_script_translations( $handle, 'complianz-gdpr' );

		wp_localize_script(
				'cmplz-settings',
				'cmplz_settings',
				apply_filters( 'cmplz_localize_script', [
						'json_translations' => cmplz_get_chunk_translations(),
						'site_url'          => get_rest_url(),
						'admin_url'         => admin_url(),
						'admin_ajax_url'    => add_query_arg(
								array(
										'type'   => 'errors',
										'action' => 'cmplz_rest_api_fallback'
								),
								admin_url( 'admin-ajax.php' ) ),
						'dashboard_url'     => cmplz_admin_url(),
						'upgrade_link'      => 'https://complianz.io/pricing',
						'plugin_url'        => CMPLZ_URL,
						'license_url'      =>  is_multisite() ? cmplz_main_site_url('#settings/license') : '#settings/license',
						'blocks'            => cmplz_blocks(),
						'is_premium'        => defined( 'cmplz_premium' ),
						'nonce'             => wp_create_nonce( 'wp_rest' ),//to authenticate the logged in user
						'cmplz_nonce'       => wp_create_nonce( 'cmplz_react' ),
						'menu'              => cmplz_menu(),
						'regions'           => COMPLIANZ::$config->regions,
						'user_id'           => get_current_user_id(),
                        'is_multisite'      => is_multisite(),
                        'is_multisite_plugin'=> defined('cmplz_premium_multisite'),
						'onboarding_complete' => COMPLIANZ::$wsc_onboarding->wsc_is_dismissed(),
				] )
		);
	}
}

/**
 * Get admin url, adjusted for multisite
 * @return string|null
 */
function cmplz_admin_url($path=''){
	if (is_network_admin()) {
		switch_to_blog(get_main_site_id());
	}
	$url = add_query_arg(array('page' => 'complianz'), admin_url('admin.php') );
	if (is_network_admin()) {
		restore_current_blog();
	}
	return $url.$path;
}

/**
 * Get admin url, adjusted for multisite
 * @return string|null
 */
function cmplz_main_site_url($path=''){
	$switch_back = false;
	if ( !is_main_site()) {
		$switch_back = true;
		switch_to_blog(get_main_site_id());
	}
	$url = add_query_arg(array('page' => 'complianz'), admin_url('admin.php') );
	if ($switch_back) {
		restore_current_blog();
	}
	return $url.$path;
}

/**
 * Add SSL menu
 *
 * @return void
 */
function cmplz_add_option_menu() {
	if ( !cmplz_user_can_manage() ) {
        return;
	}

	$warnings = COMPLIANZ::$admin->get_warnings( array(
			'plus_ones' => true,
	) );
	$warning_count = count( $warnings );
	$warning_title = esc_attr( cmplz_sprintf( '%s plugin warnings', $warning_count ) );
	$plus_one = " <span class='update-plugins count-$warning_count' title='$warning_title'><span class='update-count'>" . number_format_i18n( $warning_count ) . "</span></span>";
	$page_hook_suffix = add_menu_page(
			'Complianz',
			'Complianz'.$plus_one,
			apply_filters('cmplz_capability','manage_privacy'),
			'complianz',
			'cmplz_settings_page',
			CMPLZ_URL . 'assets/images/menu-icon.svg',
			CMPLZ_MAIN_MENU_POSITION
	);

	add_submenu_page(
			'complianz#dashboard',
			__( 'Dashboard', 'complianz-gdpr' ).$plus_one,
			__( 'Dashboard', 'complianz-gdpr' ),
			apply_filters('cmplz_capability','manage_privacy'),
			'complianz',
			'cmplz_settings_page'
	);

	add_submenu_page(
			'complianz',
			__( 'Wizard', 'complianz-gdpr' ),
			__( 'Wizard', 'complianz-gdpr' ),
			apply_filters('cmplz_capability','manage_privacy'),
			'complianz#wizard',
			'cmplz_settings_page'
	);
	do_action( 'cmplz_cookiebanner_menu' );
	add_submenu_page(
			'complianz',
			__( 'Integrations', 'complianz-gdpr' ),
			__( 'Integrations', 'complianz-gdpr' ),
			apply_filters('cmplz_capability','manage_privacy'),
			'complianz#integrations',
			'cmplz_settings_page'
	);
	add_submenu_page(
			'complianz',
			__( 'Settings', 'complianz-gdpr' ),
			__( 'Settings', 'complianz-gdpr' ),
			apply_filters('cmplz_capability','manage_privacy'),
			'complianz#settings',
			'cmplz_settings_page'
	);
	add_submenu_page(
			'complianz',
			__( 'Tools', 'complianz-gdpr' ),
			__( 'Tools', 'complianz-gdpr' ),
			apply_filters('cmplz_capability','manage_privacy'),
			'complianz#tools',
			'cmplz_settings_page'
	);


	do_action( 'cmplz_admin_menu' );
	if ( defined( 'cmplz_free' ) && cmplz_free ) {
		global $submenu;
		if (isset($submenu['complianz'])) {
			$class                  = 'cmplz-submenu';
			$highest_index = count($submenu['complianz']);
			$submenu['complianz'][] = array(
					__( 'Upgrade to premium', 'complianz-gdpr' ),
					apply_filters('cmplz_capability','manage_privacy'),
					'https://complianz.io/l/pricing'
			);
			if ( isset( $submenu['complianz'][$highest_index] ) ) {
				if (! isset ($submenu['complianz'][$highest_index][4])) $submenu['complianz'][$highest_index][4] = '';
				$submenu['complianz'][$highest_index][4] .= ' ' . $class;
			}
		}
	}

	add_action( "admin_print_scripts-{$page_hook_suffix}", 'cmplz_plugin_admin_scripts' );
}
add_action( 'admin_menu', 'cmplz_add_option_menu' );


/**
 * Render the settings page
 */

 function cmplz_settings_page()
{
	if (!cmplz_user_can_manage()) {
        return;
	}
	?>
	<div id="complianz" class="cmplz"></div>
	<div id="complianz-modal"></div>
	<?php
}

/**
 * If the rest api is blocked, the code will try an admin ajax call as fall back.
 *
 * @return void
 */
function cmplz_rest_api_fallback(){
	$response = $data = [];
	$error = $action = $do_action =false;
	if ( ! cmplz_user_can_manage() ) {
		$error = true;
	}

	//if the site is using this fallback, we want to show a notice
	update_option('cmplz_ajax_fallback_active', time(), false );
	$requestData = json_decode(file_get_contents('php://input'), true);
	if (empty($requestData)) {
		$requestData = $_GET;
	}

	if ( $requestData ) {
		$action = $requestData['rest_action'] ?? false;
		$action = sanitize_text_field( $action );
		$data = $requestData['data'] ?? false;
		if ( strpos($action, 'complianz/v1/do_action/')!==false ){
			$do_action = strtolower(str_replace('complianz/v1/do_action/', '',$action ));
		}
	}
	if (!$error) {
		if ( strpos($action, 'fields/get')!==false) {
			$response =  cmplz_rest_api_fields_get();
		} else if (strpos($action, 'fields/set')!==false) {
			$request = new WP_REST_Request();
			$response =  cmplz_rest_api_fields_set($request);
		} else if ($do_action)  {
			$request = new WP_REST_Request();
			$request->set_param('action', $do_action);
			$request->set_param('data', $data);
			$response = cmplz_do_action($request );
		}
	}
	if ( ob_get_length() ) {
		ob_clean();
	}
	header( "Content-Type: application/json" );
	echo json_encode($response);
	exit;
}
add_action( 'wp_ajax_cmplz_rest_api_fallback', 'cmplz_rest_api_fallback' );

add_action( 'rest_api_init', 'cmplz_settings_rest_route', 10 );
function cmplz_settings_rest_route() {
	if (!cmplz_user_can_manage()) {
		return;
	}

	register_rest_route( 'complianz/v1', 'fields/get', array(
		'methods'  => 'GET',
		'callback' => 'cmplz_rest_api_fields_get',
		'permission_callback' => function () {
			return cmplz_user_can_manage();
		}
	) );

	register_rest_route( 'complianz/v1', 'fields/set', array(
		'methods'  => 'POST',
		'callback' => 'cmplz_rest_api_fields_set',
		'permission_callback' => function () {
			return cmplz_user_can_manage();
		}
	) );

	register_rest_route( 'complianz/v1', 'do_action/(?P<action>[a-z\_\-]+)', array(
		'methods'  => 'POST',
		'callback' => 'cmplz_do_action',
		'permission_callback' => function () {
			return cmplz_user_can_manage();
		}
	) );
}


/**
 * @param WP_REST_Request $request
 *
 * @return array
 */
function cmplz_do_action($request){
	if ( !cmplz_user_can_manage() ) {
		return [];
	}

	$data = $request->get_param('data');
	$nonce = $request->get_param('nonce');
	if ( empty($nonce) && isset($data['nonce'])) {
		$nonce = $data['nonce'];
	}

	if ( ! wp_verify_nonce( $nonce, 'cmplz_react' ) ) {
		return [];
	}
	$action = sanitize_title($request->get_param('action'));
	switch($action){
		case 'get_pages_list':
			$data = COMPLIANZ::$document->get_pages_list($request);
			break;
		case 'update_custom_legal_document_id':
			$data = COMPLIANZ::$documents_admin->update_custom_legal_document_id($request);
			break;
		case 'plugin_actions':
			$data = cmplz_plugin_actions($request);
			break;
		case 'otherpluginsdata':
			$data = cmplz_other_plugins_data();
			break;
		case 'get_cookiebanner_required':
			$is_required = COMPLIANZ::$banner_loader->site_needs_cookie_warning();
			$data = ['required' => $is_required];
			break;
		case 'reset_settings':
			$data = cmplz_reset_settings();
			break;
		default:
			$data = apply_filters("cmplz_do_action", [], $action, $request);
	}
	$data['request_success'] = true;
	if ( ob_get_length() ) {
		ob_clean();
	}
	return $data;
}


/**
 * process the reset
 * @return array
 */

function cmplz_reset_settings() {
	if ( ! cmplz_user_can_manage() ) {
		return [];
	}

	COMPLIANZ::$scan->reset_pages_list(false, true);
	$options = array(
			'cmplz_first_sync_started',
			'cmplz_post_scribe_required',
			'cmplz_activation_time',
			'cmplz_review_notice_shown',
			"cmplz_wizard_completed_once",
			'cmplz_options',
			'complianz_active_policy_id',
			'complianz_scan_token',
			'cmplz_license_notice_dismissed',
			'cmplz_license_status',
			'cmplz_changed_cookies',
			'cmplz_plugins_changed',
			'cmplz_detected_stats',
			'cmplz_deleted_cookies',
			'cmplz_reported_cookies',
			'cmplz_sync_cookies_complete',
			'cmplz_sync_cookies_after_services_complete',
			'cmplz_sync_services_complete',
			'cmplz_detected_social_media',
			'cmplz_detected_thirdparty_services',
			//'cmplz_vendorlist_downloaded_once',
	);

	foreach ( $options as $option_name ) {
		delete_option( $option_name );
		delete_site_option( $option_name );
	}

	global $wpdb;
	$table_names = array(
			$wpdb->prefix . 'cmplz_statistics',
			$wpdb->prefix . 'cmplz_cookies',
			$wpdb->prefix . 'cmplz_services'
	);

	foreach ( $table_names as $table_name ) {
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name ) {
			$wpdb->query( "TRUNCATE TABLE $table_name" );
		}
	}

	$banners = cmplz_get_cookiebanners( array( 'status' => 'all' ) );
	foreach ( $banners as $banner ) {
		$banner = cmplz_get_cookiebanner( $banner->ID );
		$banner->delete( true );
	}
	//ensure the activation will run again
	update_option( 'cmplz_run_activation', true, false );


	return [ 'success'=> true, 'id'=>'reset_settings','message' => __( 'Data successfully cleared', 'complianz-gdpr' ) ];
}

/**
 * Process plugin installation or activation actions
 *
 * @param WP_REST_Request $request
 *
 * @return array
 */

function cmplz_plugin_actions($request){
	if ( !cmplz_user_can_manage() ) {
		return [];
	}
	$slug = $request->get_param('slug');
	$action = $request->get_param('pluginAction');
	if ( $action==='download' || $action==='activate' ) {
		require_once(CMPLZ_PATH . 'class-installer.php');
		$installer = new cmplz_installer($slug);
	}

	if ( $action==='download' ) {
	    $installer->download_plugin();
    } else if ( $action === 'activate' ) {
        $installer->activate_plugin();
    }
    return cmplz_other_plugins_data($slug);
}

/**
 * Get plugin data for other plugin section
 * @param string $slug
 * @return array
 */
function cmplz_other_plugins_data($slug=false){
	if ( !cmplz_user_can_manage() ) {
		return [];
	}
	$plugins = array(
		[
			'slug' => 'complianz-terms-conditions',
			'constant_free' => 'cmplz_tc_version',
			'create' => admin_url('admin.php?page=terms-conditions'),
			'wordpress_url' => 'https://wordpress.org/plugins/complianz-terms-conditions/',
			'title' => 'Complianz - '. __("Terms & Conditions", "complianz-gdpr"),
			'summary' => __("Configure your own Terms and Conditions.", 'complianz-gdpr'),
			'description' => __("A simple, but in-depth wizard will configure a Terms and Conditions page for your website or for those of your clients.", 'complianz-gdpr'),
			'image' => "complianz-gdpr.png",
		],
		[
			'slug' => 'really-simple-ssl',
			'constant_free' => 'rsssl_version',
			'constant_premium' => 'rsssl_pro',
			'wordpress_url' => 'https://wordpress.org/plugins/really-simple-ssl/',
			'upgrade_url' => 'https://really-simple-ssl.com/pro?src=cmplz-plugin',
			'title' => "Really Simple Security",
            'summary' => __("Lightweight plugin. Heavyweight security features.", 'complianz-gdpr'),
			'description' => __("Leverage your SSL certificate to the fullest, with health checks, security headers, hardening, vulnerability detection and more.", 'complianz-gdpr'),
			'image' => "really-simple-ssl.png",
		]
	);

    foreach ($plugins as $index => $plugin ){
		$star_rating = false;
		require_once(CMPLZ_PATH . 'class-installer.php');
		$installer = new cmplz_installer($plugin['slug']);
        #if slug defined, get star rating as well
		if ( $slug ) {
			$plugin_info = $installer->get_plugin_info();
			$star_rating =  ['rating' => $plugin_info->rating,  'rating_count' => $plugin_info->num_ratings ];
        }

        if ( isset($plugin['constant_premium']) && defined($plugin['constant_premium']) ) {
	        $plugins[ $index ]['pluginAction'] = 'installed';
        } else if ( !$installer->plugin_is_downloaded() && !$installer->plugin_is_activated() ) {
	        $plugins[$index]['pluginAction'] = 'download';
        } else if ( $installer->plugin_is_downloaded() && !$installer->plugin_is_activated() ) {
            $plugins[ $index ]['pluginAction'] = 'activate';
        } else {
            if (isset($plugin['constant_premium']) ) {
                $plugins[$index]['pluginAction'] = 'upgrade-to-premium';
	        } else {
                $plugins[ $index ]['pluginAction'] = 'installed';
	        }
	    }
    }

    if ( $slug ) {
        foreach ($plugins as $key=> $plugin) {
            if ($plugin['slug']===$slug){
				if ($star_rating) $plugin['star_rating'] = $star_rating;

				return $plugin;
            }
        }
    }

    // If first plugin Complianz T&C is not installed, return only that one
    if ($plugins[0]['pluginAction'] !== 'installed') {
        return ['plugins' => [$plugins[0]]];
    }

    // If first plugin is installed, return only the second one
    return ['plugins' => [$plugins[1]]];
}


/**
 * List of allowed field types
 * @param $type
 *
 * @return mixed|string
 */
function cmplz_sanitize_field_type($type){
    $types = [
        'hidden',
        'license',
        'database',
        'checkbox',
        'multicheckbox',
        'password',
        'radio',
        'text',
        'text_checkbox',
        'borderradius',
        'borderwidth',
        'colorpicker',
        'css',
        'editor',
        'textarea',
        'document',
        'number',
        'email',
        'select',
        'phone',
        'url',
        'processors',
        'thirdparties',
    ];
    if ( in_array( $type, $types, true ) ){
        return $type;
    }
    return 'checkbox';
}

/**
 * @param WP_REST_Request $request
 *
 * @return array
 */
function cmplz_rest_api_fields_set( WP_REST_Request $request): array {
    if ( !cmplz_user_can_manage() ) {
        return [];
    }

	if ( COMPLIANZ::$wizard->get_lock_user() !== get_current_user_id() ) {
		return [];
	}
	$data = $request->get_param('data');
	$nonce = $request->get_param('nonce');
	if ( empty($nonce) && isset($data['nonce'])) {
		$nonce = $data['nonce'];
	}
	if ( ! wp_verify_nonce( $nonce, 'cmplz_react' ) ) {
		return [];
	}

	$fields = $request->get_param('fields');
	$finish = (bool) $request->get_param('finish');
    $config_fields = COMPLIANZ::$config->fields;
    $config_ids = array_column($config_fields, 'id');
	foreach ( $fields as $index => $field ) {
		$config_field_index = in_array( $field['id'], $config_ids, true );
		if ( $config_field_index===false ){
			unset($fields[$index]);
		}
	}

	$options = get_option( 'cmplz_options', [] );
	//build a new options array
    foreach ( $fields as $index => $field ) {
		$prev_value = $options[ $field['id'] ] ?? false;

		$value = cmplz_sanitize_field( $field['value'] , $field['type'],  $field['id']);
		$fields[$index]['value'] = $value;
		$fields[$index]['prev_value'] = $prev_value;
		$fields[$index]['type'] = cmplz_sanitize_field_type($field['type']);
		$fields[$index]['id'] = cmplz_sanitize_title_preserve_uppercase($field['id']);

        do_action( "cmplz_before_save_option", $fields[$index]['id'], $value, $prev_value, $fields[$index]['type'] );
        $options[ $field['id'] ] = $value;
		$options = cmplz_maybe_add_source_option($options, $value, $field );
    }

	foreach ( $fields as $field ) {
		$options = apply_filters( 'cmplz_before_save_options', $options, $field['id'], $field['value'], $field['prev_value'], $field['type'] );
	}

    if ( ! empty( $options ) ) {
		update_option( 'cmplz_options', $options );
    }

	foreach ( $fields as $field ) {
		$prev_value = $options[ $field['id'] ] ?? false;
        if ( isset($field['translatable']) && $field['translatable']) {

			$value = $field['value'];
			$type = $field['type'];
			$id = $field['id'];
			if ( is_array( $value ) && ( $type === 'thirdparties' || $type === 'processors' ) ) {
				foreach ( $value as $item_key => $item ) {
					//contains the values of an item
					foreach ( $item as $key => $key_value ) {
						cmplz_register_translation( $key_value, $item_key . '_' . $id . "_" . $key );
					}
				}
			} else {
				cmplz_register_translation( $value, $id );
			}

        }
        do_action( "cmplz_after_save_field", $field['id'], $field['value'], $prev_value, $field['type'] );
    }
	do_action('cmplz_after_saved_fields', $fields );
	cmplz_delete_transient('cmplz_blocked_scripts');
	if ($finish){
		do_action('cmplz_finish_wizard');
	}
	if ( ob_get_length() ) {
		ob_clean();
	}
	$fields = cmplz_fields(true, $options);
	$fields = apply_filters('cmplz_after_saved_fields', $fields );

	return [
            'request_success' => true,
            'fields' => $fields,
            //fields to update the progress bar and notices
            'notices' => COMPLIANZ::$progress->notices(),
            'show_cookiebanner' => cmplz_cookiebanner_should_load(true),
            'field_notices' => cmplz_field_notices(),
	];
}

/**
 * Update a complianz option
 *
 * @param string $name
 * @param mixed  $value
 *
 * @return void
 */
if (!function_exists('cmplz_update_option')) {
	function cmplz_update_option( string $name, $value ) {
		if ( ! cmplz_admin_logged_in() ) {
			return;
		}
		$config_fields      = COMPLIANZ::$config->fields;
		$config_ids         = array_column( $config_fields, 'id' );

		$config_field_index = array_search( $name, $config_ids );
		$config_field       = $config_fields[ $config_field_index ] ?? false;
		if ( $config_field_index === false ) {
			return;
		}

		$type = isset( $config_field['type'] ) ? $config_field['type'] : false;
		if ( ! $type ) {
			return;
		}

		$options = get_option( 'cmplz_options', [] );

		if ( ! is_array( $options ) ) {
			$options = [];
		}

		/*
		 * Some fields are duplicate, but opposite, like safe_mode vs 'enable_cookie_blocker'.
		 * This function will get the value as its mapped value in the related field.
		 * Then the value is saved in that related field
		 */

		$prev_value       = $options[ $name ] ?? false;
		$name             = cmplz_sanitize_title_preserve_uppercase( $name );
		$type             = cmplz_sanitize_field_type( $type );
		$value            = cmplz_sanitize_field( $value, $type, $name );
		$value            = apply_filters( "cmplz_field_value", $value, cmplz_sanitize_title_preserve_uppercase( $name ), $type );
		$options[ $name ] = $value;
		$options            = cmplz_maybe_add_source_option( $options, $value, $config_field );
		$options = apply_filters( 'cmplz_before_save_options', $options, $name, $value, $prev_value, $type );

		update_option( 'cmplz_options', $options );

		do_action( "cmplz_after_save_field", $name, $value, $prev_value, $type );
	}
}

/**
 * Update a complianz option without running the hooks
 * This is needed to prevent infinite loops, when used in the hook callback itself.
 *
 * @param string $name
 * @param mixed  $value
 *
 * @return void
 */
if (!function_exists('cmplz_update_option_no_hooks')) {
	function cmplz_update_option_no_hooks( string $name, $value ) {
		if ( ! cmplz_admin_logged_in() ) {
			return;
		}
		$config_fields      = COMPLIANZ::$config->fields;
		$config_ids         = array_column( $config_fields, 'id' );
		$config_field_index = array_search( $name, $config_ids );
		$config_field       = $config_fields[ $config_field_index ];
		if ( $config_field_index === false ) {
			return;
		}

		$type = isset( $config_field['type'] ) ? $config_field['type'] : false;
		if ( ! $type ) {
			return;
		}

		$options = get_option( 'cmplz_options', [] );

		if ( ! is_array( $options ) ) {
			$options = [];
		}

		$name             = cmplz_sanitize_title_preserve_uppercase( $name );
		$type             = cmplz_sanitize_field_type( $type );
		$value            = cmplz_sanitize_field( $value, $type, $name );
		$value            = apply_filters( "cmplz_field_value", $value, $name , $type );
		$options[ $name ] = $value;
		update_option( 'cmplz_options', $options );
	}
}
/**
 * Some fields are duplicate, but opposite, like safe_mode vs 'enable_cookie_blocker'.
 * This function will convert the value to equivalent value in the related field.
 * @param $options
 * @param $value
 * @param $field
 *
 * @return int|mixed|string
 */
function cmplz_maybe_add_source_option($options, $value, $field){
	if (!isset($field['source_id'])) {
		return $options;
	}
	//convert value to mapped source value
	$source_mapping = array_flip($field['source_mapping']);
	if ( !isset($source_mapping[$value]) ) {
		return $options;
	}

	$mapped_value = $source_mapping[$value];

	//get the source field, and update the value in that field.
	$config_fields = COMPLIANZ::$config->fields;;
	$config_ids = array_column($config_fields, 'id');
	$source_field_index = array_search( $field['source_id'], $config_ids, true );
	$source_field = $config_fields[$source_field_index];
	$options[$source_field['id']] = $mapped_value;
	return $options;
}

/**
 * Get the rest api fields
 * @return array
 */
function cmplz_rest_api_fields_get(): array {
	if ( !cmplz_user_can_manage() ) {
		return [];
	}
	$nonce = $_GET['nonce'] ?? false;
	if ( ! wp_verify_nonce( $nonce, 'cmplz_react' ) ) {
		return [];
	}

	if ( !COMPLIANZ::$wizard->wizard_is_locked() || COMPLIANZ::$wizard->get_lock_user() === get_current_user_id() ) {
		COMPLIANZ::$wizard->lock_wizard();
	}

	delete_option('cmplz_ajax_fallback_active' );

	$output = array();
	$fields = cmplz_fields();
	$output['fields'] = $fields;
	$output['field_notices'] = cmplz_field_notices();
	$output['request_success'] = true;
	$output['locked_by'] = COMPLIANZ::$wizard->get_lock_user() ? COMPLIANZ::$wizard->get_lock_user() : get_current_user_id();
	if ( ob_get_length() ) {
		ob_clean();
	}
    return apply_filters('cmplz_rest_api_fields_get', $output);
}

/**
 * Sanitize a field
 *
 * @param mixed  $value
 * @param string $type
 * @oaram string $id
 *
 * @return array|bool|int|string|void
 */
function cmplz_sanitize_field( $value, string $type, string $id ) {
	switch ( $type ) {
		case 'checkbox':
		case 'number':
			return (int) $value;
		case 'hidden':
			return sanitize_title($value);
		case 'document':
			return in_array($value, ['generated','custom','url','none']) ? $value : false;
		case 'url':
			return esc_url_raw($value);
		case 'license':
			return $value;
		case 'multicheckbox':
			if ( ! is_array( $value ) ) {
				$value = empty($value) ? [] : [$value => 1];
			}
			return array_map( 'sanitize_text_field', $value );
		case 'email':
			return sanitize_email( $value );
		case 'processors':
			return cmplz_sanitize_processors($value);
		case 'thirdparties':
			return cmplz_sanitize_thirdparties($value);
		case 'editor':
			return wp_kses_post($value);
		case 'colorpicker':
			return cmplz_sanitize_color_picker($value);
		case 'textarea':
			return wp_kses_post($value);
		case 'select':
		case 'password':
		case 'text':
		default:
			return sanitize_text_field( $value );
	}
}

function cmplz_sanitize_color_picker($value) {

	if ( !is_array($value)) {
		return false;
	}

	foreach ( $value as $key => $color ) {
		$value[sanitize_text_field($key)] = sanitize_hex_color($color);
	}

	return $value;
}
/**
 * Sanizite a processors field
 * @param array $processors
 *
 * @return array
 */
function cmplz_sanitize_processors($processors) {
	if ( !is_array($processors) ) {
		$processors = [];
	}
	$defaults = [
			'name' => '',
			'processing_agreement' => false,
			'country' => false,
			'purpose' => '',
			'data' => '',
			'saved_by_user' => false,
	];

	foreach ( $processors as $index => $processor ) {
		$processor = wp_parse_args($processor, $defaults);
		$processor['name'] = sanitize_text_field($processor['name']);
		$processor['processing_agreement'] = (int) $processor['processing_agreement'];
		$processor['country'] = sanitize_text_field($processor['country']);
		$processor['purpose'] = sanitize_text_field($processor['purpose']);
		$processor['data'] = sanitize_text_field($processor['data']);
		$processor['saved_by_user'] = (bool) $processor['saved_by_user'];
		$processors[$index] = $processor;
	}
	return $processors;
}

/**
 * Sanizite a processors field
 *
 * @param array $thirdparties
 *
 * @return array
 */
function cmplz_sanitize_thirdparties( $thirdparties ): array {
	if (!is_array($thirdparties)) {
		$thirdparties = [];
	}
	$defaults = [
			'name' => '',
			'country' => false,
			'purpose' => '',
			'data' => '',
			'saved_by_user' => false,
	];

	foreach ( $thirdparties as $index => $thirdparty ) {
		$thirdparty = wp_parse_args($thirdparty, $defaults);
		$thirdparty['name'] = sanitize_text_field($thirdparty['name']);
		$thirdparty['country'] = sanitize_text_field($thirdparty['country']);
		$thirdparty['purpose'] = sanitize_text_field($thirdparty['purpose']);
		$thirdparty['data'] = sanitize_text_field($thirdparty['data']);
		$thirdparty['saved_by_user'] = (bool) $thirdparty['saved_by_user'];
		$thirdparty[$index] = $thirdparty;
	}
	return $thirdparties;
}

/**
 * Sanitize and encode a password
 *
 * @param $password
 *
 * @return mixed|string
 */
function cmplz_encode_password($password) {
	if (!cmplz_user_can_manage()) {
		return $password;
	}
	if ( strlen(trim($password)) === 0 ) {
		return $password;
	}

    $password = sanitize_text_field($password);
	if (strpos( $password , 'cmplz_') !== FALSE ) {
		return $password;
	}

	$key = get_site_option('cmplz_key');
	if ( !$key ) {
		update_site_option( 'cmplz_key' , time() );
		$key = get_site_option('cmplz_key');
	}

	$ivlength = openssl_cipher_iv_length('aes-256-cbc');
	$iv = openssl_random_pseudo_bytes($ivlength);
	$ciphertext_raw = openssl_encrypt($password, 'aes-256-cbc', $key, 0, $iv);
	$key = base64_encode( $iv.$ciphertext_raw );

	return 'cmplz_'.$key;
}

/**
 * Check if the server side conditions apply
 *
 * @param array $conditions
 *
 * @return bool
 */

function cmplz_conditions_apply( array $conditions ){
	if (!cmplz_user_can_manage()) {
		return false;
	}
	$defaults = ['relation' => 'AND'];
	$conditions = wp_parse_args($conditions, $defaults);
	$relation = $conditions['relation'] === 'AND' ? 'AND' : 'OR';
	unset($conditions['relation']);
	$condition_applies = true;

	foreach ( $conditions as $condition => $condition_value ) {
		$invert = substr($condition, 1)==='!';
		$condition = ltrim($condition, '!');

		if ( is_array($condition_value)) {
			$this_condition_applies = cmplz_conditions_apply($condition_value);
		} else {
			//check if it's a function
			if (substr($condition, -2) === '()'){
				$func = $condition;
				if ( preg_match( '/(.*)\:\:(.*)->(.*)/i', $func, $matches)) {
					$class = $matches[2];
					$func = $matches[3];
					$func = str_replace('()', '', $func);
					$this_condition_applies = COMPLIANZ::${$class}->$func() === $condition_value;
				} else {
					$func = str_replace('()', '', $func);
					$this_condition_applies = $func() === $condition_value;
				}
			} else {
				$this_condition_applies = cmplz_get_option($condition) === $condition_value;
			}

			if ( $invert ){
				$this_condition_applies = !$this_condition_applies;
			}

		}

		if ($relation === 'AND') {
			$condition_applies = $condition_applies && $this_condition_applies;
		} else {
			$condition_applies = $condition_applies || $this_condition_applies;
		}
	}

	return $condition_applies;
}
