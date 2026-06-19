import {useEffect, memo} from "@wordpress/element";
import {__} from '@wordpress/i18n';
import useFields from "../Fields/FieldsData";
import {UseTranslationsData} from "./useTranslationsData";
import './Translations.scss';

const TranslationManagementControl = () => {
	const { showSavedSettingsNotice} = useFields();
	const {
		translationStatus,
		translationDataLoading,
		saving,
		error,
		testResults,
		fetchTranslationStatus,
		fetchTranslations,
		testTranslations,
		clearError
	} = UseTranslationsData();

	useEffect(() => {
		fetchTranslationStatus();
	}, []);

	const handleTestTranslations = async () => {
		const result = await testTranslations();
		if (result.success) {
			showSavedSettingsNotice(result.message);
		}
	};

	const handleRetry = () => {
		clearError();
		fetchTranslationStatus();
	};

	// Helper function to get status display info
	const getStatusDisplay = (fileInfo) => {
		const statusConfig = {
			'no_translation_needed': {
				icon: '✓',
				text: __('No translation needed', 'complianz-gdpr'),
				className: 'cmplz-status-ok',
			},
			'available': {
				icon: '✓',
				text: __('Available', 'complianz-gdpr'),
				className: 'cmplz-status-ok',
			},
			'not_fetched': {
				icon: '⏳',
				text: __('Not fetched', 'complianz-gdpr'),
				className: 'cmplz-status-warning',
			},
			'not_supported': {
				icon: '✗',
				text: __('Not supported', 'complianz-gdpr'),
				className: 'cmplz-status-error',
			},
			'version_mismatch': {
				icon: '⚠',
				text: __('Version mismatch', 'complianz-gdpr'),
				className: 'cmplz-status-warning',
			},
			'invalid_format': {
				icon: '✗',
				text: __('Invalid format', 'complianz-gdpr'),
				className: 'cmplz-status-error',
			},
			'error': {
				icon: '✗',
				text: __('Error', 'complianz-gdpr'),
				className: 'cmplz-status-error',
			}
		};

		return statusConfig[fileInfo.status] || statusConfig['unknown'];
	};

	if (translationDataLoading) {
		return (
			<div className="cmplz-translation-management">
				<p>{__('Loading translation status...', 'complianz-gdpr')}</p>
			</div>
		);
	}

	if (error) {
		return (
			<div className="cmplz-translation-management">
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

	if (!translationStatus) {
		return (
			<div className="cmplz-translation-management">
				<p>{__('No status information available', 'complianz-gdpr')}</p>
			</div>
		);
	}

	return (
		<div className="cmplz-translation-management">
			{/* Language Files Status Table */}
			{translationStatus.language_files && Object.keys(translationStatus.language_files).length > 0 && (
				<div className="cmplz-language-files">
					<h4>{__('Language Files Status', 'complianz-gdpr')}</h4>
					<p>{__('If the translations have been recently fetched or updated, you may want to reload the page or clear the cache to see the latest changes.', 'complianz-gdpr')}</p>
					<table className="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th>{__('Language', 'complianz-gdpr')}</th>
								<th>{__('Status', 'complianz-gdpr')}</th>
								<th>{__('Last Updated', 'complianz-gdpr')}</th>
							</tr>
						</thead>
						<tbody>
							{Object.entries(translationStatus.language_files).map(([lang, fileInfo]) => {
								const statusDisplay = getStatusDisplay(fileInfo);
								return (
									<tr key={lang}>
										<td><strong>{lang}</strong></td>
										<td>
											<span className={statusDisplay.className}>
												{statusDisplay.icon} {statusDisplay.text}
											</span>
										</td>
										<td>
											{fileInfo.last_updated ? (
												new Date(fileInfo.last_updated * 1000).toLocaleString()
											) : (
												__('N/A', 'complianz-gdpr')
											)}
										</td>
									</tr>
								);
							})}
						</tbody>
					</table>
				</div>
			)}

			{/* Check Now Button */}
			<div style={{marginBottom: '20px', textAlign: 'center'}}>
				<button 
					className="button button-primary" 
					onClick={async () => {
						const result = await fetchTranslations();
						if (result.success) {
							showSavedSettingsNotice(result.message);
						}
					}}
					disabled={saving}
				>
					{saving ? __('Checking...', 'complianz-gdpr') : __('Check Now', 'complianz-gdpr')}
				</button>
			</div>

			{/* Hidden Test Section - Only shown if there are test results */}
			{/*testResults && (
				<div className="cmplz-test-section" style={{marginTop: '20px', padding: '10px', backgroundColor: '#f9f9f9', border: '1px solid #ddd'}}>
					<details>
						<summary style={{cursor: 'pointer', fontWeight: 'bold', color: '#666'}}>
							{__('Test Results (Click to expand)', 'complianz-gdpr')}
						</summary>
						<div style={{marginTop: '10px'}}>
							<button 
								className="button button-secondary" 
								onClick={handleTestTranslations}
								style={{marginBottom: '10px'}}
							>
								{__('Run Test Again', 'complianz-gdpr')}
							</button>
							<pre style={{backgroundColor: '#fff', padding: '10px', border: '1px solid #ccc', fontSize: '12px', overflow: 'auto'}}>
								{JSON.stringify(testResults, null, 2)}
							</pre>
						</div>
					</details>
				</div>
			)*/}

			{/* Hidden Test Button - Only for debugging */}
			{/*process.env.NODE_ENV === 'development' && (
				<div style={{marginTop: '10px', textAlign: 'center'}}>
					<button 
						className="button button-secondary" 
						onClick={handleTestTranslations}
						style={{fontSize: '11px', padding: '5px 10px'}}
					>
						{__('Test Functionality (Dev)', 'complianz-gdpr')}
					</button>
				</div>
			)*/}
		</div>
	);
};

export default memo(TranslationManagementControl); 