<?php
/**
 * Snapchat integration for Complianz.
 *
 * Registers Snapchat scripts and placeholder handling for GDPR compliance.
 *
 * @package Complianz_Integrations
 */

defined( 'ABSPATH' ) || die( 'you do not have access to this page!' );

add_filter( 'cmplz_known_script_tags', 'cmplz_snapchat_script' );
/**
 * Register Snapchat script tags for Complianz.
 *
 * @param array $tags Existing script tags.
 * @return array Modified script tags including Snapchat.
 */
function cmplz_snapchat_script( $tags ) {
	$tags[] = array(
		'name'               => 'snapchat',
		'category'           => 'marketing',
		'urls'               => array(
			'snapchat.com',
			'snapchat.com/embed.js',
			'snapchat.com/spotlight',
		),
		'placeholder'        => 'snapchat',
		'enable_placeholder' => '1',
		'placeholder_class'  => 'snapchat-embed',
	);
	return $tags;
}

/**
 * Add custom Snapchat CSS; hides the view on Snapchat button.
 *
 * @return void
 */
function cmplz_snapchat_css() {
	?>
		<style>    
		.snapchat-embed > div  {
			display: none !important;
		}
		</style>   
	<?php
}
add_action( 'wp_footer', 'cmplz_snapchat_css' );
add_action( 'cmplz_banner_css', 'cmplz_snapchat_css' );

/**
 * This empty function ensures Complianz recognizes that this integration has a placeholder.
 * @return void
 *
 */
function cmplz_snapchat_placeholder(){}