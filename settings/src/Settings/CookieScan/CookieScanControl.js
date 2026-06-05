import {useEffect, useState, memo} from "@wordpress/element";
import * as cmplz_api from "../../utils/api";
import {UseCookieScanData} from './CookieScanData';
import { __ } from '@wordpress/i18n';
import Panel from "../Panel";
import Icon from "../../utils/Icon";
import UseSyncData from "../Cookiedatabase/SyncData";
import useMenu from "../../Menu/MenuData";
import useFields from "../Fields/FieldsData";
import Details from "./Details";
import useProgress from "../../Dashboard/Progress/ProgressData";

const CookieScanControl = () => {
	const {setSyncProgress, fetchSyncProgressData} = UseSyncData();
	const {initialLoadCompleted, loading, nextPage, progress, setProgress, cookies, fetchProgress, lastLoadedIframe, setLastLoadedIframe} = UseCookieScanData();
	const [iframeLoading, setIframeLoading] = useState(false);
	const {addHelpNotice, fieldsLoaded} = useFields();
	const {selectedSubMenuItem } = useMenu();
	const {setProgressLoaded} = useProgress();
	const wscLocked = !cmplz_settings.wsc_is_authenticated;

	useEffect ( () => {
		if (lastLoadedIframe === nextPage) return;
		if ( iframeLoading ) return;
		setIframeLoading(true);
		loadIframe();
	}, [nextPage, lastLoadedIframe, iframeLoading]);

	useEffect (  () => {
		if ( wscLocked ) return;
		if ( !iframeLoading && !loading && progress <100 ) {
			fetchProgress();
		}
	}, [iframeLoading, loading, progress, wscLocked]);

	useEffect (  () => {
		if (!fieldsLoaded) return;
		if ( window.canRunAds === undefined ) {
			addHelpNotice('cookie_scan', 'warning',
				__("You are using an ad blocker. This will prevent most cookies from being placed. Please run the scan without an adblocker enabled.", 'complianz-gdpr'),
				__("Ad Blocker detected.", 'complianz-gdpr'),
				null,
			);
		}
		if ( doNotTrack() ) {
			addHelpNotice('cookie_scan', 'warning',
				__( "Your browser has the Do Not Track or Global Privacy Control setting enabled.","complianz-gdpr")+"&nbsp;"+__("This will prevent most cookies from being placed.","complianz-gdpr")+"&nbsp;"+__("Please run the scan with these browser options disabled.", 'complianz-gdpr' ),
				__("DNT or GPC enabled.", 'complianz-gdpr'),
				null,
			);
		}
	},[fieldsLoaded]);

	const doNotTrack = () => {
		let dnt = 'doNotTrack' in navigator && navigator.doNotTrack === '1';
		let gpc = 'globalPrivacyControl' in navigator && navigator.globalPrivacyControl;
		return gpc || dnt;
	}

    const loadIframe =  () => {
		if ( !nextPage ) {
			setIframeLoading(false);
			return;
		}
    	// Get a handle to the iframe element
    	let iframe = document.getElementById("cmplz_cookie_scan_frame");
    	if ( !iframe ) {
    		iframe = document.createElement('iframe');
    		iframe.setAttribute('id','cmplz_cookie_scan_frame');
    		iframe.classList.add('hidden');
    	}
    	iframe.setAttribute('src', nextPage);
    	// Check if loading is complete
    	iframe.onload = function (response) {
    		setTimeout(() => {
				setIframeLoading(false);
				setLastLoadedIframe(nextPage);
    		}, 200)
    	}
		document.body.appendChild(iframe);
    }

	const getStyles = () => {
		return Object.assign(
			{},
			{width: progress+"%"},
		);
	 }

	const Start = async () => {
		let data = {};
		data.scan_action = 'restart';
		setProgress(1);
		await cmplz_api.doAction('scan', data);
		await fetchProgress()
		if (progress===100) {
			await fetchSyncProgressData();
			if ( cookies.length>0 ){
				setSyncProgress(1);
			}
		}
	}

	const clearCookies = async () => {
		let data = {};
		data.scan_action = 'reset';
		setProgress(1);
		await cmplz_api.doAction('scan', data);
		await fetchProgress()
		if (progress===100) {
			//ensure a reload of the progress notices
			setProgressLoaded(false);
			await fetchSyncProgressData();
			if (cookies.length>0){
				setSyncProgress(1);
			}
		}
	}

	//this item can be loaded on other pages, but should then not show anything.
	if (selectedSubMenuItem !== 'cookie-scan') return null;

	let cookieCount = cookies ? cookies.length : 0;
	let description = '';
	if ( cookieCount===0 ){
		description = __("No cookies found on your domain yet.", "complianz-gdpr");
	} else if (cookieCount === 1) {
		description = __("The scan found 1 cookie on your domain.", "complianz-gdpr");
	} else {
		description = __("The scan found %s cookies on your domain.", "complianz-gdpr").replace('%s', cookieCount);
	}

	if ( progress>=100 ) {
		if (cookieCount>0) description += ' '+__('Continue the wizard to categorize cookies and configure consent.', 'complianz-gdpr');
	} else {
		description += ' '+__('Scanning, %s complete.', 'complianz-gdpr').replace('%s', Math.round(progress)+'%' );
	}

	if ( !initialLoadCompleted ) {
		description = <Icon name = "loading" color = 'grey' />;
	}

	let scanProgress = progress < 100 && progress > 0;
	let scanDisabled = wscLocked ? true : scanProgress;

	const startOnboardingFromWscAlert = () => {
		const url = new URL(cmplz_settings.dashboard_url);
		url.searchParams.set('websitescan', '');
		setTimeout(() => {
			window.location.href = url.href;
		}, 500);
	}

	return (
		<>
			{wscLocked &&
				<div className="cmplz-wscscan-alert">
					<Icon name={'warning'} color={'orange'} size={48}/>
					<div className="cmplz-wscscan-alert-group">
						<div className="cmplz-wscscan-alert-group-title">{__("Cookie Scan Unavailable", "complianz-gdpr")}</div>
						<div className="cmplz-wscscan-alert-group-desc">{__("We need to authenticate this domain.", "complianz-gpdr")}</div>
					</div>

					<div className="cmplz-wscscan-alert-desc-long">
						{__("The new advanced Website Scan needs to authenticate your website for security purposes. It only takes a second!")}
					</div>
					<div>
						<button type="button" onClick={startOnboardingFromWscAlert} className="cmplz-wscscan-alert button-secondary">
							{__("Start", "complianz-gdpr")}
						</button>
					</div>
				</div>
			}

			<div className="cmplz-table-header">
				<button disabled={scanDisabled} className="button button-default" onClick={ (e) => Start(e) }>{__("Scan","complianz-gdpr")}</button>
				<button disabled={scanDisabled} className="button button-default cmplz-reset-button" onClick={ (e) => clearCookies(e) }>{__("Clear Cookies","complianz-gdpr")}</button>
			</div>
			<div id="cmplz-scan-progress">
				<div className='cmplz-progress-bar' style={getStyles()}></div>
			</div>
			{!wscLocked && (
				<div className="cmplz-panel__list">
					<Panel summary={description} details={Details(initialLoadCompleted, cookies)}/>
				</div>
			)}
		</>
	);
}

export default memo(CookieScanControl)
