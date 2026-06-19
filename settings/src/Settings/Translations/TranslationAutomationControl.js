import {useEffect, memo} from "@wordpress/element";
import {__} from '@wordpress/i18n';
import {UseTranslationsData} from "./useTranslationsData";
import './Translations.scss';

const TranslationAutomationControl = (props) => {
	const {
		cronStatus,
		translationDataLoading,
		error,
		fetchCronStatus,
		clearError
	} = UseTranslationsData();

	useEffect(() => {
		fetchCronStatus();
	}, []);

	const handleRetry = () => {
		clearError();
		fetchCronStatus();
	};

	if (translationDataLoading) {
		return (
			<div className="cmplz-translation-automation">
				<p>{__('Loading automation status...', 'complianz-gdpr')}</p>
			</div>
		);
	}

	if (error) {
		return (
			<div className="cmplz-translation-automation">
				<p className="cmplz-error">{error}</p>
				<button 
					className="button button-secondary" 
					onClick={handleRetry}
				>
					{__('Retry', 'complianz-gdpr')}
				</button>
			</div>
		);
	}

	if (!cronStatus) {
		return (
			<div className="cmplz-translation-automation">
				<p>{__('No automation status available', 'complianz-gdpr')}</p>
			</div>
		);
	}

	return (
		<div className="cmplz-translation-automation">
			<div className="cmplz-automation-status">
				<h4>{__('Translation Automation Status', 'complianz-gdpr')}</h4>
				
				<div className="cmplz-status-info">
					<p>
						<strong>{__('Status:', 'complianz-gdpr')}</strong> 
						{cronStatus.is_enabled ? (
							<span className="cmplz-status-enabled">✓ {__('Enabled', 'complianz-gdpr')}</span>
						) : (
							<span className="cmplz-status-disabled">✗ {__('Disabled', 'complianz-gdpr')}</span>
						)}
					</p>
					
					{cronStatus.is_enabled && (
						<>
							<p>
								<strong>{__('Interval:', 'complianz-gdpr')}</strong> {cronStatus.interval_display}
							</p>
							<p>
								<strong>{__('Next Run:', 'complianz-gdpr')}</strong> {cronStatus.next_run_formatted}
							</p>
						</>
					)}
				</div>
			</div>
		</div>
	);
};

export default memo(TranslationAutomationControl); 