export const startWscOnboarding = () => {
	const url = new URL( cmplz_settings.dashboard_url );
	url.searchParams.set( 'websitescan', '' );
	setTimeout( () => {
		window.location.href = url.href;
	}, 500 );
};

/**
 * Map the scan_upsell object from cmplz_settings to Alert component props.
 * All display properties (strings, icon, CTA) are defined PHP-side.
 *
 * @returns {object|null}
 */
export const getScanUpsellAlertProps = () => {
	const upsell = cmplz_settings.scan_upsell;
	if ( ! upsell ) return null;
	return {
		title:      upsell.title,
		subtitle:   upsell.subtitle,
		body:       upsell.body,
		icon:       upsell.icon,
		iconColor:  upsell.icon_color,
		ctaLabel:   upsell.cta_label,
		ctaUrl:     upsell.cta_url,
		ctaFieldId: upsell.cta_field_id,
	};
};