<?php
/**
 * Consent signals for the Meta plugins (Meta Pixel for WordPress 5.2.1+, Meta for WooCommerce 3.7+).
 *
 * Both ship a hold/release engine: window.fbwcsignal, the wc_facebook_signals_state cookie and the
 * facebook_signals_held server filter. This file drives it from the Complianz consent: an explicit choice for the
 * Facebook service (consent per service) decides, otherwise the marketing category does. Server events follow that
 * rule, and Meta's pixel snippet gets its fbevents.js loader deferred until consent, with a bridge script that releases
 * or holds the engine on the same rule. A snippet that is not recognised stays blocked as before.
 *
 * Included from the main plugin file, so the filter exists before the Meta plugins load (Meta Pixel for WordPress
 * reads the held state while loading). Functions are wrapped in function_exists() because the free and the premium
 * plugin can both include this file.
 *
 * @package Complianz
 */

defined( 'ABSPATH' ) || die( 'You do not have access to this page!' );

if ( ! function_exists( 'cmplz_meta_signals_marker' ) ) {
	/**
	 * Marker comment that exempts a recognised Meta script from the cookie blocker.
	 *
	 * Random per request: the blocker skips any tag containing it, so page content must not be able to guess it.
	 *
	 * @return string
	 */
	function cmplz_meta_signals_marker() {
		static $marker = null;
		if ( null === $marker ) {
			$marker = '/*cmplz-meta-signals-' . bin2hex( random_bytes( 8 ) ) . '*/';
		}

		return $marker;
	}
}

if ( ! function_exists( 'cmplz_meta_signals_option' ) ) {
	/**
	 * Read a Complianz option straight from the option table; works while plugins are still loading.
	 *
	 * @param string $id Option id.
	 * @return mixed
	 */
	function cmplz_meta_signals_option( $id ) {
		$options = get_option( 'cmplz_options', array() );

		return is_array( $options ) && isset( $options[ $id ] ) ? $options[ $id ] : '';
	}
}

if ( ! function_exists( 'cmplz_meta_signals_cookie_prefix' ) ) {
	/**
	 * The prefix of the Complianz consent cookies, the same rule as cmplz_banner_loader::get_cookie_prefix().
	 *
	 * @return string
	 */
	function cmplz_meta_signals_cookie_prefix() {
		if ( is_multisite() && is_main_site() && ! cmplz_meta_signals_option( 'set_cookies_on_root' ) ) {
			return 'cmplz_rt_';
		}

		return 'cmplz_';
	}
}

if ( ! function_exists( 'cmplz_meta_signals_plugin' ) ) {
	/**
	 * Which Meta plugin runs the engine: 'wp' (Meta Pixel for WordPress), 'woo' (Meta for WooCommerce) or ''.
	 * Empty when neither has it, or both do: they share one window.fbwcsignal.
	 *
	 * @return string
	 */
	function cmplz_meta_signals_plugin() {
		// The WordPress plugin requires its classes when it loads. Meta for WooCommerce autoloads, so check its function first.
		$wp  = class_exists( 'FacebookPixelPlugin\Core\FacebookSignalState', false );
		$woo = function_exists( 'facebook_for_woocommerce' ) && class_exists( 'WooCommerce\Facebook\Events\FacebookSignalsState' );
		if ( $wp === $woo ) {
			return '';
		}

		return $wp ? 'wp' : 'woo';
	}
}

if ( ! function_exists( 'cmplz_meta_signals_integration_enabled' ) ) {
	/**
	 * Is the Complianz integration for this Meta plugin enabled? Read from the option, the integration list is not loaded yet.
	 *
	 * @param string $plugin 'wp' or 'woo'.
	 * @return bool
	 */
	function cmplz_meta_signals_integration_enabled( $plugin ) {
		$key     = 'wp' === $plugin ? 'facebook-for-wordpress' : 'facebook-for-woocommerce';
		$enabled = get_option( 'complianz_options_integrations', array() );

		return ! is_array( $enabled ) || ! isset( $enabled[ $key ] ) || (bool) $enabled[ $key ];
	}
}

