<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );
add_filter( 'cmplz_known_script_tags', 'cmplz_facebook_script' );
function cmplz_facebook_script( $tags ) {
	$tags[] = array(
				'name' => 'facebook',
				'category' => 'marketing',
				'urls' => array(
					'connect.facebook.net',
					'facebook.com/plugins',
				),
				'placeholder' => 'facebook',
				'enable_placeholder' => '1',
				'placeholder_class' => 'fb-page,fb-post',
		);
	return $tags;
}

add_filter( 'cmplz_image_tags', 'cmplz_facebook_image_tags' );

/**
 * Block the pixel image of the <noscript> fallback that comes with a hand-inserted Meta pixel. A visitor without
 * JavaScript cannot see the banner or give consent, but the image would still be requested.
 *
 * @param array $tags Image URLs to block.
 * @return array
 */
function cmplz_facebook_image_tags( $tags ) {
	$tags[] = 'facebook.com/tr?';

	return $tags;
}

/**
 * Add some custom css for the placeholder
 */

add_action( 'cmplz_banner_css', 'cmplz_facebook_css' );
function cmplz_facebook_css() {
	?>
		.cmplz-placeholder-element > blockquote.fb-xfbml-parse-ignore {
			margin: 0 20px;
		}
	<?php
}

/**
 * This empty function ensures Complianz recognizes that this integration has a placeholder
 * @return void
 *
 */
function cmplz_facebook_placeholder(){}
