import useIntegrations from "./IntegrationsData";
import {useState, useEffect} from "@wordpress/element";
import { __ } from '@wordpress/i18n';
import useFields from "../Fields/FieldsData";
import readMore from "../../utils/readMore";
import useMenu from "../../Menu/MenuData";
import {memo} from "@wordpress/element";
import SwitchInput from '../Inputs/SwitchInput';

const ServicesControl = () => {
	const { updatePlaceholderStatus, integrationsLoaded, services, fetchIntegrationsData} = useIntegrations();
	const [ updatedServices, setUpdatedServices ] = useState( [] );
	const [ searchValue, setSearchValue ] = useState( '' );
	const [ disabled, setDisabled ] = useState( false );
	const [ updatingServices, setUpdatingServices ] = useState( false );
	const [ disabledText, setDisabledText ] = useState( '' );
	const [ disabledReadmore, setDisabledReadmore ] = useState( '' );
	const { updateField, getField, getFieldValue, saveFields, setChangedField, addHelpNotice} = useFields();
	const { selectedSubMenuItem } = useMenu();

	const [DataTable, setDataTable] = useState(null);
	useEffect( () => {
		import('react-data-table-component').then(({ default: DataTable }) => {
			setDataTable(() => DataTable);
		});
	}, []);

	useEffect(() => {
		if (!integrationsLoaded) fetchIntegrationsData();

		if (integrationsLoaded) {
			//filter enabled services
			if ( getFieldValue( 'safe_mode' ) == 1 ) {
				setDisabledText( __( 'Safe Mode enabled. To manage integrations, disable Safe Mode under Tools - Support.', 'complianz-gdpr' ) );
				setDisabled( true );
			} else if (
				getFieldValue( 'uses_thirdparty_services' ) !== 'yes' &&
				getFieldValue( 'uses_social_media' ) !== 'yes' &&
				getFieldValue( 'uses_ad_cookies' ) !== 'yes'
				 ) {
				setDisabledText( __( 'Third-party services and social media are marked as not being used on your website in the wizard.', 'complianz-gdpr' ) );
				setDisabledReadmore('#wizard/services');
				setDisabled( true );
			}
		}
	}, [integrationsLoaded])

	useEffect(() => {
		syncServicesWithFields();
	}, [services])

	const syncServicesWithFields = () => {
		//for each service, update the value from the field
		let servicesCopy = [...services];
		servicesCopy.forEach(function(service, i) {
			let serviceCopy = {...service};
			let field = getField(service.source);
			if ( field.type==='multicheckbox' ) {
				let value = field.value;
				if (!Array.isArray(value)) value = [];
				serviceCopy.enabled = value.includes(service.id);
			} else {
				serviceCopy.enabled = field.value === 'yes';
			}
			servicesCopy[i] = serviceCopy;
		});

		setUpdatedServices(servicesCopy);
		let reCaptcha = getFieldValue('block_recaptcha_service') === 'yes';
		//get service with id == recaptcha from the services list, and check if it's enabled
		let recaptchaService = services.filter(service => service.id === 'google-recaptcha')[0];
		if (reCaptcha && recaptchaService && recaptchaService.enabled ) {
			addHelpNotice('integrations-services', 'warning', __( "reCaptcha is connected and will be blocked before consent. To change your settings, disable reCaptcha in the list.", 'complianz-gdpr' ) , __('reCaptcha blocking enabled', 'complianz-gdpr'),'#wizard/services');
		}
	}

	const customStyles = {
		headCells: {
			style: {
				paddingLeft: '0',
				paddingRight: '0',
			},
		},
		cells: {
			style: {
				paddingLeft: '0',
				paddingRight: '0',
			},
		},
	};

	const onChangePlaceholderHandler = async (service, enabled) => {
		setUpdatingServices(true);
		//set placeholder to 'disabled' or 'enabled' in updatedServices
		let services = [...updatedServices];
		let serviceIndex = services.findIndex(item => item.id === service.id);
		services[serviceIndex].placeholder = enabled ? 'enabled' : 'disabled';
		setUpdatedServices(services);
		await updatePlaceholderStatus(service.id, enabled);
		setUpdatingServices(false);
	}

	const onChangeHandler = async (service, enabled) => {
		setUpdatingServices(true);
		let field = getField(service.source);
		let value;
		if ( field.type==='multicheckbox' ) {
			let fieldValue = field.value;
			if (!Array.isArray(fieldValue)) fieldValue = [];
			value = [...fieldValue];
			if (!Array.isArray(value)) value = [];
			if (enabled) {
				value.push(service.id);
			} else {
				value = value.filter(item => item !== service.id);
			}
		} else {
			value = enabled ? 'yes' : 'no';
		}

		updateField(service.source, value);
		setChangedField(service.source, value);
		//check if any of the services is enabled. If not, disable the services field. If yes, enable it.

		await saveFields(selectedSubMenuItem, false);
		await fetchIntegrationsData();
		setUpdatingServices(false);
	}

	useEffect(() => {
		if (updatedServices.length===0) return;
		let servicesEnabled = 'yes';
		if (updatedServices.filter(item => item.enabled===true && item.source==='thirdparty_services_on_site').length === 0) {
			servicesEnabled = 'no';
		}

		if (getFieldValue('uses_thirdparty_services')!== servicesEnabled) {
			updateField('uses_thirdparty_services', servicesEnabled);
			setChangedField('uses_thirdparty_services', servicesEnabled);
		}

		let socialMediaEnabled = 'yes';
		if (updatedServices.filter(item => item.enabled===true && item.source==='socialmedia_on_site').length === 0) {
			socialMediaEnabled = 'no';
		}

		if (getFieldValue('uses_social_media')!== socialMediaEnabled) {
			updateField('uses_social_media', socialMediaEnabled);
			setChangedField('uses_social_media', socialMediaEnabled);
		}
	},[updatedServices]);

	const enabledDisabledPlaceholderSort = (rowA, rowB) => {
		const a = rowA.placeholder;
		const b = rowB.placeholder;
		if (a > b) {
			return 1;
		}
		if (b > a) {
			return -1;
		}
		return 0;
	}

	const enabledDisabledSort = (rowA, rowB) => {
		const a = rowA.enabled;
		const b = rowB.enabled;
		if (a > b) {
			return 1;
		}
		if (b > a) {
			return -1;
		}
		return 0;
	}

	const columns = [
		{
			name: __('Service',"complianz-gdpr"),
			selector: row => row.label,
			sortable: true,
			grow: 5,
		},
		{
			name: __('Placeholder',"complianz-gdpr"),
			selector: row => row.placeholderControl,
			sortable: true,
			sortFunction: enabledDisabledPlaceholderSort,
			grow: 2,
		},
		{
			name: __('Status',"complianz-gdpr"),
			selector: row => row.enabledControl,
			sortable: true,
			sortFunction: enabledDisabledSort,
			grow: 1,
			right: true,
		},
	];

	//filter the services by search value
	let filteredServices = updatedServices.filter(service => {
		return service.label.toLowerCase().includes(searchValue.toLowerCase());
	})
	//sort the services alphabetically by label
	filteredServices.sort((a, b) => {
		if (a.label < b.label) {
			return -1;
		}
		if (a.label > b.label) {
			return 1;
		}
		return 0;
	});
	filteredServices.forEach(service => {
		let value = getFieldValue(service.source);
		if ( Array.isArray(value) ) {
			service.enabled = value.includes(service.id);
		} else {
			service.enabled = value === 'yes';
		}

		service.enabledControl = <SwitchInput
			disabled = {updatingServices}
			value= { service.enabled }
			onChange={ ( fieldValue ) => onChangeHandler(service, fieldValue) }
			className={"cmplz-switch-input-tiny"}
		/>
		service.placeholderControl = <> {service.placeholder!=='none' && service.enabled && <><SwitchInput
			disabled = {updatingServices}
			value = { service.placeholder==='enabled' }
			onChange = { ( fieldValue ) => onChangePlaceholderHandler(service, fieldValue) }
			className={"cmplz-switch-input-tiny"}
		/></>}</>
	});

	return (
		<>
			<p>
				{ __( "Enabled services will be blocked on the front-end of your website until the user has given consent (opt-in), or after the user has revoked consent (opt-out). When possible a placeholder is activated. You can also disable or configure the placeholder to your liking.", 'complianz-gdpr' )}
				{ readMore( "https://complianz.io/help/wordpress/configuration/blocking-recaptcha-manually/" )}
			</p>
			<div className="cmplz-table-header">
				<div className="cmplz-table-header-controls">
					<input type="text" placeholder={__("Search", "complianz-gdpr")} value={searchValue} onChange={ ( e ) => setSearchValue(e.target.value) } />
				</div>
			</div>
			{ (disabled || filteredServices.length===0) && <>
				<div className="cmplz-settings-overlay">
					<div className="cmplz-settings-overlay-message" >{disabledText}
						{disabledReadmore && <>&nbsp;<a href={disabledReadmore} >{__('View services', 'complianz-gdpr')}</a></>}
					</div>
				</div>
			</>}
			{ (filteredServices.length===0) && <>
				<div className="cmplz-integrations-placeholder">
					<div></div><div></div><div></div><div></div><div></div><div></div>
				</div>
			</>}
			{!disabled && filteredServices.length>0 && DataTable && <>
				<DataTable
					columns={columns}
					data={filteredServices}
					dense
					pagination
					paginationPerPage={5}
					noDataComponent={<div className="cmplz-no-documents">{__("No services", "complianz-gdpr")}</div>}
					persistTableHead
					theme="really-simple-plugins"
					customStyles={customStyles}
				/></>
			}
		</>
	)
}
export default memo(ServicesControl)
