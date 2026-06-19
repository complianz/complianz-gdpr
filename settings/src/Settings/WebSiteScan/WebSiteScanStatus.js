import UseWebSiteScanData from "./UseWebSiteScanData";
import { useEffect, useState, memo } from "@wordpress/element";
import Icon from "../../utils/Icon";
import { __ } from '@wordpress/i18n';
import "./WebSiteScanStatus.scss";

const statuses = {
	'enabled': {
		label: __("Enabled", "complianz-gdpr"),
		color: 'green',
	},
	'disabled': {
		label: __("Disabled", "complianz-gdpr"),
		color: 'grey',
	},
	'pending': {
		label: __("Pending", "complianz-gdpr"),
		color: 'orange',
	},
	'error': {
		label: __("Error", "complianz-gdpr"),
		color: 'red',
	},
}

// Token state isn't user-toggled — it exists/works or it doesn't. Use active/inactive
// (semantic for a credential) instead of enabled/disabled (which belongs to user choices).
const tokenStatuses = {
	'enabled': {
		label: __("Active", "complianz-gdpr"),
		color: 'green',
	},
	'disabled': {
		label: __("Inactive", "complianz-gdpr"),
		color: 'grey',
	},
	'pending': {
		label: __("Pending", "complianz-gdpr"),
		color: 'orange',
	},
	'error': {
		label: __("Error", "complianz-gdpr"),
		color: 'red',
	},
}

const WebSiteScanStatus = () => {
	const { tokenStatus, wscStatus, wscSignupDate, batchStats, requestActivationEmail } = UseWebSiteScanData();
	const isPremium = !! cmplz_settings.is_premium;
	const [canRequestAgain, setCanRequestAgain] = useState(false);

	useEffect(() => {
		if (!wscSignupDate) {
			setCanRequestAgain(false);
			return;
		}

		// check if the user can request the activation email again after 1 hour
		const currentTime = Math.floor(Date.now() / 1000);
		const oneHourInSeconds = 60 * 60;
		const timeDifference = currentTime - wscSignupDate;

		// update the state after 1 hour
		setCanRequestAgain(timeDifference >= oneHourInSeconds);
	}, [wscSignupDate, tokenStatus]);

	const handleRequestActivationEmail = (e) => {
		e.preventDefault();
		requestActivationEmail();
	}

	return (
		<div className="cmplz-wsc-status-container">
			{tokenStatus &&
				<div className="cmplz-wsc-status-row">
					<Icon name={'circle-check'} color={tokenStatuses[tokenStatus]?.color} size={16} />
					<span>{__("Token Status: ", "complianz-gdpr")}
						<strong>{tokenStatuses[tokenStatus]?.label}</strong>
					</span>
				</div>
			}

			{wscStatus &&
				<div className="cmplz-wsc-status-row">
					<Icon name={'circle-check'} color={statuses[wscStatus]?.color} size={16} />
					<span>{__("Website Scan: ", "complianz-gdpr")}
						<strong>{statuses[wscStatus]?.label}</strong>
					</span>
				</div>
			}
			{wscStatus && wscStatus === 'enabled' && !isPremium && (
				<div className="cmplz-wsc-status-row">
					<div className="cmplz-wsc-alert cmplz-wsc-alert-warning">
						<span className="cmplz-wsc-alert-title">{__("Website Scan only scans your homepage.", "complianz-gdpr")}</span>
						{" "}
						<a href={cmplz_settings.upgrade_link} target="_blank" rel="noopener noreferrer">
							{__("Upgrade to scan all pages individually.", "complianz-gdpr")}
						</a>
					</div>
				</div>
			)}

			{wscStatus && wscStatus === 'enabled' && isPremium && batchStats && batchStats.enabled && (
				<div className="cmplz-wsc-status-row">
					<Icon name={'circle-check'} color={'green'} size={16} />
					<span>{__("Website Scan — Background scanning: ", "complianz-gdpr")}
						<strong>{batchStats.done}</strong>{__(" scanned", "complianz-gdpr")}
						{batchStats.inflight > 0 && <> · <strong>{batchStats.inflight}</strong>{__(" in progress", "complianz-gdpr")}</>}
						{batchStats.queue > 0 && <> · <strong>{batchStats.queue}</strong>{__(" queued", "complianz-gdpr")}</>}
					</span>
				</div>
			)}

			{wscStatus && wscStatus === 'enabled' && isPremium && batchStats && !batchStats.enabled && (
				<div className="cmplz-wsc-status-row">
					<Icon name={'info'} color={'orange'} size={16} />
					<span>{__("Website Scan — Background scanning: ", "complianz-gdpr")}<strong>{__("Disabled", "complianz-gdpr")}</strong></span>
				</div>
			)}

			{wscStatus && wscStatus === 'pending' && (
				<div className="cmplz-wsc-status-row">
					{canRequestAgain ? (
						<div className="cmplz-wsc-alert cmplz-wsc-alert-warning">
							<span className="cmplz-wsc-alert-title">{__("Didn't receive the activation email? ", "complianz-gdpr")}</span>
							<a href="#" onClick={handleRequestActivationEmail}>
								{__("Request again!", "complianz-gdpr")}
							</a>
						</div>
					) : (
						<div className="cmplz-wsc-alert cmplz-wsc-alert-warning">
							<p className="cmplz-wsc-alert-title">{__('Check your email to activate the Website Scan!', 'complianz-gdpr')}</p>
							<p>{__("Didn't receive the activation email? Please wait 1 hour before requesting again.", "complianz-gdpr")}</p>
						</div>
					)}
				</div>
			)}
		</div>
	)
}
export default memo(WebSiteScanStatus)
