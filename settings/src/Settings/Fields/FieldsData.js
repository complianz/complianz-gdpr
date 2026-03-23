import {create} from 'zustand';
import produce from 'immer';
import * as cmplz_api from "../../utils/api";
import {toast} from 'react-toastify';
import {__} from '@wordpress/i18n';
import useProgress from "../../Dashboard/Progress/ProgressData";
import {updateFieldsListWithConditions} from "../../utils/updateFieldsListWithConditions";
const fetchFields = () => {
  return cmplz_api.getFields().then((response) => {
	  return response;
  }).catch((error) => {
    console.error(error);
  });
}

const useFields = create(( set, get ) => ({
	fieldsLoaded: false,
	saving:false,
	preloadFields:[],
	error:false,
	fields: [],
	selectedFields: [],
	fieldNotices: [],
	fieldNoticesLoaded:false,
	changedFields:[],
	documentSettingsChanged:false,
	notCompletedRequiredFields:[],
	notCompletedFields:[],
	completableFields:[],
	allRequiredFieldsCompleted:false,
	nextButtonDisabled:false,
	highLightField: '',
	lockedByUser: 0,
	fetchingFieldNotices:false,
	setDocumentSettingsChanged(changed){
		set({documentSettingsChanged:changed})
	},
	setHighLightField: (highLightField) => set(state => ({ highLightField })),
	fetchAllFieldsCompleted: () => {
		let notCompletedRequiredFields = get().fields.filter(field => field.required && isCompletableField(field) && !field.conditionallyDisabled && ( field.never_saved || field.value.length===0 || !field.value) );
		let notCompletedFields = get().fields.filter(field => isCompletableField(field) && !field.conditionallyDisabled && (field.never_saved || field.value.length===0 || !field.value) );
		let completableFields = get().fields.filter(field => isCompletableField(field) && !field.conditionallyDisabled  );
		set(() => ({
			notCompletedRequiredFields: notCompletedRequiredFields,
			allRequiredFieldsCompleted: notCompletedRequiredFields.length===0,
			notCompletedFields:notCompletedFields,
			completableFields:completableFields,
		}));
	},
	setChangedField: (id, value) => {
		set(
			produce((state) => {
				//remove current reference
				const existingFieldIndex = state.changedFields.findIndex(field => {
					return field.id===id;
				});

				if (existingFieldIndex!==-1){
					state.changedFields[existingFieldIndex].value = value;
				} else {
					state.changedFields.push({id:id, value:value});
				}
			})
		)
	},
	showSavedSettingsNotice : (text) => {
		handleShowSavedSettingsNotice(text);
	},

	updateField: (id, value) => {
		let found=false;
		let index = false;

		set(
			produce((state) => {
				state.fields.forEach(function(fieldItem, i) {
					if (fieldItem.id === id ){
						index = i;
						found=true;
					}
				});
				if (index!==false) {
					state.fields[index].value = value;
					let field = state.fields[index];
					if (field.type==='document' || field.id==='regions'){
						state.documentSettingsChanged = true;
					}
				}
			})
		)
	},

	addHelpNotice : (id, label, text, title, url) => {
		// Create help object
		let help = {};
		help.label = label;
		help.text = text;
		if (title) help.title = title;

		// Apply referral tracking to complianz.io URLs
		if (url) {
			help.url = url.includes('complianz.io')
				? get().getReferralUrl('help', id, url)
				: url;
		}

		set(
			produce((state) => {
				const fieldIndex = state.fields.findIndex(field => {
					return field.id === id;
				});
				if (fieldIndex !== -1) {
					state.fields[fieldIndex].help = help;
				}
			})
		)
	},
	removeHelpNotice : (id) => {
		set(
			produce((state) => {
				const fieldIndex = state.fields.findIndex(field => {
					return field.id===id;
				});
				state.fields[fieldIndex].help = false;
			})
		)
	},

	/**
	 * Generate a URL with referral tracking parameters
	 *
	 * @param {string} type     - Link type: 'articles', 'menu', 'fields', 'warnings', 'help'
	 * @param {string} fieldId  - Identifier for utm_content parameter
	 * @param {string} url      - Base URL to enhance (defaults to pricing page)
	 * @returns {string}        - URL with UTM and referral parameters
	 */
	getReferralUrl: (type = 'articles', fieldId = '', url = '') => {
		const defaultUrl = 'https://complianz.io/pricing/';
		let baseUrl = url || defaultUrl;

		// Ensure trailing slash before adding query params
		if (!baseUrl.endsWith('/') && !baseUrl.includes('?')) {
			baseUrl += '/';
		}

		// Skip non-complianz URLs
		if (!baseUrl.includes('complianz.io')) {
			return baseUrl;
		}

		const referral = cmplz_settings.referral || {};
		const source = referral.source || 'cmplz_free';
		const refId = referral.ref_id || false;
		const hasPartner = referral.has_partner || false;

		// Build UTM parameters
		const params = new URLSearchParams();
		params.set('utm_source', source);
		params.set('utm_medium', `plugin_${type}`);
		if (fieldId) {
			params.set('utm_content', fieldId);
		}

		// Determine campaign and add partner referral
		if (hasPartner && type !== 'articles' && source === 'cmplz_free') {
			params.set('utm_campaign', 'partners');
			params.set('ref', refId);
			params.set('utm_id', refId);
		} else if (source === 'cmplz_free' && type !== 'articles') {
			params.set('utm_campaign', 'upgrade');
		} else {
			params.set('utm_campaign', 'articles');
		}

		// Append parameters to URL
		const separator = baseUrl.includes('?') ? '&' : '?';
		return baseUrl + separator + params.toString();
	},

	getFieldValue : (id) => {
		let fields = get().fields;
		for (const fieldItem of fields){
			if (fieldItem.id === id ){
				return fieldItem.value;
			}
		}
		return false;
	},
	getField : (id) => {
		let fields = get().fields;
		for (const fieldItem of fields){
			if (fieldItem.id === id ){
				return fieldItem;
			}
		}
		return false;
	},

	saveFields: async (selectedSubMenuItem, showSavedNotice = true, finish = false) => {
		let fields = get().fields;
		set({saving:true})
		const changedFields = get().changedFields;
		let saveFields = fields.filter(field => {
			const fieldIsChanged = changedFields.some(changedField => changedField.id === field.id );
			//check if this field has the current subMenuItem as menu_id: we include all visible fields
			const fieldIsVisible = field.menu_id && field.menu_id === selectedSubMenuItem;
			return fieldIsChanged || fieldIsVisible;
		});
		if (saveFields.length > 0 || finish) {
			//filter out banner fields
			saveFields = saveFields.filter( field => field.data_target !== 'banner');
			let response = cmplz_api.setFields(saveFields, finish).then((response) => {
				return response;
			});

			if (showSavedNotice ) {
				toast.promise(
					response,
					{
						pending: __('Saving settings...', 'complianz-gdpr'),
						success: __('Settings saved', 'complianz-gdpr'),
						error: __('Something went wrong', 'complianz-gdpr'),
					}
				);
			}
			await response.then((response) => {
				fields = response.fields;
				//if there's no value for fields, set saving to false and exit early.
				if ( !fields ) {
					fields = [];
					set(
						produce((state) => {
							state.saving = false;
						})
					);
					return;
				}
				let bannerFields = get().fields.filter( field => field.data_target === 'banner');
				//copy current values from bannerFields to the fields array
				for (let i = 0; i <fields.length; i++) {
					let field = fields[i];
					if (field.data_target === 'banner') {
						let bannerField = bannerFields.filter( bannerField => bannerField.id === field.id )[0];
						if (bannerField) {
							field.value = bannerField.value;
						}
					}
					fields[i] = field;
				}
				let fieldsWithPremium = applyPremiumSettings(fields);
				let conditionallyEnabledFields = updateFieldsListWithConditions(fieldsWithPremium);
				conditionallyEnabledFields = applyDefaults(conditionallyEnabledFields);
				let selectedFields = conditionallyEnabledFields.filter(field => field.menu_id === selectedSubMenuItem);

				set(
					produce((state) => {
						state.changedFields = [];
						state.fields = conditionallyEnabledFields;
						state.selectedFields = selectedFields;
						state.saving = false;
					})
				);
				const progressStore = useProgress.getState();
				progressStore.updateProgressData(response.notices, response.show_cookiebanner);
				set({fieldNotices:response.field_notices, fieldNoticesLoaded:true});
			});
		}
		if (showSavedNotice && saveFields.length === 0) {
			//nothing to save. show instant success.
			toast.promise(
				Promise.resolve(),
				{
					success: __('Settings saved', 'complianz-gdpr'),
				}
			);
		}
	},

	updateFieldsData: (selectedSubMenuItem) => {
		let fields = get().fields;
		fields = updateFieldsListWithConditions(fields);
		get().isNextButtonDisabled(fields, selectedSubMenuItem);
		set(
			produce((state) => {
				state.fields = fields;
			})
		)
	},
	//check if all required fields have been enabled. If so, enable save/continue button
	isNextButtonDisabled: (fields, selectedMenuItem) => {
		//get all fields with group_id this.props.group_id
		let fieldsOnPage = fields.filter(field => field.menu_id === selectedMenuItem);
		let requiredFields = fieldsOnPage.filter(field => field.required && !field.conditionallyDisabled && ( field.value.length===0 || !field.value ) );
		set({nextButtonDisabled: requiredFields.length > 0});
		return requiredFields.length > 0;
	},
	fetchFieldsData: async ( selectedSubMenuItem ) => {
		const { fields, error, locked_by, field_notices }   = await fetchFields();
		if (!Array.isArray(fields)){
			set(() => ({
				fieldsLoaded: true,
				fields: [],
				selectedFields: [],
				error:error,
				lockedByUser:locked_by,
			}))
		}
		let fieldsWithPremium = applyPremiumSettings(fields);
		let conditionallyEnabledFields = updateFieldsListWithConditions(fieldsWithPremium);
		conditionallyEnabledFields = applyDefaults(conditionallyEnabledFields);

		let selectedFields = conditionallyEnabledFields.filter(field => field.menu_id === selectedSubMenuItem);
		let preloadFields = [];
		const encounteredTypes = {};
		fields.forEach((field) => {
			if (
				(field.type === 'radio' || field.type === 'multicheckbox' || field.type === 'select') &&
				!encounteredTypes[field.type]
			) {
				preloadFields.push(field);
				encounteredTypes[field.type] = true;
			}
		});

		set(() => ({
			fieldsLoaded: true,
			fields: conditionallyEnabledFields,
			selectedFields: selectedFields,
			error:error,
			lockedByUser:locked_by,
			preloadFields:preloadFields,
			fieldNotices: field_notices,
			fieldNoticesLoaded:true,
		}));
	}
}));

