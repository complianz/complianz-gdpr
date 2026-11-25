import { __ } from '@wordpress/i18n';
import useMenu from "./Menu/MenuData";
import {useEffect} from "@wordpress/element";

const Header = () => {
	const {menu, selectedMainMenuItem, fetchSelectedMainMenuItem, fetchMenuData} = useMenu();
	const plugin_url = cmplz_settings.plugin_url;
	useEffect( () => {
		fetchMenuData();
		fetchSelectedMainMenuItem();
	}, [] );
	let menuItems =Object.values(menu);
	menuItems = menuItems.filter( item => item!==null );
	return (
		<div className="cmplz-header-container">
			<div className="cmplz-settings-header">
				<img className="cmplz-header-logo" src={plugin_url+"assets/images/cmplz-logo.svg"} alt="Complianz logo" />
				<div className="cmplz-header-left">
					<nav className="cmplz-header-menu">
						<ul>
							{menuItems.map((menu_item, i) =>
							  <li key={i}><a className={ selectedMainMenuItem === menu_item.id ? 'cmplz-main active' : 'cmplz-main' } href={"#" + menu_item.id.toString()} >{menu_item.title}</a></li>)}

						</ul>
					</nav>
				</div>
				<div className="cmplz-header-right">
					<a className="cmplz-knowledge-base-link" href="https://complianz.io/help/" target="_blank" rel="noopener noreferrer">{__("Documentation", "complianz-gdpr")}</a>
					{cmplz_settings.is_premium &&
						<a href="#tools/support"
						   className="button button-black"
						   >{__("Support", "complianz-gdpr")}</a>
					}
					{!cmplz_settings.is_premium &&
						<a href={cmplz_settings.upgrade_link}
						   className="button button-black"
						   target="_blank" rel="noopener noreferrer">{__("Go Pro", "complianz-gdpr")}</a>
					}
				</div>
			</div>
		</div>
	);
}
export default Header
