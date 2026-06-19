import {useState, useEffect, memo} from "@wordpress/element";
import useFields from '../../Settings/Fields/FieldsData';
import {__} from '@wordpress/i18n';
import Icon from "../../utils/Icon";
import LabelWrapper from "./LabelWrapper";
import DOMPurify from "dompurify";
import Placeholder from "../../Placeholder/Placeholder";

const COMPONENT_MAP = {
	'multicheckbox': {
		componentPath: 'Settings/Inputs/CheckboxGroup',
	},
	'checkbox': {
		componentPath: 'Settings/Inputs/SwitchInput',
	},
	'radio': {
		componentPath: 'Settings/Inputs/RadioGroup',
	},
	'select': {
		componentPath: 'Settings/Inputs/SelectInput',
	},
	'placeholder_preview': {
		componentPath: 'Settings/PlaceholderPreview/PlaceholderPreview',
	},
	'text': {
		componentPath: 'Settings/Inputs/TextInput',
	},
	'password': {
		componentPath: 'Settings/Inputs/PasswordInput',
	},
	'textarea': {
		componentPath: 'Settings/Inputs/TextAreaInput',
	},
	'phone': {
		componentPath: 'Settings/Inputs/PhoneInput',
	},
	'email': {
		componentPath: 'Settings/Inputs/EmailInput',
	},
	'number': {
		componentPath: 'Settings/Inputs/NumberInput',
	},
	'url': {
		componentPath: 'Settings/Inputs/URLInput',
	},
	'text_checkbox': {
		componentPath: 'Settings/Inputs/TextSwitchInput',
	},
	'statistics': {
		componentPath: 'Statistics/Statistics',
	},
	'statistics-feedback': {
		componentPath: 'Statistics/StatisticsFeedback',
	},
	'import': {
		componentPath: 'Settings/Export/ImportControl',
	},
	'export': {
		componentPath: 'Settings/Export/ExportControl',
	},
	'integrations-services': {
		componentPath: 'Settings/Integrations/ServicesControl',
	},
	'integrations-plugins': {
		componentPath: 'Settings/Integrations/PluginsControl',
	},
	'integrations-script-center': {
		componentPath: 'Settings/Integrations/ScriptCenterControl',
	},
	'borderradius': {
		componentPath: 'Settings/Inputs/BorderInput',
		customProps: {
			units: ['px', '%', 'em', 'rem'],
		}
	},
	'borderwidth': {
		componentPath: 'Settings/Inputs/BorderInput',
	},
	'colorpicker': {
		componentPath: 'Settings/ColorPicker/ColorPickerControl',
	},
	'wcag_contrast_checker': {
		componentPath: 'Settings/WcagContrastChecker/WcagContrastChecker',
	},
	'document': {
		componentPath: 'Settings/DocumentControl',
	},
	'hidden': {
		componentPath: 'Settings/Inputs/InputHidden',
	},
	'editor': {
		componentPath: 'Settings/Editor/Editor',
	},
	'css': {
		componentPath: 'Settings/Editor/AceEditorControl',
	},
	'cookie_scan': {
		componentPath: 'Settings/CookieScan/CookieScanControl',
	},
	'finish': {
		componentPath: 'Settings/Finish/FinishControl',
	},
	'cookiedatabase_sync': {
		componentPath: 'Settings/Cookiedatabase/CookieDatabaseSyncControl',
	},
	'translation_management': {
		componentPath: 'Settings/Translations/TranslationManagementControl',
	},
	'translation_automation': {
		componentPath: 'Settings/Translations/TranslationAutomationControl',
	},
	'documents_menu': {
		componentPath: 'Settings/DocumentsMenu/DocumentsMenuControl',
	},
	'documents_menu_region_redirect': {
		componentPath: 'Settings/DocumentsMenu/DocumentsMenuControl',
	},
	'plugins_privacy_statements': {
		componentPath: 'Settings/PluginsPrivacyStatements/PluginsPrivacyStatementsControl',
	},
	'button': {
		componentPath: 'Settings/Inputs/Button',
	},
	'debug-data': {
		componentPath: 'Settings/Debug/DebugDataControl',
	},
	'banner-reset-button': {
		componentPath: 'Settings/CookieBannerPreview/ResetBannerButton',
	},
	'processing-agreements': {
		componentPath: 'Settings/ProcessingAgreements/ProcessingAgreementsControl',
	},
	'create-processing-agreements': {
		componentPath: 'Settings/ProcessingAgreements/CreateProcessingAgreements',
	},
	'data-breach-reports': {
		componentPath: 'Settings/DataBreachReports/DataBreachReportsControl',
	},
	'create-data-breach-reports': {
		componentPath: 'Settings/DataBreachReports/CreateDataBreachReport',
	},
	'proof-of-consent': {
		componentPath: 'Settings/ProofOfConsent/ProofOfConsentControl',
	},
	'records-of-consent': {
		componentPath: 'Settings/RecordsOfConsent/RecordsOfConsentControl',
	},
	'datarequests': {
		componentPath: 'Settings/DataRequests/DatarequestsControl',
	},
	'export-datarequests': {
		componentPath: 'Settings/DataRequests/ExportDatarequests',
	},
	'export-records-of-consent': {
		componentPath: 'Settings/RecordsOfConsent/ExportRecordsOfConsent',
	},
	'create-proof-of-consent': {
		componentPath: 'Settings/ProofOfConsent/CreateProofOfConsent',
	},
	'processors': {
		componentPath: 'Settings/Processors/ProcessorControl',
	},
	'thirdparties': {
		componentPath: 'Settings/ThirdParties/ThirdPartyControl',
	},
	'create-documents': {
		componentPath: 'Settings/CreateDocuments/CreateDocumentsControl',
	},
	'plugins_overview': {
		componentPath: 'Settings/PluginsOverviewControl',
	},
	'license': {
		componentPath: 'Settings/License/License',
	},
	'banner_logo': {
		componentPath: 'Settings/CookieBannerPreview/BannerLogoControl',
	},
	'support': {
		componentPath: 'Settings/Support/Support',
	},
	'security_measures': {
		componentPath: 'Settings/SecurityMeasures/SecurityMeasures',
	},
	'install-plugin': {
		componentPath: 'Settings/InstallPlugin/InstallPlugin',
	},
	'copy-multisite': {
		componentPath: 'Settings/Multisite/CopyMultisite',
	},
	'websitescan_status': {
		componentPath: 'Settings/WebSiteScan/WebSiteScanStatus',
	},
	'websitescan_actions': {
		componentPath: 'Settings/WebSiteScan/WebSiteScanActions',
	}
};

