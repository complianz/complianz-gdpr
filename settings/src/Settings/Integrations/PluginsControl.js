import useIntegrations from "./IntegrationsData";
import {useState, useEffect} from "@wordpress/element";
import { __ } from '@wordpress/i18n';
import useFields from "../Fields/FieldsData";
import readMore from "../../utils/readMore";
import {memo} from "@wordpress/element";
import SwitchInput from '../Inputs/SwitchInput';
import Alert from "../../utils/Alert";
import { getScanUpsellAlertProps } from "../../utils/wsc";

const PluginsControl = () => {
	const {updatePlaceholderStatus, fetching, updatePluginStatus, integrationsLoaded, plugins, fetchIntegrationsData} = useIntegrations();
	const [ searchValue, setSearchValue ] = useState( '' );
	const [ disabled, setDisabled ] = useState( false );
	const [ disabledText, setDisabledText ] = useState( '' );
	const { getFieldValue} = useFields();

	const [DataTable, setDataTable] = useState(null);
	useEffect( () => {
		import('react-data-table-component').then(({ default: DataTable }) => {
			setDataTable(() => DataTable);
		});
	}, []);

	useEffect(() => {
		if (!integrationsLoaded) fetchIntegrationsData();

		if (integrationsLoaded) {
			//filter enabled plugins
			if ( getFieldValue( 'safe_mode' ) == 1 ) {
				setDisabledText( __( 'Safe Mode enabled. To manage integrations, disable Safe Mode under Tools - Support.', 'complianz-gdpr' ) );
				setDisabled( true );
			} else if ( plugins.length===0 ) {
				setDisabledText( __( 'No active plugins detected in the integrations list.', 'complianz-gdpr' ) );
				setDisabled( true );
			}
		}
	}, [integrationsLoaded])

	useEffect(() => {

	}, [plugins])



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

	const onChangePlaceholderHandler = async (plugin, enabled) => {
		await updatePlaceholderStatus(plugin.id, enabled, true);
	}

	const onChangeHandler = async (plugin, enabled) => {
		await updatePluginStatus(plugin.id, enabled);
		await fetchIntegrationsData();
	}

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
			name: __('Plugin',"complianz-gdpr"),
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

	//filter the plugins by search value
	let filteredPlugins = plugins.filter(plugin => {
		return plugin.label.toLowerCase().includes(searchValue.toLowerCase());
	})

	//sort the plugins alphabetically by label
	filteredPlugins.sort((a, b) => {
		if (a.label < b.label) {
			return -1;
		}
		if (a.label > b.label) {
			return 1;
		}
		return 0;
	});

	//add the controls to the plugins
	let outputPlugins = [];
	filteredPlugins.forEach(plugin => {
		let pluginCopy = {...plugin}
		pluginCopy.enabledControl = <SwitchInput
			disabled={fetching}
			className={"cmplz-switch-input-tiny"}
			value= { plugin.enabled }
			onChange={ ( fieldValue ) => onChangeHandler(plugin, fieldValue) }
		/>
		pluginCopy.placeholderControl = <>{plugin.placeholder!=='none' && <><SwitchInput
			className={"cmplz-switch-input-tiny"}
			disabled = {plugin.placeholder==='none' || fetching}
			value = { plugin.placeholder==='enabled' }
			onChange = { ( fieldValue ) => onChangePlaceholderHandler(plugin, fieldValue) }
		/></>}</>
		outputPlugins.push(pluginCopy);

	});

	return (
		<>
			<p>
				{__( 'Below you will find the plugins currently detected and integrated with Complianz. Most plugins work by default, but you can also add a plugin to the script center or add it to the integration list.', 'complianz-gdpr' ) }
				{ readMore('https://complianz.io/developers-guide-for-third-party-integrations')}
				{__( "Enabled plugins will be blocked on the front-end of your website until the user has given consent (opt-in), or after the user has revoked consent (opt-out). When possible a placeholder is activated. You can also disable or configure the placeholder to your liking.",
				'complianz-gdpr' )}
				{readMore( "https://complianz.io/blocking-recaptcha-manually/" )}
			</p>
			{ cmplz_settings.scan_upsell?.code === 'webshop' && <div className="cmplz-table-header"><Alert { ...getScanUpsellAlertProps() } /></div> }
			<div className="cmplz-table-header">
				{plugins.length>5 && <input type="text" placeholder={__("Search", "complianz-gdpr")} value={searchValue} onChange={ ( e ) => setSearchValue(e.target.value) } /> }
			</div>
			{ (disabled || filteredPlugins.length===0) &&
				<div className="cmplz-settings-overlay">
					<div className="cmplz-settings-overlay-message">{disabledText}</div>
				</div>
			}
			{ (outputPlugins.length===0) &&
				<></>
			}
			{!disabled && outputPlugins.length>0 && DataTable && <>
				<DataTable
					columns={columns}
					data={outputPlugins}
					dense
					pagination
					paginationPerPage={5}
					noDataComponent={<div className="cmplz-no-documents">{__("No plugins", "complianz-gdpr")}</div>}
					persistTableHead
					theme="really-simple-plugins"
					customStyles={customStyles}
				/></>
			}
		</>
	)
}
export default memo(PluginsControl)
