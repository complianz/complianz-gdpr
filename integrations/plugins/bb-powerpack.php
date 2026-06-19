<?php
defined( 'ABSPATH' ) or die();


if ( cmplz_uses_thirdparty('youtube') ) {

  if (!function_exists('cmplz_bb_powerpack_script')) {
      function cmplz_bb_powerpack_script() {
          ob_start();
          ?>
          <script>
  	        <?php //make sure the ajax loaded lightbox gets a blocked content container ?>
              setInterval(function () {
                  cmplz_set_blocked_content_container();
              }, 2000);

              function cmplz_bb_add_event(event, selector, callback ) {
                  document.addEventListener(event, e => {
                      if ( e.target.closest(selector) ) {
                          callback(e);
                      }
                  });
               }
  	        <?php //If the marketing is accepted on the video, the lightbox dismisses the video. We open it again. ?>
  			cmplz_bb_add_event('click', '.fancybox-container .cmplz-accept-category, .fancybox-container .cmplz-accept-service',
                  function(e){
                      document.querySelector('.pp-video-play-icon').click();
                  }
              )
          </script>
          <?php
          $script = ob_get_clean();
          $script = str_replace(array('<script>', '</script>'), '', $script);
          wp_add_inline_script( 'cmplz-cookiebanner', $script );
      }
      add_action( 'wp_enqueue_scripts', 'cmplz_bb_powerpack_script',PHP_INT_MAX );
    }

	function cmplz_beaver_builder_powerpack_whitelist( $tags ){
		$tags[] = 'pp-video-lightbox-content';
		return $tags;
	}

	add_filter( 'cmplz_whitelisted_script_tags', 'cmplz_beaver_builder_powerpack_whitelist');
}
