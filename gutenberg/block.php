<?php // phpcs:ignore Squiz.Commenting.FileComment.Missing

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue Gutenberg block assets for backend editor.
 *
 * @since 1.0.0
 */
function cmplz_editor_assets() {
	$asset_file = include plugin_dir_path( __FILE__ ) . 'build/index.asset.php';
	wp_enqueue_script(
		'cmplz-block',
		plugins_url( 'gutenberg/build/index.js', __DIR__ ),
		$asset_file['dependencies'],
		$asset_file['version'],
		true
	);

	wp_localize_script(
		'cmplz-block',
		'complianz',
		array(
			'site_url'                 => get_rest_url(),
			'cmplz_preview'            => CMPLZ_URL . 'assets/images/gutenberg-preview.png',
			'user_can_unfiltered_html' => current_user_can( 'unfiltered_html' ),
		)
	);

	wp_set_script_translations( 'cmplz-block', 'complianz-gdpr', CMPLZ_PATH . '/languages' );
}
add_action( 'enqueue_block_editor_assets', 'cmplz_editor_assets' );

/**
 * Enqueue block styles for both editor iframe and frontend.
 */
function cmplz_block_assets() {
	if ( ! is_admin() ) {
		return;
	}
	$load_css = cmplz_get_option( 'use_document_css', true );
	if ( $load_css ) {
		$v = filemtime( CMPLZ_PATH . 'assets/css/document.min.css' );
		wp_enqueue_style(
			'cmplz-block',
			CMPLZ_URL . 'assets/css/document.min.css',
			array( 'wp-edit-blocks' ),
			$v
		);
	} else {
		$v = filemtime( CMPLZ_PATH . 'assets/css/document-grid.min.css' );
		wp_enqueue_style(
			'cmplz-block',
			CMPLZ_URL . 'assets/css/document-grid.min.css',
			array( 'wp-edit-blocks' ),
			$v
		);
	}
}
add_action( 'enqueue_block_assets', 'cmplz_block_assets' );

register_block_type(
	'complianz/document',
	array(
		'render_callback' => 'cmplz_render_document_block',
	)
);
register_block_type(
	'complianz/consent-area',
	array(
		'render_callback' => 'cmplz_render_consent_area_block',
	)
);

/**
 * Handles the front end rendering of the complianz consent area block
 *
 * @param array  $attributes
 * @param string $content
 * @return string
 */
function cmplz_render_consent_area_block( $attributes, $content ) {
	$category            = isset( $attributes['category'] ) ? cmplz_sanitize_category( $attributes['category'] ) : 'marketing';
	$service             = isset( $attributes['service'] ) ? COMPLIANZ::$cookie_blocker->sanitize_service_name( $attributes['service'] ) : 'general';
	$post_id             = (int) $attributes['postId'];
	$block_id            = sanitize_title( $attributes['blockId'] );
	$placeholder_content = $attributes['placeholderContent'] ?? '';
	ob_start();
	?><div class="cmplz-consent-area cmplz-placeholder" data-post_id="<?php echo esc_attr( $post_id ); ?>" data-block_id="<?php echo esc_attr( $block_id ); ?>" data-category="<?php echo esc_attr( $category ); ?>" data-service="<?php echo esc_attr( $service ); ?>">
		<?php echo wp_kses_post( $placeholder_content ); ?>
	</div>
	<?php
	return ob_get_clean();
}
/**
 * Handles the front end rendering of the complianz block
 *
 * @param $attributes
 * @param $content
 * @return string
 */
function cmplz_render_document_block( $attributes, $content ): string {
	$html = '';
	if ( isset( $attributes['selectedDocument'] ) ) {
		if ( isset( $attributes['documentSyncStatus'] ) && 'unlink' === $attributes['documentSyncStatus'] && isset( $attributes['customDocument'] ) ) {
			$html = $attributes['customDocument'];
		} else {
			$type   = $attributes['selectedDocument'];
			$region = cmplz_get_region_from_legacy_type( $type );
			if ( $region ) {
				$type = str_replace( '-' . $region, '', $type );
			}
			$html = COMPLIANZ::$document->get_document_html( $type, $region );
		}
	}

	return $html;
}




