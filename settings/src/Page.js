import Header from "./Header";
import getAnchor from "./utils/getAnchor";
import {useEffect,useState} from '@wordpress/element';

import useFields from "./Settings/Fields/FieldsData";
import useMenu from "./Menu/MenuData";
import Menu from "./Menu/Menu";
import PagePlaceholder from "./Placeholder/PagePlaceholder";
import MenuPlaceholder from "./Placeholder/MenuPlaceholder";
import SettingsPlaceholder from "./Placeholder/SettingsPlaceholder";
import UseSyncData from "./Settings/Cookiedatabase/SyncData";
import useProgress from "./Dashboard/Progress/ProgressData";
import PreloadFields from "./Settings/Fields/PreloadFields";
import {__, setLocaleData} from "@wordpress/i18n";
import ErrorBoundary from "./utils/ErrorBoundary";

const Page = () => {
	const {progressLoaded, fetchProgressData} = useProgress();
	const {error, fields, changedFields, fetchFieldsData, updateFieldsData, fieldsLoaded, lockedByUser} = useFields();
	const {fetchMenuData, selectedMainMenuItem, selectedSubMenuItem } = useMenu();
	const {loading, syncProgress, fetchSyncProgressData} = UseSyncData();
	const [Settings, setSettings] = useState(null);
	const [DashboardPage, setDashboardPage] = useState(null);
	const [Menu, setMenu] = useState(null);
	const [Tour, setTour] = useState(null);
	const [CookieScanControl, setCookieScanControl] = useState(null);
	const [ToastContainer, setToastContainer] = useState(null);
	const [NewOnboarding, setNewOnboarding] = useState(null);

	//load the chunk translations passed to us from the cmplz_settings object
	//only works in build mode, not in dev mode.
	useEffect(() => {
		cmplz_settings.json_translations.forEach( (translationsString) => {
			let translations = JSON.parse(translationsString);
			let localeData = translations.locale_data[ 'complianz-gdpr' ] || translations.locale_data.messages;
			localeData[""].domain = 'complianz-gdpr';
			setLocaleData( localeData, 'complianz-gdpr' );
		});
	},[]);

	let showTour = window.location.href.indexOf('tour') !==-1;
	useEffect ( () => {
		if (showTour && !Tour){
			import ("./Tour/Tour").then(({ default: Tour }) => {
				setTour(() => Tour);
			});
		}
	}, [showTour]);

	let showNewOnboarding = window.location.href.indexOf('websitescan') !== -1;
	useEffect ( () => {
		if (showNewOnboarding && !NewOnboarding){
			import ("./Onboarding/NewOnboarding").then(({ default: NewOnboarding }) => {
				setNewOnboarding(() => NewOnboarding);
			});
		}
	}, [showNewOnboarding]);

	useEffect( () => {
		if (selectedMainMenuItem !== 'dashboard' && !Settings && !Menu){
			import ("./Settings/Settings").then(({ default: Settings }) => {
				setSettings(() => Settings);
			});
			import ("./Menu/Menu").then(({ default: Menu }) => {
				setMenu(() => Menu);
			});
		}
		if (selectedMainMenuItem === 'dashboard' && !DashboardPage){
			import ( "./Dashboard/DashboardPage").then(({ default: DashboardPage }) => {
				setDashboardPage(() => DashboardPage);
			});
		}

	}, [selectedMainMenuItem]);

	useEffect( () => {
		if (!CookieScanControl) {
			import (  "./Settings/CookieScan/CookieScanControl").then(({default: CookieScanControl}) => {
				setCookieScanControl(() => CookieScanControl);
			});
		}
	}, []);

	// async load react-toastify
	useEffect(() => {
		import('react-toastify').then((module) => {
			const ToastContainer = module.ToastContainer;
			setToastContainer(() => ToastContainer);
		});
	}, []);

	useEffect(() => {
		window.addEventListener('hashchange', () => {
			fetchMenuData(fields);
		});
		if (fieldsLoaded) {
			fetchMenuData(fields);
		}
	}, [fields]);

	useEffect( () => {
		let subMenuItem = getAnchor('menu');
		updateFieldsData(subMenuItem);
	}, [changedFields, selectedSubMenuItem] );

	useEffect( () => {
		const run = async () => {
			let subMenuItem = getAnchor('menu');
			await fetchFieldsData(subMenuItem);
			if (!progressLoaded) {
				await fetchProgressData()
			}
			if (!loading && syncProgress<100 && cmplz_settings.wsc_is_authenticated){
				fetchSyncProgressData();
			}
		}
		run();
	}, [] );

	if (error) {
		return (
			<PagePlaceholder error={error}></PagePlaceholder>
		)
	}
	if ( parseInt(lockedByUser) !== parseInt(cmplz_settings.user_id) ) {
		return (
			<PagePlaceholder lockedByUser={lockedByUser}></PagePlaceholder>
		)
	}

	return (
		<div className="cmplz-wrapper">
			<>
				<Header/>
				{ showTour && Tour && <Tour />}
				{ showNewOnboarding && NewOnboarding && <NewOnboarding/>}

				<div className={"cmplz-content-area cmplz-grid cmplz-" + selectedMainMenuItem}>
					{ selectedMainMenuItem !== 'dashboard' &&
						<>
							{Menu && <Menu/>}
							{!Menu && <MenuPlaceholder />}
							{Settings && <ErrorBoundary fallback={"Could not load:"+' Settings page'}><Settings /></ErrorBoundary>}
							{!Settings && <SettingsPlaceholder />}
						</>
					}
					{ selectedMainMenuItem === 'dashboard' && DashboardPage &&
						<DashboardPage/>
					}
				</div>
				{ToastContainer && (
					<ToastContainer
						position="bottom-right"
						autoClose={2000}
						limit={3}
						hideProgressBar
						newestOnTop
						closeOnClick
						pauseOnFocusLoss
						pauseOnHover
						theme="light"
					/>
				)}
			</>
			{/*{we want to run the scan also on the background }*/}
			{selectedSubMenuItem!=='cookie-scan' && CookieScanControl && <CookieScanControl/> }
			{ selectedMainMenuItem === 'dashboard' &&
				<PreloadFields/>
			}
		</div>
	);
}
export default Page