if ( ! function_exists( 'cmplz_meta_signals_blocker_on' ) ) {
	/**
	 * Does Complianz block scripts on this request? The checks possible before the page is parsed.
	 *
	 * @return bool
	 */
	function cmplz_meta_signals_blocker_on() {
		if ( 'yes' !== cmplz_meta_signals_option( 'enable_cookie_blocker' ) || cmplz_meta_signals_option( 'safe_mode' ) ) {
			return false;
		}
		if ( ( defined( 'CMPLZ_DO_NOT_BLOCK' ) && CMPLZ_DO_NOT_BLOCK ) || isset( $_GET['cmplz_safe_mode'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return false;
		}
		if ( ( defined( 'JSON_REQUEST' ) && JSON_REQUEST ) || ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) ) {
			return false;
		}
		// The cookie scanner must see the Meta scripts running (its nonce check needs wp_verify_nonce(), not loaded yet).
		if ( function_exists( 'wp_verify_nonce' ) && function_exists( 'cmplz_scan_in_progress' ) && cmplz_scan_in_progress() ) {
			return false;
		}
		// Without a consent warning nothing is blocked and the Meta plugins run on their own.
		if ( class_exists( 'COMPLIANZ', false ) && isset( COMPLIANZ::$banner_loader ) && ! COMPLIANZ::$banner_loader->site_needs_cookie_warning() ) {
			return false;
		}

		return true;
	}
}

if ( ! function_exists( 'cmplz_meta_signals_woo_connected' ) ) {
	/**
	 * Is Meta for WooCommerce connected? Without a connection it does not load fbwcsignal.
	 *
	 * @return bool
	 */
	function cmplz_meta_signals_woo_connected() {
		if ( ! function_exists( 'facebook_for_woocommerce' ) ) {
			return false;
		}
		try {
			return (bool) facebook_for_woocommerce()->get_connection_handler()->is_connected();
		} catch ( \Throwable $e ) {
			return false;
		}
	}
}

if ( ! function_exists( 'cmplz_meta_signals_active' ) ) {
	/**
	 * Should Complianz drive the Meta consent engine on this request?
	 *
	 * @return bool
	 */
	function cmplz_meta_signals_active() {
		/**
		 * Kill switch: return false to go back to blocking the Meta scripts.
		 *
		 * @param bool $enabled Whether Complianz drives the Meta consent engine.
		 */
		if ( ! apply_filters( 'cmplz_meta_signals_enabled', true ) ) {
			return false;
		}

		$plugin = cmplz_meta_signals_plugin();
		if ( '' === $plugin || ! cmplz_meta_signals_integration_enabled( $plugin ) || ( 'woo' === $plugin && ! cmplz_meta_signals_woo_connected() ) ) {
			return false;
		}

		// Once the page is parsed, use the decision the cookie blocker itself makes (previews, feeds, scans...).
		// The answer does not change during the request and this runs for every event Meta wants to send.
		if ( did_action( 'wp' ) ) {
			static $after_wp = null;
			if ( null === $after_wp ) {
				$after_wp = (bool) cmplz_can_run_cookie_blocker();
			}

			return $after_wp;
		}

		return cmplz_meta_signals_blocker_on();
	}
}

if ( ! function_exists( 'cmplz_meta_signals_service_choice' ) ) {
	/**
	 * The visitor's explicit choice for the Facebook service, made with consent per service on the cookie policy page.
	 *
	 * Read from the consented_services cookie the way cmplz_has_service_consent() reads it, but without its consent
	 * type rules: an explicit choice decides for every consent type, opt-out included. The 'facebook' key matches the
	 * cookie policy checkbox while the service is named "Facebook" in the cookie list, which is the wizard default and
	 * the name synced from cookiedatabase.org.
	 *
	 * @return bool|null True or false for an explicit choice, null when there is none or the cookie is not valid JSON.
	 */
	function cmplz_meta_signals_service_choice() {
		$name = cmplz_meta_signals_cookie_prefix() . 'consented_services';
		if ( ! isset( $_COOKIE[ $name ] ) || ! is_string( $_COOKIE[ $name ] ) ) {
			return null;
		}
		$services = json_decode( stripslashes( $_COOKIE[ $name ] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- JSON written by complianz.js, decoded like cmplz_has_service_consent(); only the facebook flag is read, as a bool.
		if ( ! is_object( $services ) || ! property_exists( $services, 'facebook' ) ) {
			return null;
		}

		return (bool) $services->facebook;
	}
}

if ( ! function_exists( 'cmplz_meta_signals_allows_marketing' ) ) {
	/**
	 * Does Complianz release Meta for this visitor? An explicit choice for the Facebook service decides; otherwise the
	 * marketing category, consent type aware (opt-out regions). Before init, and with Do Not Track or Global Privacy
	 * Control respected (as in complianz.js), only the stored cookie counts: an explicit allow, failing closed. Those
	 * two paths skip the cmplz_has_consent filter.
	 *
	 * @return bool
	 */
	function cmplz_meta_signals_allows_marketing() {
		$choice = cmplz_meta_signals_service_choice();
		if ( null !== $choice ) {
			return $choice;
		}
		if ( ! did_action( 'init' ) || ( function_exists( 'cmplz_dnt_enabled' ) && cmplz_dnt_enabled() ) ) {
			$name = cmplz_meta_signals_cookie_prefix() . 'marketing';

			return isset( $_COOKIE[ $name ] ) && 'allow' === $_COOKIE[ $name ];
		}

		return (bool) cmplz_has_consent( 'marketing' );
	}
}

if ( ! function_exists( 'cmplz_meta_signals_is_visitor_request' ) ) {
	/**
	 * Is this request made by a visitor's browser? Cron, WP-CLI, the admin and server-to-server calls (webhooks) have no
	 * consent to apply. A browser sends cookies, Sec-Fetch-Site, a Referer or an Origin; the state cookie added by
	 * cmplz_meta_signals_set_request_state() does not count.
	 *
	 * @return bool
	 */
	function cmplz_meta_signals_is_visitor_request() {
		if ( wp_doing_cron() || ( defined( 'WP_CLI' ) && WP_CLI ) || ( is_admin() && ! wp_doing_ajax() ) ) {
			return false;
		}

		return ! empty( array_diff_key( $_COOKIE, array( 'wc_facebook_signals_state' => 1 ) ) ) || ! empty( $_SERVER['HTTP_SEC_FETCH_SITE'] ) || ! empty( $_SERVER['HTTP_REFERER'] ) || ! empty( $_SERVER['HTTP_ORIGIN'] );
	}
}

if ( ! function_exists( 'cmplz_meta_signals_held' ) ) {
	/**
	 * Meta's facebook_signals_held filter: hold server events unless Complianz releases Meta (Facebook service choice,
	 * else marketing), for visitor requests only.
	 *
	 * @param bool $held Whether signals are held.
	 * @return bool
	 */
	function cmplz_meta_signals_held( $held ) {
		if ( $held || ! cmplz_meta_signals_is_visitor_request() || ! cmplz_meta_signals_active() ) {
			return $held;
		}

		return ! cmplz_meta_signals_allows_marketing();
	}
}
add_filter( 'facebook_signals_held', 'cmplz_meta_signals_held' );

if ( ! function_exists( 'cmplz_meta_signals_set_request_state' ) ) {
	/**
	 * Give the Meta plugins the state matching the Complianz choice for this request. No cookie is sent (page caches would
	 * stop storing the response); the bridge script sets the browser cookie. Runs on 'wp' to know if the blocker runs.
	 *
	 * @return void
	 */
	function cmplz_meta_signals_set_request_state() {
		if ( ! cmplz_meta_signals_active() ) {
			return;
		}
		$_COOKIE['wc_facebook_signals_state'] = cmplz_meta_signals_allows_marketing() ? 'active' : 'held';
	}
}
add_action( 'wp', 'cmplz_meta_signals_set_request_state', 0 );

if ( ! function_exists( 'cmplz_meta_signals_whitelist' ) ) {
	/**
	 * Exempt the scripts marked by cmplz_meta_signals_rewrite() from the cookie blocker, only while the integration runs.
	 *
	 * @param array $tags Whitelisted script tags.
	 * @return array
	 */
	function cmplz_meta_signals_whitelist( $tags ) {
		if ( ! cmplz_meta_signals_active() ) {
			return $tags;
		}
		$tags[] = cmplz_meta_signals_marker();

		return $tags;
	}
}
add_filter( 'cmplz_whitelisted_script_tags', 'cmplz_meta_signals_whitelist' );

if ( ! function_exists( 'cmplz_meta_signals_bridge_js' ) ) {
	/**
	 * The bridge between the Complianz consent events and the Meta consent engine, as inline JavaScript.
	 *
	 * @param string $plugin 'wp' or 'woo'.
	 * @return string
	 */
	function cmplz_meta_signals_bridge_js( $plugin ) {
		$js = <<<'JS'
( function () {
	var PLUGIN = '%PLUGIN%';
	var RELOAD_FLAG = 'cmplz_meta_signals_reload';
	var lastAllowed = null;
	function cookieName() {
		return ( window.facebookSignalConfig && window.facebookSignalConfig.cookieName ) || 'wc_facebook_signals_state';
	}
	function readCookie( name ) {
		var match = document.cookie.match( new RegExp( '(?:^|;\\s*)' + name + '=([^;]*)' ) );
		return match ? decodeURIComponent( match[ 1 ] ) : null;
	}
	function setState( state ) {
		var expires = new Date();
		expires.setTime( expires.getTime() + 365 * 24 * 60 * 60 * 1000 );
		document.cookie = cookieName() + '=' + encodeURIComponent( state ) + ';expires=' + expires.toUTCString() + ';path=/;SameSite=Lax';
	}
	function eraseCookie( name ) {
		var expired = '=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/';
		var host = window.location.hostname.split( '.' );
		document.cookie = name + expired;
		for ( var i = 0; i < host.length - 1; i++ ) {
			document.cookie = name + expired + ';domain=.' + host.slice( i ).join( '.' );
		}
	}
	function swallow( request ) {
		if ( request && typeof request.catch === 'function' ) {
			request.catch( function () {} );
		}
	}
	// Reload at most once per session, and only after the hold went through: a failing hold must not loop.
	function reloadOnce() {
		try {
			if ( window.sessionStorage.getItem( RELOAD_FLAG ) ) {
				return;
			}
			window.sessionStorage.setItem( RELOAD_FLAG, '1' );
		} catch ( e ) {
			return;
		}
		window.location.reload();
	}
	function withSignal( callback, tries ) {
		if ( window.fbwcsignal && typeof window.fbwcsignal.getState === 'function' ) {
			callback( window.fbwcsignal );
		} else if ( tries > 0 ) {
			setTimeout( function () {
				withSignal( callback, tries - 1 );
			}, 100 );
		}
	}
	function apply( allowed ) {
		// The same answer can arrive twice on load (direct check and the init event).
		if ( lastAllowed === allowed ) {
			return;
		}
		lastAllowed = allowed;
		if ( allowed ) {
			try {
				window.sessionStorage.removeItem( RELOAD_FLAG );
			} catch ( e ) {}
			if ( typeof window.__cmplzLoadFb === 'function' ) {
				window.__cmplzLoadFb();
			}
			withSignal( function ( signal ) {
				if ( signal.getState() !== 'active' ) {
					swallow( signal.release() );
				}
			}, 300 );
			return;
		}
		withSignal( function ( signal ) {
			if ( signal.getState() === 'held' ) {
				return;
			}
			eraseCookie( '_fbp' );
			eraseCookie( '_fbc' );
			var request = signal.hold();
			if ( PLUGIN === 'woo' && request && typeof request.then === 'function' ) {
				// The in-page engine of Meta for WooCommerce has no hold(): start the next page held.
				request.then( reloadOnce, function () {} );
				return;
			}
			swallow( request );
		}, 300 );
	}

	// The visitor's choice for the Facebook service when the site offers consent per service, else the marketing
	// category. complianz.js falls back to the category itself when there is no entry for the service.
	function allowed( categories ) {
		if ( typeof window.cmplz_has_service_consent === 'function' ) {
			return !! window.cmplz_has_service_consent( 'facebook', 'marketing' );
		}
		return categories.indexOf( 'marketing' ) !== -1;
	}

	// A browser without a state yet starts held, before the Meta engine reads the cookie.
	try {
		if ( readCookie( cookieName() ) === null ) {
			setState( 'held' );
		}
	} catch ( e ) {}
	document.addEventListener( 'cmplz_fire_categories', function ( e ) {
		var categories = e && e.detail && Array.isArray( e.detail.categories ) ? e.detail.categories : [];
		apply( allowed( categories ) );
	} );
	// complianz.js may have run before this script when scripts are delayed.
	if ( typeof window.cmplz_has_service_consent === 'function' ) {
		apply( allowed( [] ) );
	}
} )();
JS;

		return str_replace( '%PLUGIN%', 'woo' === $plugin ? 'woo' : 'wp', $js );
	}
}

if ( ! function_exists( 'cmplz_meta_signals_is_javascript' ) ) {
	/**
	 * Is this an inline JavaScript script? JSON, import maps and templates must not get a comment added.
	 *
	 * @param string $open The opening script tag.
	 * @return bool
	 */
	function cmplz_meta_signals_is_javascript( $open ) {
		if ( 1 === preg_match( '/\bsrc\s*=/i', $open ) ) {
			return false;
		}
		$type = 1 === preg_match( '/\btype\s*=\s*["\']?([^"\'\s>]+)/i', $open, $match ) ? strtolower( $match[1] ) : '';

		return in_array( $type, array( '', 'text/javascript', 'application/javascript', 'module' ), true );
	}
}

if ( ! function_exists( 'cmplz_meta_signals_rewrite' ) ) {
	/**
	 * Rewrite Meta's standard pixel snippet so fbevents.js is only requested after consent.
	 *
	 * The injection of the loader becomes window.__cmplzLoadFb, called by the bridge after consent; queued fbq
	 * calls replay when the loader arrives. The snippet, the engine script after it and other inline scripts using the
	 * engine are marked for the cookie blocker to skip. Hand-inserted pixels are not touched.
	 *
	 * Scripts are split with one possessive pattern: lazy patterns hit a PCRE JIT stack error on large inline scripts.
	 *
	 * @param string $html   The page.
	 * @param string $plugin 'wp' or 'woo'.
	 * @return array{html: string, matched: bool}
	 */
	function cmplz_meta_signals_rewrite( $html, $plugin ) {
		$result = array(
			'html'    => $html,
			'matched' => false,
		);
		if ( ! is_string( $html ) || false === strpos( $html, 'fbevents.js' ) ) {
			return $result;
		}

		$found = preg_match_all( '#(<script\b[^>]*+>)((?:[^<]++|<(?!/script>))*+)(</script>)#i', $html, $scripts, PREG_SET_ORDER | PREG_OFFSET_CAPTURE );
		if ( ! $found ) {
			return $result;
		}

		$marker = cmplz_meta_signals_marker();
		$needle = '#s\.parentNode\.insertBefore\(\s*t\s*,\s*s\s*\)#';
		// A script whose code starts (whitespace and block comments aside) with the engine, not one that mentions it later.
		$engine = '#^(?:\s++|/\*(?:[^*]++|\*(?!/))*+\*/)*+(?:window\.)?FacebookSignals?\b#';

		// Stub scripts directly followed (comments and whitespace aside) by the engine script.
		$stubs = array();
		foreach ( $scripts as $i => $script ) {
			$body = $script[2][0];
			if ( false !== strpos( $body, '__cmplzLoadFb' ) ) {
				return $result; // Already rewritten.
			}
			if ( ! isset( $scripts[ $i + 1 ] ) || false === strpos( $body, '!function(f,b,e,v,n,t,s)' ) || 1 !== preg_match( $needle, $body ) ) {
				continue;
			}
			$next      = $scripts[ $i + 1 ];
			$gap_start = $script[3][1] + strlen( $script[3][0] );
			$gap       = substr( $html, $gap_start, $next[0][1] - $gap_start );
			if ( 1 === preg_match( '#^(?:\s|<!--(?:(?!-->).)*-->)*$#s', $gap ) && 1 === preg_match( '#^\s*(?:window\.)?FacebookSignals?\b#', $next[2][0] ) ) {
				$stubs[ $i ] = true;
			}
		}
		if ( ! $stubs ) {
			return $result;
		}

		$out     = '';
		$pos     = 0;
		$bridged = false;
		foreach ( $scripts as $i => $script ) {
			$out .= substr( $html, $pos, $script[0][1] - $pos );
			$pos  = $script[0][1] + strlen( $script[0][0] );
			$open = $script[1][0];
			$body = $script[2][0];

			if ( isset( $stubs[ $i ] ) ) {
				// The first script of the page may be gone when the loader is injected: then append to the head.
				$body = preg_replace( $needle, 'window.__cmplzLoadFb=function(){if(window.__cmplzFbDone){return;}window.__cmplzFbDone=1;if(s.parentNode){s.parentNode.insertBefore(t,s);}else{(document.head||document.documentElement).appendChild(t);}}', $body, 1 );
				$out .= $open . $marker . "\n" . $body . $script[3][0];
				if ( ! $bridged ) {
					$bridged = true;
					$nonce   = 1 === preg_match( '/\bnonce\s*=\s*("[^"]*"|\'[^\']*\')/', $open, $nonce_match ) ? ' nonce=' . $nonce_match[1] : '';
					$out    .= "\n<script" . $nonce . '>' . cmplz_meta_signals_bridge_js( $plugin ) . "</script>\n";
				}
			} elseif ( cmplz_meta_signals_is_javascript( $open ) && 1 === preg_match( $engine, $body ) ) {
				// The engine script and every other inline script of the Meta plugin that starts with an engine call.
				$out .= $open . $marker . "\n" . $body . $script[3][0];
			} else {
				$out .= $script[0][0];
			}
		}
		$out .= substr( $html, $pos );

		$result['html']    = $out;
		$result['matched'] = true;

		return $result;
	}
}

if ( ! function_exists( 'cmplz_meta_signals_filter_output' ) ) {
	/**
	 * Cookie blocker hook: rewrite the page before the blocker looks at it.
	 *
	 * @param string $html The page.
	 * @return string
	 */
	function cmplz_meta_signals_filter_output( $html ) {
		if ( ! cmplz_meta_signals_active() ) {
			return $html;
		}
		$result = cmplz_meta_signals_rewrite( $html, cmplz_meta_signals_plugin() );
		if ( ! $result['matched'] ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG && is_string( $html ) && false !== strpos( $html, 'FacebookSignal' ) && false === strpos( $html, '__cmplzLoadFb' ) ) {
				error_log( 'Complianz: the Meta pixel snippet was not recognised, the Meta scripts stay blocked until consent.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}
			return $html;
		}

		return $result['html'];
	}
}
add_filter( 'cmplz_cookieblocker_before_replace', 'cmplz_meta_signals_filter_output' );
