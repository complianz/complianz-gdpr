import {create} from 'zustand';
import produce from 'immer';
import * as cmplz_api from "../../utils/api";


const useIntegrations = create(( set, get ) => ({
	integrationsLoaded: false,
	fetching:false,
	services: [],
	plugins:[],
	scripts:[],
	placeholders:[],
	blockedScripts:[],
	setScript: (script, type) => {
		set(
			produce((state) => {
				//update blocked scripts options list if new urls were added.
				if (type==='block_script') {
					let options = state.blockedScripts;
					if ( script.urls ) {
						for (const [index, url] of Object.entries(script.urls)) {
							if (!url || url.length===0) continue;
							//check if url exists in the options object
							let found = false;
							for (const [optionIndex, optionValue] of Object.entries(options)) {
								if (url===optionIndex) found = true;
							}
							if (!found) {
								options[url] = url;
							}

						}
						state.blockedScripts = options;
					}
				}

				const index = state.scripts[type].findIndex(item => {
					return item.id===script.id;
				});
				if (index!==-1) state.scripts[type][index] = script;
			})
		)
	},
	fetchIntegrationsData: async ( ) => {
		if (get().fetching) return;

		set({fetching:true});
		const { services, plugins, scripts, placeholders, blocked_scripts}   = await fetchData();
		let scriptsWithId = scripts;

		//add a unique id to each script
		if (scriptsWithId.block_script && scriptsWithId.block_script.length>0 ) {
			scriptsWithId.block_script.forEach((script, i) => {
				script.id = i;
			})
		}

		if (scriptsWithId.add_script && scriptsWithId.add_script.length>0 ) {
			scriptsWithId.add_script.forEach((script, i) => {
				script.id = i;
			})
		}

		if (scriptsWithId.whitelist_script && scriptsWithId.whitelist_script.length>0 ) {
			scriptsWithId.whitelist_script.forEach((script, i) => {
				script.id = i;
			})
		}
		set(() => ({
			integrationsLoaded: true,
			services: services,
			plugins: plugins,
			scripts: scriptsWithId,
			fetching:false,
			placeholders: placeholders,
			blockedScripts:blocked_scripts,
		}));
	},
	addScript:(type) => {
		set({fetching:true});
		//check if get().scripts has property type. If not, add it.
		if ( !get().scripts[type] || !Array.isArray(get().scripts[type]) ) {
			set(
				produce((state) => {
					state.scripts[type] = [];
				})
			)
		}

		set(
			produce((state) => {
				state.scripts[type].push({'name':'general', 'id':state.scripts[type].length, 'enable':true});
			})
		)
		let scripts = get().scripts;
		return cmplz_api.doAction('update_scripts', {scripts: scripts}).then((response) => {
			set({fetching:false});
			return response;
		}).catch((error) => {
			console.error(error);
		});
	},
	saveScript:(script, type) => {
		set({fetching:true});
		if ( !get().scripts[type] || !Array.isArray(get().scripts[type]) ) {
			set(
				produce((state) => {
					state.scripts[type] = [];
				})
			)
		}
		set(
			produce((state) => {
				const index = state.scripts[type].findIndex(item => {
					return item.id===script.id;
				});
				if (index!==-1) state.scripts[type][index] = script;
			})
		)
		let scripts = get().scripts;
		return cmplz_api.doAction('update_scripts', {scripts: scripts}).then((response) => {
			set({fetching:false});
			return response;
		}).catch((error) => {
			console.error(error);
		});
	},
	deleteScript:(script, type) => {
		set({fetching:true});
		if ( !get().scripts[type] || !Array.isArray(get().scripts[type]) ) {
			set(
				produce((state) => {
					state.scripts[type] = [];
				})
			)
		}
		set(
			produce((state) => {
				const index = state.scripts[type].findIndex(item => {
					return item.id===script.id;
				});
				//drop script with this index
				if (index!==-1) state.scripts[type].splice(index, 1);
			})
		)
		let scripts = get().scripts;
		return cmplz_api.doAction('update_scripts', {scripts: scripts}).then((response) => {
			set({fetching:false});
			return response;
		}).catch((error) => {
			console.error(error);
		});
	},
	updatePluginStatus: async (pluginId, enabled) => {
		set({fetching:true});
		set(
			produce((state) => {
				const index = state.plugins.findIndex(plugin => {
					return plugin.id===pluginId;
				});
				if (index!==-1) state.plugins[index].enabled = enabled;
			})
		)
		const response = await cmplz_api.doAction('update_plugin_status', {plugin: pluginId, enabled: enabled}).then((response) => {
			return response;
		}).catch((error) => {
			console.error(error);
		});
		set({fetching:false});
		return response;
	},
	updatePlaceholderStatus: async (id, enabled, isPlugin) => {
		set({fetching:true});

		// keep the store in sync for services too, otherwise a screen that remounts re-reads the stale value
		set(
			produce((state) => {
				const list = isPlugin ? state.plugins : state.services;
				const index = list.findIndex(item => {
					return item.id===id;
				});
				if (index!==-1) list[index].placeholder = enabled ? 'enabled' : 'disabled';
			})
		)
		const response = await cmplz_api.doAction('update_placeholder_status', {id: id, enabled: enabled}).then((response) => {
			return response;
		}).catch((error) => {
			console.error(error);
		});
		set({fetching:false});

		return response;
	}
}));

export default useIntegrations;

const fetchData = () => {
	return cmplz_api.doAction('get_integrations_data', {}).then((response) => {
		return response;
	}).catch((error) => {
		console.error(error);
	});
}
