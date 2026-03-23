<?php
defined( 'ABSPATH' ) || die( "you do not have access to this page!" );
function cmplz_clarity_consent_mode() {
    ?>
    <script>
        function cmplzCallClarity(method, params) {
            if (typeof window.clarity === 'function') {
                try {
                    window.clarity(method, params);
                } catch (e) {
                    console.warn('Clarity API error:', e);
                }
            }
        }

        function cmplzGetConsentFromEvent(e) {
            var d = e && e.detail && e.detail.categories ? e.detail.categories : [];
            var categories = Array.isArray(d) ? d : [];

            return {
                analyticsAllowed: categories.indexOf('statistics') !== -1,
                adsAllowed: categories.indexOf('marketing') !== -1
            };
        }

        function cmplzSendClarityConsent(analyticsAllowed, adsAllowed) {
            var status = function (b) { return b ? "granted" : "denied"; };
            cmplzCallClarity('consentv2', {
                analytics_Storage: status(!!analyticsAllowed),
                ad_Storage: status(!!adsAllowed)
            });
        }

        function cmplzEraseClarityCookies() {
            cmplzCallClarity('consent', false);
        }

        document.addEventListener('cmplz_fire_categories', function (e) {
            var consent = cmplzGetConsentFromEvent(e);
            cmplzSendClarityConsent(consent.analyticsAllowed, consent.adsAllowed);
        });

        document.addEventListener('cmplz_revoke', function (e) {
            var consent = cmplzGetConsentFromEvent(e);
            cmplzSendClarityConsent(consent.analyticsAllowed, consent.adsAllowed);
            if (!consent.analyticsAllowed && !consent.adsAllowed) {
                cmplzEraseClarityCookies();
            }
        });

    </script>
    <?php
}
add_action( 'wp_head', 'cmplz_clarity_consent_mode', 5);