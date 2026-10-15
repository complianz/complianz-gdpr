import Panel from "../Panel";
import { __ } from '@wordpress/i18n';
import Icon from "../../utils/Icon";
import {memo} from "@wordpress/element";
import useFields from "../Fields/FieldsData";
import DOMPurify from 'dompurify';

/**
 * Render a help notice in the sidebar
 */
const SinglePrivacyStatement = (props) => {
	const {updateField, setChangedField, getFieldValue} = useFields();

	const Details = () => {

		return (
			<>
				{/* nosemgrep: react-dangerouslysetinnerhtml */}
				<div className="cmplz-details-row" dangerouslySetInnerHTML={{__html: DOMPurify.sanitize( props.plugin.policy_text ) } }  />
			</>
		)
	}

	const addPolicyHandler = (title, text, e) => {
		e.preventDefault();
		let newText = getFieldValue('custom_privacy_policy_text');
		newText += '<h1>'+title+'</h1>'+text;
		updateField('custom_privacy_policy_text', newText);
		setChangedField('custom_privacy_policy_text', newText);
	}

	const Icons = () => {
		return (
			<>
				<button className={'cmplz-button-icon'} onClick = { (e) => addPolicyHandler(props.plugin.plugin_name, props.plugin.policy_text, e) }>
					<Icon tooltip={__( "Add to annex of Privacy Statement", "complianz-gdpr" )} name = 'plus'  />
				</button>

				{ props.plugin.consent_api !== 'na' &&
					<>
						<button className={'cmplz-button-icon'} >
						{ !props.plugin.consent_api && <Icon tooltip={__( "Does not conform with the Consent API", "complianz-gdpr" )} name = 'circle' color="red"  />}
						{ props.plugin.consent_api && <Icon tooltip={__( "Conforms to the Consent API", "complianz-gdpr" )} name = 'circle' color="green"  /> }
						</button>
					</>
				}
			</>
		)
	}

	return (
		<>
			<Panel summary={props.plugin.plugin_name} icon={props.icon} icons={Icons()} details={Details()} />
		</>
	);

}

export default memo(SinglePrivacyStatement)
