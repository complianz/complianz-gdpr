// import useTools from "../Tools/ToolsData";
import useFields from "../../Settings/Fields/FieldsData";
import {
	useEffect, useState
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import useDatarequestsData from "../../Settings/DataRequests/useDatarequestsData";
import Statistics from "./Statistics";
import ToolItem from "./ToolItem";

const PlusOnes = (props) => {
	return (
		<div className="cmplz-plusone">{props.count}</div>
	)
}

const Tools = () => {
	const {fields, getFieldValue} = useFields();
	const [consentStatisticsEnabled, setConsentStatisticsEnabled] = useState(false);
	const [abTestingEnabled, setAbTestingEnabled] = useState(false);
	const { recordsLoaded, fetchData, totalOpen} = useDatarequestsData();
	useEffect( () => {
		if ( !recordsLoaded){
			fetchData(10, 1, 'ID', 'ASC');
		}

	}, [recordsLoaded] );

	useEffect (() => {
		let consentStats = getFieldValue('a_b_testing')==1;
		setConsentStatisticsEnabled(consentStats);
		let ab = getFieldValue('a_b_testing_buttons')==1;
		setAbTestingEnabled(ab);

	},[fields])
	const tools = [
		{
			title: __("Data Requests", "complianz-gdpr"),
			viewLink: "#tools/data-requests",
			enableLink: "#wizard/security-consent",
			field: {name:"datarequest",value: 'yes'},
			link: "https://complianz.io/definition/what-is-a-data-request/",
			plusone: <PlusOnes count={totalOpen}/>
		},
		{
			title: __("Records of Consent", "complianz-gdpr"),
			viewLink: "#tools/records-of-consent",
			enableLink: "#wizard/security-consent",
			field: {name:"records_of_consent",value: 'yes'},
			link: "https://complianz.io/records-of-consent/",
		},
		{
			title: __("Processing Agreements", "complianz-gdpr"),
			viewLink: "#tools/processing-agreements",
			link: "https://complianz.io/definition/what-is-a-processing-agreement/",
		},
		{
			title: __("Consent Statistics", "complianz-gdpr"),
			viewLink: "#tools/ab-testing",
			link: "https://complianz.io/a-quick-introduction-to-a-b-testing/",
		},
		{
			title: __("A/B Testing", "complianz-gdpr"),
			viewLink: "#tools/ab-testing",
			link: "https://complianz.io/a-quick-introduction-to-a-b-testing/",
		},
		{
			title: __("Translations", "complianz-gdpr"),
			viewLink: "#tools/translations",
			link: "https://complianz.io/translations/",
		},
		{
			title: __("Documentation", "complianz-gdpr"),
			link: "https://complianz.io/support/",
		},
		{
			title: __("Premium Support", "complianz-gdpr"),
			viewLink: "#tools/support",
			link: "https://complianz.io/about-premium-support/",
		},
		{
			title: "WooCommerce",
			plugin: "woocommerce",
			link: cmplz_settings.admin_url+'admin.php?page=wc-settings&tab=account',
		},
		{
			title: __("Security", "complianz-gdpr"),
			link: "#tools/security",
			viewLink: "#tools/security",
		},
	]
	let multisiteLink = cmplz_settings.is_multisite_plugin ? "#tools/tools-multisite" : "https://complianz.io/complianz-for-wordpress-multisite-installations/";
	if ( cmplz_settings.is_multisite ) {
		tools.push({
			title: __("Multisite", "complianz-gdpr"),
			link: multisiteLink,
			viewLink: multisiteLink,
		})
	}

	if (consentStatisticsEnabled) {
		return (
			<>
				<Statistics abTestingEnabled={abTestingEnabled}/>
			</>
		)
	}

	return (
		<>
			{tools.map((item, i) =>
				<ToolItem key={i} item={item}/>
				)
			}

		</>
	);

}
export default Tools;
