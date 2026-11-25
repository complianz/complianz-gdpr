import {__} from '@wordpress/i18n';

const TipsTricksFooter = () => {
	return (
		<a href="https://complianz.io/help/"
		   className="button button-default cmplz-flex-push-left"
		   target="_blank" rel="noopener noreferrer">{__('View all', 'complianz-gdpr')}</a>
	);
};

export default TipsTricksFooter;
