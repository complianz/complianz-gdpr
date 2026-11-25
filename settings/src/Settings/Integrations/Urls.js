import { __ } from '@wordpress/i18n';
import readMore from "../../utils/readMore";
import TextInput from "../Inputs/TextInput";
import Icon from "../../utils/Icon";
import useIntegrations from "./IntegrationsData";
import {useEffect, useState} from "react";

const Urls = (props) => {
	const { setScript, fetching } = useIntegrations();
	const [hasEmptyUrl, setHasEmptyUrl] = useState(false);
	const script = props.script;
	const type = props.type;

	useEffect(() => {
		//check if one of the URLS is empty. if so, setHasEmptyUrl to true
		let urls = script.hasOwnProperty('urls') ? Object.values(script.urls) : [''];
		if (urls.includes('')) {
			setHasEmptyUrl(true);
		} else {
			setHasEmptyUrl(false);
		}

	}, [script]);

	const onChangeUrlHandler = (index, url) => {
		let copyScript = {...script};
		let urls = {...copyScript.urls};
		urls[index] = url;
		copyScript.urls = urls;
		setScript(copyScript, props.type);
	}


	const addUrl = () => {
		let copyScript = {...script};
		let urls = copyScript.hasOwnProperty('urls') ? {...copyScript.urls} : [''];
		let curLength = Object.keys(urls).length;

		urls[curLength+1] = '';
		copyScript.urls = urls;
		setScript(copyScript, props.type);
	}



	const deleteUrl = (key) => {
		let copyScript = {...script};
		let urls = {...copyScript.urls};
		//delete index 'key' from copyScript.urls
		delete urls[key];
		copyScript.urls = urls;
		setScript(copyScript, props.type);
	}
	let urls = script.hasOwnProperty('urls') ? Object.entries(script.urls) : [''];
	return (
		<div className="cmplz-details-row">
			<label>
				{type==='block_script' && __("URLs that should be blocked before consent.", "complianz-gdpr")}
				{type==='whitelist_script' &&
					<>{__("URLs that should be whitelisted.", "complianz-gdpr")}
						{readMore("https://complianz.io/help/wordpress/configuration/whitelisting-inline-script/") }
					</>
				}
			</label>
			{ urls.map( ([index, url], i)=>
				<div key={i} className="cmplz-scriptcenter-url">
					<TextInput
						disabled={fetching}
						value={url ? url : ''}
						onChange={(value)=>onChangeUrlHandler(index, value)}
						id={i+"_url"}
						name={"url"}
					/>
					{i===0 && !hasEmptyUrl && <button className="button button-default" onClick={() => addUrl() }> <Icon name="plus" size={14}/></button>}
					{i!==0 && <button className="button button-default" onClick={() => deleteUrl(index) }> <Icon name="minus" size={14}/></button>}
				</div>
			)}
		</div>
	);
}
export default Urls;
