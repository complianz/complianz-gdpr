/**
 * Microsoft Clarity Consent Mode V2 integration with Complianz
 */
function callClarity(method, params) {
	if (typeof window.clarity === 'function') {
		try {
			window.clarity(method, params);
		} catch (e) {
			console.warn('Clarity API error:', e);
		}
	}
}

(function (c, l, a, r, i, t, y) {
	c[a] = c[a] || function () { (c[a].q = c[a].q || []).push(arguments); };
	t = l.createElement(r);
	t.async = 1;
	t.src = "https://www.clarity.ms/tag/" + i;
	y = l.getElementsByTagName(r)[0];
	y.parentNode.insertBefore(t, y);
})(window, document, "clarity", "script", "{site_ID}");

function getConsentFromEvent(e) {
	var d = e && e.detail && e.detail.categories ? e.detail.categories : [];
	var categories = Array.isArray(d) ? d : [];

	return {
		analyticsAllowed: categories.indexOf('statistics') !== -1,
		adsAllowed: categories.indexOf('marketing') !== -1
	};
}

function sendClarityConsent(analyticsAllowed, adsAllowed) {
	var status = function (b) { return b ? "granted" : "denied"; };
	callClarity('consentv2', {
		analytics_Storage: status(!!analyticsAllowed),
		ad_Storage: status(!!adsAllowed)
	});
}

function eraseClarityCookies() {
	callClarity('consent', false);
}

document.addEventListener('cmplz_fire_categories', function (e) {
	var consent = getConsentFromEvent(e);
	sendClarityConsent(consent.analyticsAllowed, consent.adsAllowed);
});

document.addEventListener('cmplz_revoke', function (e) {
	var consent = getConsentFromEvent(e);
	sendClarityConsent(consent.analyticsAllowed, consent.adsAllowed);
	if (!consent.analyticsAllowed && !consent.adsAllowed) {
		eraseClarityCookies();
	}
});
