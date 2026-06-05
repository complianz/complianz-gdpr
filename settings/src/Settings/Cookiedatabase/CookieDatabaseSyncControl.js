import {useEffect, useState} from "@wordpress/element";
import UseSyncData from './SyncData';
import Service from './Service';
import { __ } from '@wordpress/i18n';
import useFields from "../../Settings/Fields/FieldsData";
import {memo} from "@wordpress/element";
import CheckboxGroup from "../Inputs/CheckboxGroup";
import { memoize } from 'lodash';
import Icon from "../../utils/Icon";

const CookieDatabaseSyncControl = () => {
	const { filterAndSort, showDeletedCookies, setShowDeletedCookies, syncDataLoaded, loadingSyncData, language, setLanguage, languages, fCookies, cookieCount, addCookie, addService, fServices, syncProgress, curlExists, hasSyncableData, setSyncProgress, restart, fetchSyncProgressData, errorMessage} = UseSyncData();
	const {addHelpNotice, removeHelpNotice, getFieldValue} = useFields();
	const wscLocked = !cmplz_settings.wsc_is_authenticated;
	const [disabled, setDisabled] = useState(false);
	const [noCookieNoticeShown, setNoCookieNoticeShown] = useState(false);
	const [servicesAndCookies, setServicesAndCookies] = useState([]);

	const startOnboardingFromWscAlert = () => {
		const url = new URL(cmplz_settings.dashboard_url);
		url.searchParams.set('websitescan', '');
		setTimeout(() => {
			window.location.href = url.href;
		}, 500);
	}

	useEffect ( () => {
		if ( !loadingSyncData && syncProgress <100 ) {
			fetchSyncProgressData();
		}
	},[syncProgress]);

	useEffect ( () => {
		fetchSyncProgressData();
	},[language]);

	useEffect ( () => {

		let useCdbApi = getFieldValue('use_cdb_api')!=='no';
		if ( !useCdbApi ) {
			setDisabled(true) ;
			let explanation = __("You have opted out of the use of the Cookiedatabase.org synchronization.", "complianz-gdpr");
			addHelpNotice('cookiedatabase_sync', 'warning', explanation, 'Cookiedatabase', 'complianz-gdpr');
		} else if ( !curlExists ) {
			setDisabled(true) ;
			let explanation = __("CURL is not enabled on your site, which is required for the Cookiedatabase sync to function.", "complianz-gdpr");
			addHelpNotice('cookiedatabase_sync', 'warning', explanation, 'Cookiedatabase', 'complianz-gdpr');
		} else if ( errorMessage!=='' ) {
			setDisabled(true) ;
			addHelpNotice('cookiedatabase_sync', 'warning', errorMessage, 'Cookiedatabase', 'complianz-gdpr');
		} else if ( !hasSyncableData ) {
			setDisabled(true);
			let explanation = __("Synchronization disabled: All detected cookies and services have been synchronised.", "complianz-gdpr");
			addHelpNotice('cookiedatabase_sync', 'warning', explanation, 'Cookiedatabase', 'complianz-gdpr');
		} else if ( syncDataLoaded ) {
			setDisabled(false);
			if ( cookieCount === 0) {
				setNoCookieNoticeShown(true);
				let explanation = __("No cookies have been found currently. Please try another site scan, or check the most common causes in the article below ", "complianz-gdpr");
				addHelpNotice('cookiedatabase_sync', 'warning', explanation, __('No cookies found', 'complianz-gdpr'), 'https://complianz.io/cookie-scan-results/' );
			} else if ( noCookieNoticeShown ) {
				removeHelpNotice('cookiedatabase_sync')
			}
		}
	},[getFieldValue('use_cdb_api'), curlExists, errorMessage, hasSyncableData, servicesAndCookies, syncDataLoaded, fCookies ]);

	useEffect ( () => {
		if ( syncProgress<100 && syncProgress>0) {
			setDisabled(true) ;
		} else if ( syncProgress === 100 ) {
			setDisabled(false);
		}
	},[syncProgress]);

	useEffect(() => {
		filterAndSort();
	}, [showDeletedCookies]);


	const buildServicesCookiesArray = memoize(() => {
		let filteredCookies = [...fCookies];
		const servicesMap = {};
		[...fServices]
			.forEach(function(service) {
				servicesMap[service.ID] = {
					id: service.ID,
					name: service.name,
					service: service,
					cookies: []
				};
			});

		filteredCookies.forEach(function(cookie) {
			let serviceID = cookie.service ? cookie.serviceID : 0;
			if (!servicesMap[serviceID]) {
				servicesMap[serviceID] = {
					id: serviceID,
					name: !cookie.service ? __("Unknown Service","complianz-gdpr") : cookie.service,
					service: fServices.filter(service => service.ID === serviceID)[0],
					cookies: []
				};
			}
			servicesMap[serviceID].cookies.push(cookie);
		});

		setServicesAndCookies(Object.values(servicesMap) );
	})

	useEffect ( () => {
		buildServicesCookiesArray();
	},[ fServices, fCookies ]);

	const onAddServiceHandler = () => {
		addService();
	}

	const getStyles = () => {
		return Object.assign(
			{},
			{width: (wscLocked ? 0 : syncProgress)+"%"},
		);
	}

	const Start = () => {
		setSyncProgress(1);
		restart();
	}

	return (
		<>
			{wscLocked &&
				<div className="cmplz-wscscan-alert">
					<Icon name={'warning'} color={'orange'} size={48}/>
					<div className="cmplz-wscscan-alert-group">
						<div className="cmplz-wscscan-alert-group-title">{__("Cookiedatabase Sync Unavailable", "complianz-gdpr")}</div>
						<div className="cmplz-wscscan-alert-group-desc">{__("We need to authenticate this domain.", "complianz-gdpr")}</div>
					</div>
					<div className="cmplz-wscscan-alert-desc-long">
						{__("The cookiedatabase.org sync requires WSC authentication. It only takes a second!", "complianz-gdpr")}
					</div>
					<div>
						<button type="button" onClick={startOnboardingFromWscAlert} className="cmplz-wscscan-alert button-secondary">
							{__("Start", "complianz-gdpr")}
						</button>
					</div>
				</div>
			}
			<div className="cmplz-cookiedatabase-controls">
				<button disabled={disabled || loadingSyncData} className="button button-default" onClick={ (e) => Start(e) }>{__("Sync","complianz-gdpr")}</button>
				{ languages.length > 1 &&
					<select disabled={loadingSyncData} value={language} onChange={(e) => setLanguage(e.target.value) }>
						{languages.map((language, i) => <option key={i} value={language}>{language}</option>)}
					</select>
				}
				<CheckboxGroup
					id={'show_deleted_cookies'}
					disabled={wscLocked}
					value={showDeletedCookies}
					onChange={(value) => setShowDeletedCookies(value)}
					options={{true: __('Show deleted cookies', 'complianz-gdpr')}}
				/>
			</div>
			<div id="cmplz-scan-progress">
				<div className='cmplz-progress-bar' style={getStyles()}></div>
			</div>
			<div className="cmplz-panel__list">
				{ servicesAndCookies.map((service, i) => <Service key={i} addCookie={addCookie} id={service.id} cookies={service.cookies} name={service.name} service={service.service}/> ) }
			</div>
			<div className="cmplz-panel__buttons">
				<button disabled={loadingSyncData} onClick={ (e) => onAddServiceHandler() } className="button button-default">
					{__("Add service", "complianz-gdpr") }
				</button>
			</div>
		</>
	);
}

export default memo(CookieDatabaseSyncControl)
