<?php
defined( 'ABSPATH' ) || die( 'you do not have access to this page!' );

/**
 * Conditional notices for fields
 *
 * @param array $notices The notices array.
 *
 * @return array
 */
function cmplz_google_site_kit_show_compile_statistics_notice( array $notices ): array {
	if ( ! cmplz_user_can_manage() ) {
		return array();
	}

	// Don't run integration if WP Consent API is active.
	if ( defined( 'WP_CONSENT_API_VERSION' ) ) {
		return array();
	}

	$notices[] = array(
		'field_id' => 'consent-mode',
		'label'    => 'warning',
		'url'      => 'https://complianz.io/a-complete-guide-to-install-site-kit-and-configure-google-consent-mode-with-complianz/',
		'title'    => 'Google Site Kit',
		// translators: %s is the plugin name.
		'text'     => cmplz_sprintf( __( "Because you're using %s, you can choose which plugin should insert the relevant snippet. If you want to use Google Consent Mode, you can only use the default, advanced mode. You can read more about configuring SiteKit and the different Consent Mode below.", 'complianz-gdpr' ), 'Google Site Kit' ),
	);

	return $notices;
}
add_filter( 'cmplz_field_notices', 'cmplz_google_site_kit_show_compile_statistics_notice', 10, 1 );

/**
 * Check if Google Site Kit inserts the Tag Manager container itself.
 *
 * Mirrors Site Kit's Tag_Manager guards, read from the options so no Site Kit classes are needed.
 *
 * @return bool
 */
function cmplz_google_site_kit_owns_tagmanager(): bool {
	$active = get_option( 'googlesitekit_active_modules' );
	if ( ! is_array( $active ) ) {
		// Fall back to the legacy option name.
		$active = get_option( 'googlesitekit-active-modules' );
	}
	$tagmanager = (array) get_option( 'googlesitekit_tagmanager_settings', array() );

	// Site Kit prints no tag outside production, so without this we would leave no container at all.
	if ( function_exists( 'wp_get_environment_type' ) ) {
		$allowed_environments = apply_filters( 'googlesitekit_allowed_tag_environment_types', array( 'production' ) );
		if ( ! is_array( $allowed_environments ) ) {
			$allowed_environments = array( 'production' );
		}
		if ( ! in_array( wp_get_environment_type(), $allowed_environments, true ) ) {
			return false;
		}
	}

	// useSnippet defaults to true in Site Kit, so only an explicit false disables the snippet.
	return is_array( $active )
		&& in_array( 'tagmanager', $active, true )
		&& ! empty( $tagmanager['containerID'] )
		&& ( ! isset( $tagmanager['useSnippet'] ) || $tagmanager['useSnippet'] );
}

/**
 * We remove some actions to integrate fully
 * */
function cmplz_google_site_kit_remove_scripts_others() {
	// compile_statistics, as the tag manager script is shared with Matomo Tag Manager.
	// configuration_by_complianz, as get_tagmanager_script() returns early without it.
	// Field defaults are unavailable before init, but all of these read the same at render time.
	$site_kit_owns_tagmanager = 'yes' === cmplz_get_option( 'configuration_by_complianz' )
		&& 'google-tag-manager' === cmplz_get_option( 'compile_statistics' )
		&& cmplz_google_site_kit_owns_tagmanager();

	// A duplicate container is not a consent mode problem, so this is not gated on consent mode.
	if ( $site_kit_owns_tagmanager ) {
		remove_action( 'cmplz_tagmanager_script', array( COMPLIANZ::$banner_loader, 'get_tagmanager_script' ), 10 );
	}

	if ( cmplz_consent_mode() || defined( 'WP_CONSENT_API_VERSION' ) ) {
		remove_action( 'cmplz_statistics_script', array( COMPLIANZ::$banner_loader, 'get_statistics_script' ), 10 );

		if ( $site_kit_owns_tagmanager ) {
			// The signals shared a template with the loader we removed, so add them back separately.
			add_action( 'wp_head', 'cmplz_google_site_kit_tagmanager_script', 0 );
		}
	}
}
add_action( 'after_setup_theme', 'cmplz_google_site_kit_remove_scripts_others' );

/**
 * Add Google Site Kit consent mode script for Tag Manager
 *
 * Signals only, no loader: Site Kit loads the container. In wp_head, so the defaults precede it.
 *
 * @return void
 */
