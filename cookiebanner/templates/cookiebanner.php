<?php
/**
 * For each activate consenttype a banner is created
 * If A/B testing is enabled, each banner is rendered per consenttype as well.
 */
?>
<div id="cmplz-cookiebanner-{id}-{consent_type}" class="cmplz-cookiebanner cmplz-hidden banner-{id} {title_class} {consent_type} cmplz-{position} cmplz-categories-type-{use_categories}" aria-modal="true" data-nosnippet="true" role="dialog" aria-live="polite" aria-labelledby="cmplz-header-{id}-{consent_type}" aria-describedby="cmplz-message-{id}-{consent_type}">
	<div class="cmplz-header">
		<div class="cmplz-logo">{logo}</div>
		<div class="cmplz-title" id="cmplz-header-{id}-{consent_type}">{header}</div>
		<div class="cmplz-close" tabindex="0" role="button" aria-label="<?php esc_attr_e( "Close dialog", "complianz-gdpr" ) ?>">
			<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="times" class="svg-inline--fa fa-times fa-w-11" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 352 512"><path fill="currentColor" d="M242.72 256l100.07-100.07c12.28-12.28 12.28-32.19 0-44.48l-22.24-22.24c-12.28-12.28-32.19-12.28-44.48 0L176 189.28 75.93 89.21c-12.28-12.28-32.19-12.28-44.48 0L9.21 111.45c-12.28 12.28-12.28 32.19 0 44.48L109.28 256 9.21 356.07c-12.28 12.28-12.28 32.19 0 44.48l22.24 22.24c12.28 12.28 32.2 12.28 44.48 0L176 322.72l100.07 100.07c12.28 12.28 32.2 12.28 44.48 0l22.24-22.24c12.28-12.28 12.28-32.19 0-44.48L242.72 256z"></path></svg>
		</div>
	</div>

	<div class="cmplz-divider cmplz-divider-header"></div>
	<div class="cmplz-body">
		<div class="cmplz-message" id="cmplz-message-{id}-{consent_type}">{message_{consent_type}}</div>
		<!-- categories start -->
		<div class="cmplz-categories">
			<div class="cmplz-category cmplz-functional">
				<div class="cmplz-category-header">
					<span class="cmplz-category-title" id="cmplz-title-functional-{id}-{consent_type}">{category_functional}</span>
					<span class='cmplz-always-active'>
						<span class="cmplz-banner-checkbox">
							<input type="checkbox"
								   id="cmplz-functional-{consent_type}"
								   data-category="cmplz_functional"
								   class="cmplz-consent-checkbox cmplz-functional"
								   size="40"
								   value="1"/>
							<label class="cmplz-label" for="cmplz-functional-{consent_type}"><span class="screen-reader-text">{category_functional}</span></label>
						</span>
						<?php esc_html_e( "Always active", "complianz-gdpr" )?>
					</span>
					<button class="cmplz-category-toggle"
							aria-expanded="false"
							aria-controls="cmplz-desc-functional-{id}-{consent_type}"
							aria-labelledby="cmplz-title-functional-{id}-{consent_type}">
						<span class="cmplz-icon cmplz-open">
							<svg aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" height="18"><path d="M224 416c-8.188 0-16.38-3.125-22.62-9.375l-192-192c-12.5-12.5-12.5-32.75 0-45.25s32.75-12.5 45.25 0L224 338.8l169.4-169.4c12.5-12.5 32.75-12.5 45.25 0s12.5 32.75 0 45.25l-192 192C240.4 412.9 232.2 416 224 416z"/></svg>
						</span>
					</button>
				</div>
				<div class="cmplz-description" id="cmplz-desc-functional-{id}-{consent_type}" hidden>
					<span class="cmplz-description-functional">{functional_text}</span>
				</div>
			</div>

			<div class="cmplz-category cmplz-preferences">
				<div class="cmplz-category-header">
					<span class="cmplz-category-title" id="cmplz-title-preferences-{id}-{consent_type}">{category_preferences}</span>
					<span class="cmplz-banner-checkbox">
						<input type="checkbox"
							   id="cmplz-preferences-{consent_type}"
							   data-category="cmplz_preferences"
							   class="cmplz-consent-checkbox cmplz-preferences"
							   size="40"
							   value="1"/>
						<label class="cmplz-label" for="cmplz-preferences-{consent_type}"><span class="screen-reader-text">{category_preferences}</span></label>
					</span>
					<button class="cmplz-category-toggle"
							aria-expanded="false"
							aria-controls="cmplz-desc-preferences-{id}-{consent_type}"
							aria-labelledby="cmplz-title-preferences-{id}-{consent_type}">
						<span class="cmplz-icon cmplz-open">
							<svg aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" height="18"><path d="M224 416c-8.188 0-16.38-3.125-22.62-9.375l-192-192c-12.5-12.5-12.5-32.75 0-45.25s32.75-12.5 45.25 0L224 338.8l169.4-169.4c12.5-12.5 32.75-12.5 45.25 0s12.5 32.75 0 45.25l-192 192C240.4 412.9 232.2 416 224 416z"/></svg>
						</span>
					</button>
				</div>
				<div class="cmplz-description" id="cmplz-desc-preferences-{id}-{consent_type}" hidden>
					<span class="cmplz-description-preferences">{preferences_text}</span>
				</div>
			</div>

			<div class="cmplz-category cmplz-statistics">
				<div class="cmplz-category-header">
					<span class="cmplz-category-title" id="cmplz-title-statistics-{id}-{consent_type}">{category_statistics}</span>
					<span class="cmplz-banner-checkbox">
						<input type="checkbox"
							   id="cmplz-statistics-{consent_type}"
							   data-category="cmplz_statistics"
							   class="cmplz-consent-checkbox cmplz-statistics"
							   size="40"
							   value="1"/>
						<label class="cmplz-label" for="cmplz-statistics-{consent_type}"><span class="screen-reader-text">{category_statistics}</span></label>
					</span>
					<button class="cmplz-category-toggle"
							aria-expanded="false"
							aria-controls="cmplz-desc-statistics-{id}-{consent_type}"
							aria-labelledby="cmplz-title-statistics-{id}-{consent_type}">
						<span class="cmplz-icon cmplz-open">
							<svg aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" height="18"><path d="M224 416c-8.188 0-16.38-3.125-22.62-9.375l-192-192c-12.5-12.5-12.5-32.75 0-45.25s32.75-12.5 45.25 0L224 338.8l169.4-169.4c12.5-12.5 32.75-12.5 45.25 0s12.5 32.75 0 45.25l-192 192C240.4 412.9 232.2 416 224 416z"/></svg>
						</span>
					</button>
				</div>
				<div class="cmplz-description" id="cmplz-desc-statistics-{id}-{consent_type}" hidden>
					<span class="cmplz-description-statistics">{statistics_text}</span>
					<span class="cmplz-description-statistics-anonymous">{statistics_text_anonymous}</span>
				</div>
			</div>

			<div class="cmplz-category cmplz-marketing">
				<div class="cmplz-category-header">
					<span class="cmplz-category-title" id="cmplz-title-marketing-{id}-{consent_type}">{category_marketing}</span>
					<span class="cmplz-banner-checkbox">
						<input type="checkbox"
							   id="cmplz-marketing-{consent_type}"
							   data-category="cmplz_marketing"
							   class="cmplz-consent-checkbox cmplz-marketing"
							   size="40"
							   value="1"/>
						<label class="cmplz-label" for="cmplz-marketing-{consent_type}"><span class="screen-reader-text">{category_marketing}</span></label>
					</span>
					<button class="cmplz-category-toggle"
							aria-expanded="false"
							aria-controls="cmplz-desc-marketing-{id}-{consent_type}"
							aria-labelledby="cmplz-title-marketing-{id}-{consent_type}">
						<span class="cmplz-icon cmplz-open">
							<svg aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" height="18"><path d="M224 416c-8.188 0-16.38-3.125-22.62-9.375l-192-192c-12.5-12.5-12.5-32.75 0-45.25s32.75-12.5 45.25 0L224 338.8l169.4-169.4c12.5-12.5 32.75-12.5 45.25 0s12.5 32.75 0 45.25l-192 192C240.4 412.9 232.2 416 224 416z"/></svg>
						</span>
					</button>
				</div>
				<div class="cmplz-description" id="cmplz-desc-marketing-{id}-{consent_type}" hidden>
					<span class="cmplz-description-marketing">{marketing_text}</span>
				</div>
			</div>
		</div><!-- categories end -->
		<?php do_action('cmplz_banner_after_categories' ) ?>
	</div>

	<div class="cmplz-links cmplz-information">
		<ul>
			<li><a class="cmplz-link cmplz-manage-options cookie-statement" href="#" data-relative_url="#cmplz-manage-consent-container"><?php esc_html_e( "Manage options", "complianz-gdpr" )?></a></li>
			<li><a class="cmplz-link cmplz-manage-third-parties cookie-statement" href="#" data-relative_url="#cmplz-cookies-overview"><?php esc_html_e( "Manage services", "complianz-gdpr" )?></a></li>
			<li><a class="cmplz-link cmplz-manage-vendors tcf cookie-statement" href="#" data-relative_url="#cmplz-tcf-wrapper"><?php esc_html_e(sprintf(__("Manage %s vendors","complianz-gdpr"),'{vendor_count}'))?></a></li>
			<li><a class="cmplz-link cmplz-external cmplz-read-more-purposes tcf" target="_blank" rel="noopener noreferrer nofollow" href="https://cookiedatabase.org/tcf/purposes/" aria-label="<?php esc_attr_e( "Read more about TCF purposes on Cookie Database", "complianz-gdpr" )?>"><?php esc_html_e( "Read more about these purposes", "complianz-gdpr" )?></a></li>
		</ul>
		<?php do_action("cmplz_after_links")?>
	</div>

	<div class="cmplz-divider cmplz-footer"></div>

	<div class="cmplz-buttons">
		<button class="cmplz-btn cmplz-accept">{accept_{consent_type}}</button>
		<button class="cmplz-btn cmplz-deny">{dismiss}</button>
		<button class="cmplz-btn cmplz-view-preferences">{manage_options}</button>
		<button class="cmplz-btn cmplz-save-preferences">{save_settings}</button>
		<a class="cmplz-btn cmplz-manage-options tcf cookie-statement" href="#" data-relative_url="#cmplz-manage-consent-container">{manage_options}</a>
		<?php do_action("cmplz_after_buttons")?>
	</div>


	<div class="cmplz-documents cmplz-links">
		<ul>
			<li><a class="cmplz-link cookie-statement" href="#" data-relative_url="">{title}</a></li>
			<li><a class="cmplz-link privacy-statement" href="#" data-relative_url="">{title}</a></li>
			<li><a class="cmplz-link impressum" href="#" data-relative_url="">{title}</a></li>
		</ul>
		<?php do_action("cmplz_after_documents")?>
	</div>
</div>
