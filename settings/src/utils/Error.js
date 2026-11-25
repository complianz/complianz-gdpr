import {__} from "@wordpress/i18n";
import Hyperlink from "./Hyperlink";

const Error = (props) => {
	if (props.error) {
		console.log("errors detected during the loading of the settings page");
		console.log(props.error);
	}
	let description = false;
	let url = 'https://complianz.io/help/';

	let generic_rest_blocked_message = __("Please check if security settings on the server or a plugin is blocking the requests from Complianz.", "complianz-gdpr");
	let message = false;
	if (props.error) {
		message = props.error.message;
		if ( typeof message !== 'string'  ) {
			message = JSON.stringify(message);
		}
		if ( props.error.code==='rest_no_route') {
			description = __("The Complianz Rest API is disabled.", "complianz-gdpr")+" "+generic_rest_blocked_message
		} else if ( props.error.data.status === '404') {
			description = __("The Complianz Rest API returned a not found.", "complianz-gdpr")+" "+generic_rest_blocked_message;
		} else if ( props.error.data.status === '403') {
			description = __("The Complianz Rest API returned a 403 forbidden error.", "complianz-gdpr")+" "+generic_rest_blocked_message;
		}
		if (message.length>100){
			message = message.substring(0, 100)+ '...';
		}

	}

	return (
		<>
			{props.error && <div className="rsssl-rest-error-message">
				<h3>{__("A problem was detected during the loading of the settings", "complianz-gdpr")}</h3>
				{description &&
					<p>{description}</p>
				}

				<div>
					<p>{__("The request returned the following errors:", "complianz-gdpr")}</p>
					<ul>
						{props.error.code && <li>{__("Response code:", "complianz-gdpr")}&nbsp;{props.error.code}</li>}
						{props.error.data.status && <li>{__("Status code:", "complianz-gdpr")}&nbsp;{props.error.data.status}</li>}
						{message && <li>{__("Server response:", "complianz-gdpr")}&nbsp;{message}</li>}
					</ul>
				</div>
				<Hyperlink
					className="button button-default"
					target="_blank"
					rel="noopener noreferrer"
					text={__("More information","complianz-gdpr")}
					url={url}
				/>
			</div>}
		</>
	)
}
export default Error
