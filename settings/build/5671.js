"use strict";(globalThis.webpackChunkcomplianz_gdpr=globalThis.webpackChunkcomplianz_gdpr||[]).push([[5671],{85671:(t,e,s)=>{s.r(e),s.d(e,{default:()=>r});var c=s(30270),i=s(12902),n=s(48399);const r=(0,c.Ue)(((t,e)=>({integrationsLoaded:!1,fetching:!1,services:[],plugins:[],scripts:[],placeholders:[],blockedScripts:[],setScript:(e,s)=>{t((0,i.ZP)((t=>{if("block_script"===s){let s=t.blockedScripts;if(e.urls){for(const[t,c]of Object.entries(e.urls)){if(!c||0===c.length)continue;let t=!1;for(const[e,i]of Object.entries(s))c===e&&(t=!0);t||(s[c]=c)}t.blockedScripts=s}}const c=t.scripts[s].findIndex((t=>t.id===e.id));-1!==c&&(t.scripts[s][c]=e)})))},fetchIntegrationsData:async()=>{if(e().fetching)return;t({fetching:!0});const{services:s,plugins:c,scripts:i,placeholders:n,blocked_scripts:r}=await o();let d=i;d.block_script.forEach(((t,e)=>{t.id=e})),d.add_script.forEach(((t,e)=>{t.id=e})),d.whitelist_script.forEach(((t,e)=>{t.id=e})),t((()=>({integrationsLoaded:!0,services:s,plugins:c,scripts:d,fetching:!1,placeholders:n,blockedScripts:r})))},addScript:s=>{t({fetching:!0}),t((0,i.ZP)((t=>{t.scripts[s].push({name:"general",id:t.scripts[s].length,enable:!0})})));let c=e().scripts;return n.doAction("update_scripts",{scripts:c}).then((e=>(t({fetching:!1}),e))).catch((t=>{console.error(t)}))},saveScript:(s,c)=>{t({fetching:!0}),t((0,i.ZP)((t=>{const e=t.scripts[c].findIndex((t=>t.id===s.id));-1!==e&&(t.scripts[c][e]=s)})));let r=e().scripts;return n.doAction("update_scripts",{scripts:r}).then((e=>(t({fetching:!1}),e))).catch((t=>{console.error(t)}))},deleteScript:(s,c)=>{t({fetching:!0}),t((0,i.ZP)((t=>{const e=t.scripts[c].findIndex((t=>t.id===s.id));-1!==e&&t.scripts[c].splice(e,1)})));let r=e().scripts;return n.doAction("update_scripts",{scripts:r}).then((e=>(t({fetching:!1}),e))).catch((t=>{console.error(t)}))},updatePluginStatus:async(e,s)=>(t((0,i.ZP)((t=>{const c=t.plugins.findIndex((t=>t.id===e));-1!==c&&(t.plugins[c].enabled=s)}))),n.doAction("update_plugin_status",{plugin:e,enabled:s}).then((t=>t)).catch((t=>{console.error(t)}))),updatePlaceholderStatus:async(e,s,c)=>(c&&t((0,i.ZP)((t=>{const c=t.plugins.findIndex((t=>t.id===e));-1!==c&&(t.plugins[c].placeholder=s?"enabled":"disabled")}))),n.doAction("update_placeholder_status",{id:e,enabled:s}).then((t=>t)).catch((t=>{console.error(t)})))}))),o=()=>n.doAction("get_integrations_data",{}).then((t=>t)).catch((t=>{console.error(t)}))}}]);