const Field = ({field, highLightField, isCustomField, customChangeHandler}) => {
	const {updateField, getFieldValue, setChangedField} = useFields();
	const [Component, setComponent] = useState(null);
	const [error, setError] = useState(null);
	let ExtendedComponentMap = {...COMPONENT_MAP};
	// add customProps to button component extendedComponentMap
	if (field.type === 'button') {
		ExtendedComponentMap = {
			...COMPONENT_MAP,
			'button': {
				...COMPONENT_MAP.button,
				customProps: {
					action: field.action
				}
			}
		}
	}
	const ComponentInfo = ExtendedComponentMap[field.type];
	useEffect(() => {
		if (COMPONENT_MAP[field.type]) {
			import(`../../${COMPONENT_MAP[field.type].componentPath}`).then(
				(component) => {
					if (component.default) {
						setComponent(component.default);
					}
				}
			).catch((error) => {
				console.error(`Error loading component of type ${field.type}: ${error}`);
			});
		}
	}, [field.type]);

	const onChangeHandler = (fieldValue) => {
		if (error) setError(null);
		if ( field.required && ((Array.isArray(fieldValue) && fieldValue.every(item => item === '')) || fieldValue === '') ) {
			onError('required');
		}
		if ( isCustomField ){
			customChangeHandler(field.id, fieldValue);
		} else {
			updateField(field.id, fieldValue);
			setChangedField(field.id, fieldValue);
		}
	};

	const onError = (error) => {
		const availableErrors = {
			'required': __('This field is required', 'complianz-gdpr'),
			'invalid_url': __('Please enter a valid URL', 'complianz-gdpr'),
			'invalid_email': __('Please enter a valid email address', 'complianz-gdpr'),
			'invalid_number': __('Please enter a valid number', 'complianz-gdpr'),
			'invalid_phone': __('Please enter a valid phone number', 'complianz-gdpr'),
		}
		if (availableErrors[error]) {
			setError(availableErrors[error]);
		} else {
			setError(null);
		}
	}

	if ( !field.conditionallyDisabled && ComponentInfo) {
		const errorClass = error ? 'cmplz-error' : '';
		const highLightClass = highLightField === field.id ? 'cmplz-field-wrap cmplz-highlight' : 'cmplz-field-wrap';
		const fieldClass = highLightClass + ' ' + errorClass + ' cmplz-' + field.type;

		const {customProps} = ComponentInfo;
		const commentStatusClass = field.comment_status ? 'cmplz-comment-' + field.comment_status : '';
		let label = field.label;
		if ( label ) {
			//allow for dynamic content in labels, like in the compile_statistics field, where the tool is replaced in the label.
			const pattern = /{cmplz_dynamic_content=(.*?)}/;
			const match = label.match(pattern);
			if ( match && match.length > 1) {
				const fieldName = match[1];
				let dynamicContent = getFieldValue(fieldName);
				//split string on - into array, make each word uppercase, and join again, with a space in between
				dynamicContent = dynamicContent.split('-').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');

				label = label.replace('{cmplz_dynamic_content=' + fieldName + '}', dynamicContent);
			}
		}
		return (
			<div className={fieldClass}>
				{ field.parent_label &&
					<LabelWrapper
						id={field.id}
						label={field.parent_label}
						tooltip={field.tooltip}
						premium={field.premium}
						required={field.required}
						type={field.type}
						isParentLabel={true}
					/>
				}

				{ field.type !== 'hidden' &&
					<LabelWrapper
						id={field.id}
						label={label}
						tooltip={field.tooltip}
						premium={field.premium}
						required={field.required}
						type={field.type}
						isParentLabel={false}
					/>
				}
				{Component && (
					<Component
						id={field.id}
						label={label}
						value={field.value}
						onChange={onChangeHandler}
						required={field.required}
						defaultValue={field.default}
						disabled={field.disabled}
						options={field.options}
						fields={field.fields}
						field={field}
						placeholder={field.placeholder}
						onError={onError}
						{...customProps}
					/>
				)}
				{ ! Component && (
						<Placeholder lines="1"></Placeholder>
				)}
				{field.comment && (
					<div
						className={"cmplz-comment "+commentStatusClass}
						dangerouslySetInnerHTML={ { __html: DOMPurify.sanitize(field.comment) } } >{/* nosemgrep: react-dangerouslysetinnerhtml */}
					</div>
				)}
				{error && (
					<div className="cmplz-error-text">
						<Icon name={'error'} size={13} color={'red'}/>
						<p>{error}</p>
					</div>

				)}
			</div>
		);
	}
	return null;
};
export default memo(Field);
