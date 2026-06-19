<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

add_filter( 'cmplz_known_script_tags', 'cmplz_hs_tracking_script' );
function cmplz_hs_tracking_script( $tags ) {
	$tags[] = 'track.hubspot.com';
	$tags[] = 'js.hs-analytics.net';

	return $tags;
}

/**
 * Sync the Complianz banner with the hubspot banner by clicking on the appropriate button when consent is given.
 */

function cmplz_hubspot_clicker() {
	if ( cmplz_get_option('block_hubspot_service') === 'yes' ) {
		ob_start();
		?>
		<script>
			document.addEventListener("cmplz_enable_category", cmplzHubspotScriptHandler);
			document.addEventListener("cmplz_status_change_service", cmplzHubspotScriptHandler);
			document.addEventListener("cmplz_status_change", cmplzHubspotScriptHandler);
			function cmplzHubspotScriptHandler(consentData) {
				let hubspotAcceptBtn = document.getElementById("hs-eu-confirmation-button");
				let hubspotDeclinetBtn = document.getElementById("hs-eu-decline-button");
				if ( consentData.detail.category === 'marketing' ) {
					if ( hubspotAcceptBtn != null ) {
						hubspotAcceptBtn.click();
					}
				} else {
					if ( hubspotDeclinetBtn != null && !consentData.detail.categories.includes("marketing") ) {
						hubspotDeclinetBtn.click();
					}
				}
				// if ( hubspotAcceptBtn ) {
				// 	hubspotAcceptBtn.parentNode.removeChild(hubspotAcceptBtn);
				// }
			}
		</script>
		<?php
		$script = ob_get_clean();
		$script = str_replace(array('<script>', '</script>'), '', $script);
		wp_add_inline_script( 'cmplz-cookiebanner', $script);
	}
}
add_action( 'wp_enqueue_scripts', 'cmplz_hubspot_clicker', PHP_INT_MAX);

/**
 * Add custom hubspot css
 */
function cmplz_hubspot_css() {
	if ( cmplz_get_option('block_hubspot_service') === 'yes' ){ ?>
		<style>
			div#hs-eu-cookie-confirmation {display: none;}
		</style>
	<?php
	}
}
add_action( 'wp_footer', 'cmplz_hubspot_css' );
