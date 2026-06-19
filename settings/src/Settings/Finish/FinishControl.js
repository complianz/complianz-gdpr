// import Icon from "../utils/Icon";
import { __ } from '@wordpress/i18n';
import useFields from "../Fields/FieldsData";
import {useEffect} from "@wordpress/element";
import {memo} from "@wordpress/element";
import useMenu from "../../Menu/MenuData";
import Icon from "../../utils/Icon";
import useFinishData from "./useFinishData";
import './Finish.scss';
/**
 * Render a help notice in the sidebar
 */
const FinishControl = () => {
	const {fieldsLoaded, fields, updateField, getFieldValue, changedFields, setChangedField, updateFieldsData, addHelpNotice, fetchAllFieldsCompleted, allRequiredFieldsCompleted, notCompletedRequiredFields} = useFields();
	const {getMenuLinkById } = useMenu();
	const {cookiebannerRequired, getCookieBannerRequired} = useFinishData();

    useEffect ( () => {
        if ( !fieldsLoaded ) return;
        //we want to update on load. When the user changes a field, we want to update after save, then the count is zero again.
        if ( changedFields.length>0 ){
            return;
        }
        getCookieBannerRequired();
    },[changedFields]);

	useEffect ( () => {
		if (!fieldsLoaded) return;
		fetchAllFieldsCompleted();
	}, [ fieldsLoaded, fields ]);

	useEffect ( () => {
		if (!fieldsLoaded) return;
		let currentValue = getFieldValue('cookie_banner_required');
		if ( currentValue !== cookiebannerRequired ) {
			updateField('cookie_banner_required', cookiebannerRequired);
			setChangedField('cookie_banner_required', cookiebannerRequired);
			updateFieldsData();
		}
	}, [fieldsLoaded, cookiebannerRequired])

	useEffect ( () => {
		if (!fieldsLoaded) return;
		if ( cookiebannerRequired ) {
			let explanation =
				__( "The consent banner and cookie blocker are required on your website.","complianz-gdpr")
				+" "+__( "You can enable them both here, then you should check your website if your configuration is working properly.","complianz-gdpr")
				+" "+__("Please read the below article to debug any issues while in safe mode. Safe mode is available under settings.","complianz-gdpr")
				+' '+__("You will find tips and tricks on your dashboard after you have configured your consent banner.", 'complianz-gdpr' )
			addHelpNotice('last-step-feedback', 'default', explanation, __('A consent banner is required', 'complianz-gdpr'), 'https://complianz.io/debugging-issues/');
		} else {
			let explanation = __( "Your site does not require a consent banner. If you think you need a consent banner, please review your wizard settings.","complianz-gdpr")
			addHelpNotice('last-step-feedback', 'warning', explanation, __('A consent banner is not required', 'complianz-gdpr'));
		}
	},[ fieldsLoaded, cookiebannerRequired, changedFields ]);//we cannot use the "fields" dependency, as it will create an infinite loop. changedfields works fine to keep the notice in the sidebar.

	return (
		<>
			{ notCompletedRequiredFields.length<2 && <b>{__("Almost there!","complianz-gdpr")}</b>}
			{ notCompletedRequiredFields.length>=2 && <b>{__("There are %s questions that are required to complete the wizard.","complianz-gdpr").replace('%s',notCompletedRequiredFields.length)}</b>}
			{ allRequiredFieldsCompleted && <div>
				<p>{__( "Click '%s' to complete the configuration. You can come back to change your configuration at any time.", 'complianz-gdpr' ).replace('%s', __( "Finish", 'complianz-gdpr' ) ) }</p>

				{ cookiebannerRequired && <p>{
					__( "The consent banner and the cookie blocker are now ready to be enabled.", "complianz-gdpr") + ' ' +
					__( "Please check your website after finishing the wizard to verify that your configuration is working properly.", "complianz-gdpr")
				}
				</p> }
			</div>}
			{ !allRequiredFieldsCompleted &&
				<div>
					<p>
						{__("Not all required fields are completed yet.", "complianz-gdpr")
						+ " " +
						__("Please check the wizard to complete all required questions.", 'complianz-gdpr')}
					</p>
					<p>{__("The following required fields have not been completed:", 'complianz-gdpr' )}</p>
					<ul>
						{notCompletedRequiredFields.map( (field, i) =>
							<li key={i}>
								<div>
								{field.parent_label ? field.parent_label : field.label}
									&nbsp;
								<a href={getMenuLinkById(field.menu_id)}><Icon name={'circle-chevron-right'} color="black" tooltip={__("Go to question","complianz-gdpr")} size={14} /></a>
								</div>
							</li>)}
					</ul>
				</div>
			}

		</>

	);
}
export default memo(FinishControl)