export default useFields;



const applyPremiumSettings = (fields) => {
	if ( cmplz_settings.is_premium ) {
		for (const field of fields ){
			const premium = field.premium;
			if ( premium ) {
				field.disabled =premium.disabled ? premium.disabled : false;
				if (premium.default) field.default = premium.default;
				if (premium.label) field.label = premium.label;
				if (premium.comment) field.comment = premium.comment;
				if (premium.tooltip) field.tooltip = premium.tooltip;
				if (premium.react_conditions) field.react_conditions = premium.react_conditions;
			}
		}
	}
	return fields;
}



const applyDefaults = (fields) => {
	//foreach field, set the value to the default value if it's empty
	for (let i = 0; i <fields.length; i++) {
		let field = fields[i];
		//prevent 'false' as default value in the input fields
		if  (!field.value && field.type !== 'checkbox' ) field.value = '';

		//set a default if we have a default value, but no current value
		//and it wasn't saved before
		if ( field.default && field.never_saved ) {
			if (field.value.length===0 || !field.value ){
				field.value = field.default;
			}
		}

		fields[i] = field;
	}
	return fields;
}

const handleShowSavedSettingsNotice = (text) => {
	if (typeof text === 'undefined') {
		text = __( 'Settings saved', 'complianz-gdpr' );
	}
	toast.success(text);
}

const isCompletableField = (field) => {
	if (field.group_id === 'finish') {
		return false;
	}
	return field.type ==='radio' ||
		field.type === 'select' ||
		field.type === 'checkbox' ||
		field.type === 'multicheckbox' ||
		field.type === 'text' ||
		field.type === 'textarea' ||
		field.type === 'document';
}




