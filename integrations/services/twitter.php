<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

add_filter( 'cmplz_known_script_tags', 'cmplz_twitter_script' );
function cmplz_twitter_script( $tags ) {
	$tags[] = array(
		'name' => 'twitter',
		'placeholder' => 'twitter',
		'category' => 'marketing',
		'urls' => array(
			'platform.twitter.com',
			'twitter-widgets.js',
			'ads-twitter.com',
		),
		'enable_placeholder' => '1',
		'placeholder_class' => 'twitter-tweet,twitter-timeline',
	);
	return $tags;
}

/**
 * Add some custom css for the placeholder
 */

add_action( 'cmplz_banner_css', 'cmplz_twitter_css' );
function cmplz_twitter_css() {
	?>
	.twitter-tweet.cmplz-blocked-content-container {padding: 10px 40px;}
	<?php
}

function cmplz_add_twitter_js() {
	ob_start();
	$script = "
	let cmplzBlockedContent = document.querySelector('.cmplz-blocked-content-notice');
	if ( cmplzBlockedContent) {
	        cmplzBlockedContent.addEventListener('click', function(event) {
            event.stopPropagation();
        });
	}
    ";
	ob_get_clean();
	wp_add_inline_script( 'cmplz-cookiebanner', $script);
}
add_action( 'wp_enqueue_scripts', 'cmplz_add_twitter_js', PHP_INT_MAX);

/**
 * This empty function ensures Complianz recognizes that this integration has a placeholder
 * @return void
 *
 */
function cmplz_twitter_placeholder(){}