function cmplz_google_site_kit_tagmanager_script() {
	// Runs under the WP Consent API too: Site Kit only signals consent when its own Consent Mode is on.
	if ( ! cmplz_consent_mode() ) {
		return;
	}

	// The banner loader skips its frontend output here too.
	if ( cmplz_is_pagebuilder_preview() ) {
		return;
	}

	// Same template choice as get_tagmanager_script(), minus the loader. No placeholders to fill.
	$template = cmplz_get_option( 'cmplz-tm-template' ) === 'yes' ? '-template' : '';
	$script   = cmplz_get_template( "statistics/sitekit-tag-manager-consent-mode$template.js" );
	if ( empty( $script ) ) {
		return;
	}

	// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Raw JS template output; escaping would corrupt it.
	echo '<!-- Site Kit Tag Manager Consent Mode script Complianz GDPR/CCPA -->' . "\n";
	echo '<script data-category="functional" id="cmplz-site-kit-tagmanager-consent-mode">' . apply_filters( 'cmplz_script_filter', $script ) . '</script>' . "\n";
	// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Add Google Site Kit script for consent mode
 *
 * @return void
 */
function cmplz_google_sitekit_script() {
	// Don't run integration if WP Consent API is active.
	if ( defined( 'WP_CONSENT_API_VERSION' ) ) {
		return;
	}

	if ( ! cmplz_consent_mode() ) {
		return;
	}
	ob_start();
	?>
	<script>
		<?php
		$statistics = cmplz_get_option( 'compile_statistics' );
		$script     = '';
		if ( 'google-analytics' === $statistics ) {
			$enable_tcf_support = cmplz_tcf_active() ? 'true' : 'false';
			$ads_data_redaction = 'yes' === cmplz_get_option( 'cmplz-gtag-ads_data_redaction' ) ? 'true' : 'false';
			$urlpassthrough     = 'yes' === cmplz_get_option( 'cmplz-gtag-urlpassthrough' ) ? 'true' : 'false';
			$script             = cmplz_get_template( 'statistics/gtag-consent-mode-sitekit.js' );
			$script             = str_replace( array( '{enable_tcf_support}', '{ads_data_redaction}', '{url_passthrough}' ), array( $enable_tcf_support, $ads_data_redaction, $urlpassthrough ), $script );
		}
		echo apply_filters( 'cmplz_script_filter', $script );
		?>
	</script>
	<?php
	$script = ob_get_clean();
	$script = str_replace( array( '<script>', '</script>' ), '', $script );
	wp_add_inline_script( 'google_gtagjs', $script, 'before' );
}
add_action( 'wp_enqueue_scripts', 'cmplz_google_sitekit_script', 100 );
/**
 * Make sure there's no warning about configuring GA anymore
 *
 * @param array $warnings The warnings array.
 *
 * @return array The filtered warnings array.
 */
function cmplz_google_site_filter_warnings( $warnings ) {
	unset( $warnings['ga-needs-configuring'] );
	unset( $warnings['gtm-needs-configuring'] );
	return $warnings;
}
add_filter( 'cmplz_warning_types', 'cmplz_google_site_filter_warnings' );

/**
 * Hide the stats configuration options when gadwp is enabled.
 *
 * @param array $fields The fields array.
 *
 * @return array The filtered fields array.
 */
function cmplz_google_site_kit_filter_fields( $fields ) {
	$index = cmplz_get_field_index( 'compile_statistics_more_info', $fields );
	if ( false !== $index ) {
		unset( $fields[ $index ]['help'] );
	}
	return cmplz_remove_field(
		$fields,
		array(
			'configuration_by_complianz',
			'gtag-basic-consent-mode',
			'additional_gtags_stats',
			'additional_gtags_marketing',
			'ua_code',
			'aw_code',
			'gtm_code',
			'gtm_code_head',
			'cmplz-tm-template',
		)
	);
}
add_filter( 'cmplz_fields', 'cmplz_google_site_kit_filter_fields', 200, 1 );

/**
 * Whitelist a string for the cookie blocker.
 *
 * @param array $whitelisted_script_tags The whitelisted script tags array.
 *
 * @return array The filtered whitelisted script tags array.
 */
function cmplz_google_site_kit_whitelisted_script_tags( $whitelisted_script_tags ) {
	$whitelisted_script_tags[] = 'google_gtagjs-js-after'; // String from inline script or source that should be whitelisted.
	$whitelisted_script_tags[] = 'google_gtagjs-js'; // String from inline script or source that should be whitelisted.
	$whitelisted_script_tags[] = 'id="cmplz-site-kit-tagmanager-consent-mode"'; // Our consent mode script. Matched with the attribute, as this is an unanchored substring match.
	return $whitelisted_script_tags;
}
add_filter( 'cmplz_whitelisted_script_tags', 'cmplz_google_site_kit_whitelisted_script_tags', 10, 1 );
