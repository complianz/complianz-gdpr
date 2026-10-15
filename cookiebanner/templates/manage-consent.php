<?php
/**
 * One manage consent button is rendered per consent type. The aria-controls value is
 * the only thing that distinguishes them, so cookiebanner/js/complianz.js uses it as
 * the selector that picks the button matching the visitor's consent type. Keep it in
 * sync with the banner id in cookiebanner.php; dropping it falls back to selecting on
 * the banner id alone, which on a multi consent type site reveals the wrong button.
 *
 * @package complianz
 */

?>
<button class="cmplz-btn cmplz-hidden cmplz-manage-consent manage-consent-{id}" aria-haspopup="dialog" aria-controls="cmplz-cookiebanner-{id}-{consent_type}">{manage_consent}</button>

