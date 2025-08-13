<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

add_filter( 'cmplz_known_script_tags', 'cmplz_recaptcha_script' );
function cmplz_recaptcha_script( $tags ) {
	if (defined('WPCF7_VERSION') && version_compare(WPCF7_VERSION, 5.4, '>=')) return $tags;

	if (cmplz_get_option('block_recaptcha_service') === 'yes'){
		$tags[] = array(
				'name' => 'google-recaptcha',
				'placeholder' => 'google-recaptcha',
				'category' => 'marketing',
				'urls' => array(
						'google.com/recaptcha',
						'grecaptcha',
						'recaptcha.js',
						'recaptcha/api',
						'apis.google.com/js/platform.js',
						'gstatic.com/recaptcha',
				),
				'enable_placeholder' => '1',
				'placeholder_class' => 'recaptcha-invisible,g-recaptcha',
		);
  	}
	return $tags;
}

/**
 * Add some custom css for the placeholder
 */

add_action( 'cmplz_banner_css', 'cmplz_recaptcha_css' );
function cmplz_recaptcha_css() {
	if (defined('WPCF7_VERSION') && version_compare(WPCF7_VERSION, 5.4, '>=')) return;

	?>
		.cmplz-blocked-content-container.recaptcha-invisible,
		.cmplz-blocked-content-container.g-recaptcha {
			max-width: initial !important;
			height: 80px !important;
			margin-bottom: 20px;
		}

		@media only screen and (max-width: 400px) {
			.cmplz-blocked-content-container.recaptcha-invisible,
			.cmplz-blocked-content-container.g-recaptcha {
				height: 100px !important
			}
		}

		.cmplz-blocked-content-container.recaptcha-invisible .cmplz-blocked-content-notice,
		.cmplz-blocked-content-container.g-recaptcha .cmplz-blocked-content-notice {
			max-width: initial;
			padding: 7px;
		}
	<?php
}

/**
 * This empty function ensures Complianz recognizes that this integration has a placeholder
 * @return void
 *
 */
function cmplz_google_recaptcha_placeholder(){}
