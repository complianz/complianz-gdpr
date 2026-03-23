import { __ } from '@wordpress/i18n';

/**
 * Render a premium tag
 */
const Premium = ({premium, id}) => {

	if ( cmplz_settings.is_premium || !premium ) {
		 return null
	}

	const fallbackPremiumUrl = cmplz_settings.upgrade_link || 'https://complianz.io/pricing/';
	const url = premium.url && premium.url.trim() !== '' ? premium.url : fallbackPremiumUrl;

	return (
			<div className="cmplz-premium">
				<a target="_blank" rel="noopener noreferrer" href={url}>{__("Upgrade", "complianz-gdpr")}</a>
			</div>
	);

}

export default Premium
