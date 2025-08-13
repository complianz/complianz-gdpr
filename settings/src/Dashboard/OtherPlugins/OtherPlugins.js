import { useEffect} from "@wordpress/element";
import { __ } from '@wordpress/i18n';
import Placeholder from '../../Placeholder/Placeholder';
import useOtherPlugins  from "./OtherPluginsData";
const OtherPlugins = () => {
    const {dataLoaded, pluginData, pluginActions, fetchOtherPluginsData, error} = useOtherPlugins();
    useEffect(() => {
        if (!dataLoaded) {
            fetchOtherPluginsData();
        }
    }, [] )

    const otherPluginElement = (plugin, i) => {
        return (
            <div key={i}  className={"cmplz-suggested-plugin cmplz-"+plugin.slug}>
                <img className="cmplz-suggested-plugin-img" src={cmplz_settings.plugin_url+'/upgrade/img/'+plugin.image} />
                <div className="cmplz-suggested-plugin-group">
                    <div className="cmplz-suggested-plugin-group-title">{plugin.title}</div>
                    <div className="cmplz-suggested-plugin-group-desc">{plugin.summary}</div>
                </div>
                <div className="cmplz-suggested-plugin-desc-long">
                    {plugin.description}
                </div>
                <div>
                    <div className="cmplz-other-plugin-status">
                        {plugin.pluginAction==='upgrade-to-premium' && <a type="button" className="button-secondary cmplz-install-plugin" target="_blank" href={plugin.upgrade_url} rel="noopener noreferrer">{__("Upgrade", "complianz-gdpr")}</a>}
                        {plugin.pluginAction!=='upgrade-to-premium' && plugin.pluginAction!=='installed' &&
                            <button type="button" className="button-secondary cmplz-install-plugin" onClick={ (e) => pluginActions(plugin.slug, plugin.pluginAction, e) } >{plugin.pluginActionNice}</button>}
                        {plugin.pluginAction==='installed' && __("Installed", "complianz-gdpr")}
                    </div>
                </div>
            </div>
        )
    }

    if ( !dataLoaded || error) {
        return (<Placeholder lines="3" error={error}></Placeholder>)
    }

    return (
           <div className="cmplz-other-plugins-container">
               { pluginData.map((plugin, i) => otherPluginElement(plugin, i)) }
           </div>
    )
}

export default OtherPlugins;
