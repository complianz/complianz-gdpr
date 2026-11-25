import useIntegrations from "./IntegrationsData";
import useFields from "../Fields/FieldsData";
import ThirdPartyScript from "./ThirdPartyScript";
import readMore from "../../utils/readMore";
import {useState, useEffect} from "@wordpress/element";
import { __ } from '@wordpress/i18n';
import './integrations.scss';

import {memo} from "@wordpress/element";
const ScriptCenterControl = () => {
	const { scripts, addScript, saveScript, integrationsLoaded, fetchIntegrationsData} = useIntegrations();
	const [ disabled, setDisabled ] = useState( false );
	const [ disabledText, setDisabledText ] = useState( '' );
	const { getFieldValue} = useFields();

	useEffect(() => {
		if (!integrationsLoaded) fetchIntegrationsData();
		if (integrationsLoaded) {
				if ( getFieldValue( 'safe_mode' ) == 1 ) {
				setDisabledText( __( 'Safe Mode enabled. To manage integrations, disable Safe Mode under Tools - Support.', 'complianz-gdpr' ) );
				setDisabled( true );
			}
		}
	}, [integrationsLoaded])


	return (
		<>
			<p>
				{__( "The script center should be used to add and block third-party scripts and iFrames before consent is given, or when consent is revoked. For example Hotjar and embedded video’s.", 'complianz-gdpr' ) }
				{ readMore('https://complianz.io/help/wordpress/configuration/integrating-plugins/')}
			</p>
			{ (disabled ) &&
				<div className="cmplz-settings-overlay">
					<div className="cmplz-settings-overlay-message">{disabledText}</div>
				</div>
			}
			<h5>{__("Add a third-party script", 'complianz-gdpr' ) }</h5>
			{!integrationsLoaded &&  <> <ThirdPartyScript type='add_script' /></>}
			{integrationsLoaded && scripts.add_script.length>0 && scripts.add_script.map((script, i) => <ThirdPartyScript type='add_script' script={script} key={i} />)}
			<div><button onClick={()=>addScript('add_script') } className="button button-default">{__("Add new","complianz-gdpr")}</button></div>

			<h5>{__("Block a script, iframe or plugin", 'complianz-gdpr' ) }</h5>
			{!integrationsLoaded &&<> <ThirdPartyScript type='block_script' /></>}
			{integrationsLoaded && scripts.block_script.length>0 && scripts.block_script.map((script, i) => <ThirdPartyScript type='block_script' script={script} key={i} />)}
			<div><button onClick={()=>addScript('block_script')} className="button button-default">{__("Add new","complianz-gdpr")}</button></div>

			<h5>{__("Whitelist a script, iframe or plugin\n", 'complianz-gdpr' ) }</h5>
			{!integrationsLoaded &&<> <ThirdPartyScript type='whitelist_script' /></>}
			{integrationsLoaded && scripts.whitelist_script.length>0 && scripts.whitelist_script.map((script, i) => <ThirdPartyScript type='whitelist_script' script={script} key={i} />)}
			<div><button onClick={()=>addScript('whitelist_script')} className="button button-default">{__("Add new","complianz-gdpr")}</button></div>
		</>
	)
}
export default memo(ScriptCenterControl)

