<?php
// when loaded from the support form, WordPress is already loaded,
if ( ! isset( $_GET['support_form'] ) ) {
	// No need for the template engine
	if ( ! defined( 'WP_USE_THEMES' ) ) {
		define( 'WP_USE_THEMES', false );
	}

	// we set wp admin to true, so the backend features get loaded.
	if ( ! defined( 'CMPLZ_DOING_SYSTEM_STATUS' ) ) {
		define( 'CMPLZ_DOING_SYSTEM_STATUS', true );
	}

	// find the base path
	if ( ! defined( 'BASE_PATH' ) ) {
		define( 'BASE_PATH', find_wordpress_base_path() . '/' );
	}

	// Load WordPress Core
	if ( ! file_exists( BASE_PATH . 'wp-load.php' ) ) {
		die( 'WordPress not installed here' );
	}
	require_once BASE_PATH . 'wp-load.php';
	require_once ABSPATH . 'wp-includes/class-phpass.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

function cmplz_get_system_status() {
	ob_start();
	echo 'Domain:' . esc_url_raw( site_url() ) . "\n";
	$console_errors = cmplz_get_console_errors();
	if ( empty( $console_errors ) ) {
		$console_errors = 'none found';
	}
	echo 'Detected console errors: ' . $console_errors . "\n" . "\n";

	echo "General\n";
	echo "---------\n";
	echo 'Plugin version: ' . CMPLZ_VERSION . "\n";
	global $wp_version;
	echo 'WordPress version: ' . $wp_version . "\n";
	echo 'PHP version: ' . PHP_VERSION . "\n";
	echo 'Server: ' . cmplz_get_server() . "\n";
	$multisite = is_multisite() ? 'yes' : 'no';
	echo 'Multisite: ' . $multisite . "\n";

	$auto_updating_plugins = get_option( 'auto_update_plugins' );
	echo "\n\n" . 'WordPress settings' . "\n";
	if ( is_array( $auto_updating_plugins ) && ( in_array( CMPLZ_PLUGIN, $auto_updating_plugins )
												|| in_array( 'complianz-gdpr-premium/complianz-gpdr-premium.php', $auto_updating_plugins )
												|| in_array( 'complianz-gdpr/complianz-gpdr.php', $auto_updating_plugins ) ) ) {
		echo 'auto_update_plugins enabled' . "\n";
	} else {
		echo 'auto_update_plugins disabled' . "\n";
	}
	echo "---------\n\n";

	if ( get_option( 'cmplz_curl_error' ) ) {
		echo 'CURL error detected: ' . get_option( 'cmplz_curl_error' ) . "\n";

	}

	$plugins = wp_get_active_and_valid_plugins();
	echo 'Active plugins: ' . "\n";
	echo implode( "\n", $plugins ) . "\n";
	if ( is_multisite() ) {
		echo 'Network active plugins: ' . "\n";
		$network_plugins = wp_get_active_network_plugins();
		echo implode( "\n", $network_plugins ) . "\n";
	}

	$wizard = get_option( 'cmplz_options' );

	if ( is_array( $wizard ) ) {
		echo "\n\n" . 'Settings' . "\n";
		echo "---------\n";
		$t = array_keys( $wizard );
		echo implode_array_recursive( $wizard );
	} else {
		echo 'Wizard not completed yet';
	}

	do_action( 'cmplz_system_status' );
	return ob_get_clean();
}

// only run this when downloaded directly
if ( ! isset( $_GET['support_form'] ) ) {

	if ( cmplz_user_can_manage() ) {
		$content = cmplz_get_system_status();

		if ( function_exists( 'mb_strlen' ) ) {
			$fsize = mb_strlen( $content, '8bit' );
		} else {
			$fsize = strlen( $content );
		}
		$file_name = 'complianz-system-status.txt';
		header( 'Content-type: application/octet-stream' );

		// direct download
		header( 'Content-Disposition: attachment; filename="' . $file_name . '"' );

		// open in browser
		header( "Content-length: $fsize" );
		header( 'Cache-Control: private', false ); // required for certain browsers
		header( 'Pragma: public' ); // required
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Content-Transfer-Encoding: binary' );

		echo $content;

	} else {
		// should not be here, so redirect to home
		wp_redirect( home_url() );
		exit;
	}
}

/**
 * Get the WP Base path
 *
 * @return false|string|null
 */
function find_wordpress_base_path() {
	$path = __DIR__;

	do {
		if ( file_exists( $path . '/wp-config.php' ) ) {
			// check if the wp-load.php file exists here. If not, we assume it's in a subdir.
			if ( file_exists( $path . '/wp-load.php' ) ) {
				return $path;
			} else {
				// wp not in this directory. Look in each folder to see if it's there.
				if ( file_exists( $path ) && $handle = opendir( $path ) ) {
					while ( false !== ( $file = readdir( $handle ) ) ) {
						if ( $file != '.' && $file != '..' ) {
							$file = $path . '/' . $file;
							if ( is_dir( $file ) && file_exists( $file . '/wp-load.php' ) ) {
								$path = $file;
								break;
							}
						}
					}
					closedir( $handle );
				}
			}

			return $path;
		}
	} while ( $path = realpath( "$path/.." ) );

	return false;
}

/**
 * Generate a readable string from an array
 *
 * @param array $array
 *
 * @return string
 */
function implode_array_recursive( $array ) {
	if ( ! is_array( $array ) ) {
		return '';
	}
	return implode(
		"\n",
		array_map(
			function ( $v, $k ) {
				if ( is_array( $v ) ) {
					$output = implode_array_recursive( $v );
				} else {
					$output = sprintf( '%s : %s', $k, $v );
				}
				return $output;
			},
			$array,
			array_keys( $array )
		)
	);
}
