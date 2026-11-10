import useFields from "../../Settings/Fields/FieldsData";
import UseBannerData from "./CookieBannerData";
import {useEffect, useState, useRef} from "@wordpress/element";
// import { RE2 } from 're2';
import DOMPurify from "dompurify";
import {getPurposes, filterArray, concatenateString} from "./tcf";
import useMenu from "../../Menu/MenuData";
/**
 * Render a help notice in the sidebar
 */
const CookieBannerPreview = () => {
	const {selectedSubMenuItem} = useMenu();
	const rootRef = useRef(null);
	const {fields, updateField, getFieldValue, getField, setChangedField, changedFields, fetchFieldsData, updateFieldsData, fieldsLoaded} = useFields();
	const {setBannerContainerClass, bannerContainerClass, cssLoading, cssLoaded, generatePreviewCss, pageLinks, selectedBanner, selectedBannerId, tcfActiveServerside, fetchBannerData, setBannerDataLoaded, bannerDataLoaded, bannerHtml, manageConsentHtml, consentType} = UseBannerData();
	const [timer, setTimer] = useState(null)
	const [bannerDataUpdated, setBannerDataUpdated] = useState(0)
	const [bannerToFieldsSynced, setBannerToFieldsSynced] = useState(false)
	const [tcfActive, setTcfActive] = useState(false);
	const [tcfStatusValidated, setTcfStatusValidated] = useState(false);
	const [InitialCssGenerated, setInitialCssGenerated] = useState(false);

	useEffect(() => {
		if ( !fieldsLoaded || !bannerDataLoaded ) {
			return;
		}

		let active = getFieldValue('uses_ad_cookies_personalized') === 'tcf' || getFieldValue('uses_ad_cookies_personalized') === 'yes';
		if (getFieldValue('uses_ad_cookies') === 'no') {
			active = false;
		}
		setTcfActive(active);
		setTcfStatusValidated(true);
	}, [ fieldsLoaded, bannerDataLoaded, getFieldValue('uses_ad_cookies_personalized') ]);

	useEffect  (  () => {
		loadRequiredData();
	}, [window.location.hash, fieldsLoaded, bannerDataLoaded ])

	//reload fields if tcfActive status has changed
	useEffect (  () => {
		if (!tcfStatusValidated) {
			return;
		}
		loadRequiredData();
	}, [tcfActive])

	useEffect (  () => {
		if (!tcfStatusValidated) {
			return;
		}
		if (tcfActive === tcfActiveServerside) {
			return;
		}
		loadRequiredData();
	}, [tcfActive, tcfActiveServerside, selectedBanner])

	useEffect (  () => {
		if (!tcfStatusValidated) {
			return;
		}
		if (tcfActive === tcfActiveServerside) {
			return;
		}

		loadRequiredData();
	}, [selectedBanner])

	//also reload if ab testing is enabled, to get the second banner that may have been added just now.
	useEffect (  () => {
		loadRequiredData();
	}, [ getFieldValue('a_b_testing_buttons') ])

	useEffect ( () => {
		if ( bannerDataLoaded ) {
			updateField('consent_type', consentType );
			setChangedField('consent_type', consentType);
		}
	}, [consentType])

	useEffect ( () => {
		updateFieldsData(selectedSubMenuItem);

	}, [ getFieldValue('consent_type')] )

	//keep consenttype in sync
	useEffect ( () => {
		if (consentType === '') {
			return;
		}
		updateField('consent_type', consentType )
	}, [consentType])

	/**
	 * On fields change, update the values in the banner objects
	 */

	useEffect (  () => {
		syncFieldsToBanner();
		setBannerDataUpdated(bannerDataUpdated+1);
	}, [changedFields] )

	useEffect (  () => {
		if ( selectedBannerId>0 ) {
			syncBannerToFields();
			setBannerDataUpdated(bannerDataUpdated+1);
		}
	},[selectedBannerId, consentType, bannerDataLoaded, tcfActive]);

	//when the banner data is loaded, or critical settings have changed, sync the banner to the fields
	useEffect(() => {
		syncBannerToFields();
	}, [bannerDataLoaded, getFieldValue('consent_type'), getFieldValue('uses_ad_cookies_personalized'), getFieldValue('uses_ad_cookies')]);

	useEffect (  () => {
		//wait with generating the preview until we have synced the data at least once.
		if ( selectedBannerId>0 && bannerToFieldsSynced ) {
			loadBannerPreview();
		}
	},	[bannerDataUpdated, selectedBannerId, tcfActive, bannerToFieldsSynced]);

	const loadRequiredData = async () => {
		await fetchBannerData();
		await fetchFieldsData(selectedSubMenuItem);
		updateField('consent_type', consentType )
		setBannerDataUpdated(bannerDataUpdated+1);
	}

	/**
	 * Fill fields with data from the selected banner
	 */

	const syncBannerToFields = () => {
		// fill fields with data from selected banner, default the default banner
		if ( !bannerDataLoaded ) {
			return;
		}

		let bannerFields = getBannerFields();
		for ( const field of bannerFields ) {
			if ( selectedBanner.hasOwnProperty(field.id) ) {
				//load defaults
				let value = selectedBanner[field.id];
				//setting defaults does not seem logical, and causes issues when clearing fields
				//set defaults if empty. Checkboxes are '0' when false.
				// if (  (!value || value.length===0 || (value.hasOwnProperty('text') && value['text'].length===0 ) ) ) {
				// 	value = field.default;
				// }

				if ( getFieldValue(field.id)!==value ) {
					updateField(field.id, value)
				}
			}
		}

		setBannerToFieldsSynced(true);
		updateField('manage_consent', selectedBanner['revoke'] );
	}

	/**
	 * Update selected banner with changed fields data
	 */
	const syncFieldsToBanner = () => {
		// fill fields with data from selected banner, default the default banner
		let bannerFields = getBannerFields();
		for ( const field of bannerFields ) {
			if ( selectedBanner.hasOwnProperty(field.id) && selectedBanner[field.id] !== field.value ) {
				selectedBanner[field.id] = field.value;
			}
		}
	}

	/**
	 * delay rendering the preview if the user is still typing
	 */
	const updatePreview = async () => {
		clearTimeout(timer);
		let bannerFields =  getBannerFields();
		if ( !InitialCssGenerated ) {
			await generatePreviewCss(bannerFields);
			setInitialCssGenerated(true);
		} else {
			const newTimer = setTimeout(async () => {
				await generatePreviewCss(bannerFields);
			}, 500)
			setTimer(newTimer)
		}
	}

	const loadBannerPreview = async () => {
		await updatePreview();

		if ( consentType === 'optin' ) {
			let widthChanged = validateBannerWidth();
			if ( widthChanged ) {
				await updatePreview();
			}
		}
	}

	useEffect(() => {
		if ( !tcfActive ) return;

		const rootElement = rootRef.current;
		if ( !rootRef.current ) {
			return;
		}

		// Query the DOM using the root element
		//if tcf, insert categories
		if ( consentType === 'optin' && rootElement) {
			let purposesField = getField('tcf_purposes');
			let purposes = filterArray(purposesField.options, purposesField.value);
			const srcMarketingPurposes = getPurposes('marketing', false);

			const srcStatisticsPurposes = getPurposes('statistics', false);
			const marketingPurposes = filterArray(purposes, srcMarketingPurposes);
			const statisticsPurposes = filterArray(purposes, srcStatisticsPurposes);
			let featuresField = getField('tcf_features');
			let features = filterArray(featuresField.options, featuresField.value);

			let specialFeaturesField = getField('tcf_specialFeatures');
			let specialFeatures = filterArray(specialFeaturesField.options, specialFeaturesField.value);

			let specialPurposesField = getField('tcf_specialPurposes');
			let specialPurposes = filterArray(specialPurposesField.options, specialPurposesField.value);

			const marketingPurposesContainer = rootElement.querySelector('.cmplz-tcf .cmplz-marketing .cmplz-description');
			const statisticsPurposesContainer = rootElement.querySelector('.cmplz-tcf .cmplz-statistics .cmplz-description');

			const featuresContainer = rootElement.querySelector('.cmplz-tcf .cmplz-features .cmplz-description');
			const specialFeaturesContainer = rootElement.querySelector('.cmplz-tcf .cmplz-specialfeatures .cmplz-description');
			const specialPurposesContainer = rootElement.querySelector('.cmplz-tcf .cmplz-specialpurposes .cmplz-description');

			let f = rootElement.querySelector('.cmplz-tcf .cmplz-features');
			let sp = rootElement.querySelector('.cmplz-tcf .cmplz-specialpurposes');
			let sf = rootElement.querySelector('.cmplz-tcf .cmplz-specialfeatures');
			let stp = rootElement.querySelector('.cmplz-tcf .cmplz-statistics');
			if (features.length === 0 && f) f.style.display = 'none';
			if (specialPurposes.length === 0 && sp ) sp.style.display = 'none';
			if (specialFeatures.length === 0 && sf) sf.style.display = 'none';
			if (statisticsPurposes.length === 0 && stp) stp.style.display = 'none';

			if (marketingPurposesContainer) marketingPurposesContainer.innerHTML = concatenateString(marketingPurposes);
			if (statisticsPurposesContainer) statisticsPurposesContainer.innerHTML = concatenateString(statisticsPurposes);
			if (featuresContainer) featuresContainer.innerHTML = concatenateString(features);
			if (specialFeaturesContainer) specialFeaturesContainer.innerHTML = concatenateString(specialFeatures);
			if (specialPurposesContainer) specialPurposesContainer.innerHTML = concatenateString(specialPurposes);
		}
	}, [tcfActive, bannerDataUpdated, bannerDataLoaded, consentType, cssLoading, fields ]);

	// const {RE2} = require('re2-wasm');
	const replace = (string, find, replace) => {
		if (string.indexOf(find) === -1) {
			return string;
		}
		let re = new RegExp(find, 'g');
		// Creating a RE2 regular expression object
		// let re = new RE2(find, 'g');
		// Using the RE2 object to perform the replacement
		return string.replace(re, replace);
	};

	const htmlDecode = (input) => {
		var doc = new DOMParser().parseFromString(input, "text/html");
		return doc.documentElement.textContent;
	}

	const setupClickEvents = (update) => {
		//default hide manage consent button
		let cmplz_manage_consent = document.querySelector('.cmplz-manage-consent');
		let cmplz_banner = document.querySelector('#cmplz-cookiebanner-container .cmplz-cookiebanner');
		if (cmplz_manage_consent) cmplz_manage_consent.style.display = 'none';

		//only do this on updates.
		if (!tcfActive && cmplz_banner && update && consentType === 'optin' && getFieldValue('use_categories') ==='view-preferences') {
			cmplz_banner.querySelector('.cmplz-view-preferences' ).style.display = 'block';
			cmplz_banner.querySelector('.cmplz-save-preferences' ).style.display = 'none';
		}

		document.addEventListener('click', e => {
			if ( e.target.closest('.cmplz-manage-consent' ) ) {
				if (cmplz_banner) cmplz_banner.style.removeProperty('display');
				if (cmplz_manage_consent) cmplz_manage_consent.style.display = 'none';
			}

			if (e.target.closest('.cmplz-close') || e.target.closest('.cmplz-accept') || e.target.closest('.cmplz-deny') ) {
				if (cmplz_banner) cmplz_banner.style.display = 'none';
				if (cmplz_manage_consent) cmplz_manage_consent.style.display = 'block';
			}

			if ( cmplz_banner && e.target.closest('.cmplz-view-preferences') ) {
				cmplz_banner.classList.add('cmplz-categories-visible');
				cmplz_banner.querySelector('.cmplz-categories' ).style.display = 'block';
				cmplz_banner.querySelector('.cmplz-categories' ).classList.add('cmplz-fade-in');
				cmplz_banner.querySelector('.cmplz-view-preferences' ).style.display = 'none';
				cmplz_banner.querySelector('.cmplz-save-preferences' ).style.display = 'block';
			}
			if ( cmplz_banner && e.target.closest('.cmplz-save-preferences') ) {
				cmplz_banner.classList.remove('cmplz-categories-visible');
				cmplz_banner.querySelector('.cmplz-categories' ).style.display = 'none';
				cmplz_banner.querySelector('.cmplz-categories' ).classList.remove('cmplz-fade-in');
				cmplz_banner.querySelector('.cmplz-view-preferences' ).style.display = 'block';
				cmplz_banner.querySelector('.cmplz-save-preferences' ).style.display = 'none';
			}
		});
	}

	const setUpBanner = () => {
		let bannerObject = document.querySelector('#cmplz-cookiebanner-container');
		if ( bannerObject) {
			bannerObject.querySelectorAll('.cmplz-links a:not(.cmplz-external), .cmplz-buttons a:not(.cmplz-external)').forEach(docElement => {
				docElement.classList.add('cmplz-hidden');
				for (let pageType in pageLinks ) {
					if ( pageLinks.hasOwnProperty(pageType) && docElement.classList.contains(pageType) ) {
						docElement.setAttribute('href', pageLinks[pageType]['url'] + docElement.getAttribute('data-relative_url'));
						if ( docElement.innerText === '{title}') {
							docElement.innerText = htmlDecode(pageLinks[pageType]['title']);
						}
						docElement.classList.remove('cmplz-hidden');
					}
				}
			});
		}
		setupClickEvents(false);
	}

	const getBannerFields = () => {
		let bannerFields =  fields.filter( field => field.data_target === 'banner');
		return bannerFields;
	}

	const validateBannerWidth = () => {
		if ( getFieldValue('position') === 'bottom' ) {
			return false;
		}

		// if TCF, skip
		if (tcfActive) {
			return false;
		}

		if ( getFieldValue('disable_width_correction') === true ) {
			return false;
		}

		if (!document.querySelector('.cmplz-categories')) {
			return;
		}
		//temporarily set cats visibility to visible to be able to measure
		document.querySelector('.cmplz-categories').style.display = 'block';
		//check if cats width is ok
		let cats_width = document.querySelector('.cmplz-categories').offsetWidth;
		document.querySelector('.cmplz-categories').style.display = 'none';

		let message_width = document.querySelector('.cmplz-message').offsetWidth;
		let banner_width = document.querySelector('.cmplz-cookiebanner').offsetWidth;
		let max_banner_change = banner_width * 1.3;
		let new_width_cats = 0;
		let new_width_btns = 0;
		let banner_padding= false;
		let padding_left = window.getComputedStyle(document.querySelector('.cmplz-cookiebanner'), null).getPropertyValue('padding-left');
		let padding_right = window.getComputedStyle(document.querySelector('.cmplz-cookiebanner'), null).getPropertyValue('padding-left');

		//check if the banner padding is in px, and if so get it as int
		if (padding_left.indexOf('px')!==-1 && padding_right.indexOf('px')!==-1){
			banner_padding = parseInt(padding_left.replace('px', '')) + parseInt(padding_right.replace('px', ''));
		}

		if ( cats_width>0 && banner_padding ){
			if ( banner_width-banner_padding > cats_width ) {
				let difference = banner_width-42 - cats_width;
				new_width_cats =  parseInt(banner_width) + parseInt(difference);
			}
		}

		let btn_width = 0;
		btn_width = document.querySelectorAll('.cmplz-buttons .cmplz-btn').offsetWidth;
		if (btn_width > message_width) {
			let difference = btn_width - 42 - message_width;
			new_width_btns = parseInt(btn_width) + parseInt(difference);
		}

		let new_width = 0;
		if (new_width_btns > new_width_cats ) {
			new_width = new_width_btns;
		} else {
			new_width = new_width_cats;
		}
		if ( new_width > banner_width && new_width < max_banner_change ) {
			if(new_width % 2 !== 0) new_width++;
			updateField('banner_width', new_width);
			return true;
		}
		return false;
	}

	const convertLegacyFields = (fieldId) => {
		//conversion of legacy fieldnames
		let mapping = {
			'use_logo': 'logo',
			'category_all': 'category_marketing',
			'category_stats': 'category_statistics',
			'category_prefs': 'category_preferences',
			'accept_informational': 'accept_optout',
			'accept': 'accept_optin',
			'view_preferences': 'manage_options',
			'save_preferences': 'save_settings',
		}

		if (mapping.hasOwnProperty(fieldId)) {
			return mapping[fieldId];
		}

		return fieldId;
	}

	let hidePreview = getFieldValue('hide_preview')==1 || getFieldValue('disable_cookiebanner')==1;
	if ( !bannerDataLoaded  ||  !cssLoaded || hidePreview || !bannerToFieldsSynced ) {
		return (<></>)
	}

	//render banner with this data
	let resultHtml = bannerHtml;
	let resultManageConsentHtml = manageConsentHtml;
	let bannerFields = getBannerFields();
	resultHtml = replace( resultHtml, '{consent_type}', consentType );
	resultHtml = replace( resultHtml, '{id}', selectedBanner.ID );
	let vendorCount = consentType==='optin' ? 643 : '';
	resultHtml = replace( resultHtml, '{vendor_count}', vendorCount );
	resultManageConsentHtml = replace( resultManageConsentHtml, '{id}', selectedBanner.ID );
	for ( const field of bannerFields ) {
		if (field.id==='title') {
			continue;
		}
		let fieldId = convertLegacyFields(field.id);
		if ( selectedBanner.hasOwnProperty(field.id) ) {
			let fieldValue = selectedBanner[field.id];
			if ( field.type === 'text_checkbox' && fieldValue && fieldValue.hasOwnProperty('text') ) {
				resultHtml = replace(resultHtml, '{' + fieldId + '}', fieldValue['text']);
			} else if (field.type==='banner_logo'){
				let replaceLogo = selectedBanner.logo_options[fieldValue] ? selectedBanner.logo_options[fieldValue] : '';
				resultHtml = replace( resultHtml, '{'+fieldId+'}', replaceLogo );

			} else {
				resultHtml = replace( resultHtml, '{'+fieldId+'}', fieldValue );
			}
		}

		if ( field.id === 'revoke') {
			resultManageConsentHtml = replace( resultManageConsentHtml, '{manage_consent}', selectedBanner['revoke'] );
		}

	}


	setUpBanner();
	return (
		<>
			<div id="cmplz-preview-banner-container" ref={rootRef}>
				<div id="cmplz-cookiebanner-container"
					 className={bannerContainerClass} dangerouslySetInnerHTML={{ __html: DOMPurify.sanitize(resultHtml) }}>
				</div> {/* nosemgrep: react-dangerouslysetinnerhtml */}
				<div id="cmplz-manage-consent" data-nosnippet="true"
					 dangerouslySetInnerHTML={{ __html: DOMPurify.sanitize(resultManageConsentHtml) }} >{/* nosemgrep: react-dangerouslysetinnerhtml */}
				</div>
			</div>
		</>
	);
}

export default CookieBannerPreview
