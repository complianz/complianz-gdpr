const revokeListeners = [];
window.addRevokeListener = (callback) => {
	revokeListeners.push(callback);
};
document.addEventListener("cmplz_revoke", function (e) {
	cmplz_set_cookie('cmplz_consent_mode', 'revoked', false );
	revokeListeners.forEach((callback) => {
		callback();
	});
});

const consentListeners = [];
/**
 * Called from GTM template to set callback to be executed when user consent is provided.
 * @param callback
 */
window.addConsentUpdateListener = (callback) => {
	consentListeners.push(callback);
};
document.addEventListener("cmplz_fire_categories", function (e) {
	var consentedCategories = e.detail.categories;
	const consent = {
		'security_storage': "granted",
		'functionality_storage': "granted",
		'personalization_storage':  cmplz_in_array( 'preferences', consentedCategories ) ? 'granted' : 'denied',
		'analytics_storage':  cmplz_in_array( 'statistics', consentedCategories ) ? 'granted' : 'denied',
		'ad_storage': cmplz_in_array( 'marketing', consentedCategories ) ? 'granted' : 'denied',
		'ad_user_data': cmplz_in_array( 'marketing', consentedCategories ) ? 'granted' : 'denied',
		'ad_personalization': cmplz_in_array( 'marketing', consentedCategories ) ? 'granted' : 'denied',
	};

	//don't use automatic prefixing, as the TM template needs to be sure it's cmplz_.
	let consented = [];
	for (const [key, value] of Object.entries(consent)) {
		if (value === 'granted') {
			consented.push(key);
		}
	}
	cmplz_set_cookie('cmplz_consent_mode', consented.join(','), false );
	consentListeners.forEach((callback) => {
		callback(consent);
	});
});
