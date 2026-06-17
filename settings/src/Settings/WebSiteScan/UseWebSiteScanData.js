import * as cmplz_api from "../../utils/api";
import { create } from 'zustand';


const defaultState = {
	loaded: false,
	tokenStatus: '',
	wscStatus: '',
	wscSignupDate: '',
	batchStats: null,
	syncing: false,
}


const UseWebSiteScanData = create((set, get) => ({
	...defaultState,
	startOnboarding: async () => {
		const url = new URL(cmplz_settings.dashboard_url);
		url.searchParams.set('websitescan', '');
		setTimeout(() => {
			window.location.href = url.href;
		}, 500);
	},
	getStatus: async () => {
		if(get().getStatusCalled) return;

		try {
			let data = {};
			const { wsc_status, token_status, wsc_signup_date, batch_stats } = await cmplz_api.doAction('get_wsc_status', data).then((response) => {
				return response
			});
			set({
				tokenStatus: token_status,
				wscStatus: wsc_status,
				wscSignupDate: wsc_signup_date,
				batchStats: batch_stats ?? null,
				loaded: true,
			});
		} catch (error) {
			console.error(`Getting status error: `, error);
		}
	},
	resetWsc: async () => {
		let confirmation = confirm('Are you sure? This will delete all your Website Scan data.');
		if (confirmation) {
			try {
				const { result, redirect } = await cmplz_api.doAction('reset_wsc').then((response) => {
					return response
				});
				if (result) {
					set((state) => ({
						...defaultState,
						startOnboarding: state.startOnboarding,
						getStatus: state.getStatus,
						enableWsc: state.enableWsc,
						disableWsc: state.disableWsc,
						resetWsc: state.resetWsc
					}));
					setTimeout(() => {
						window.location.reload();
					}, 500);
				}
			} catch (error) {
				console.error(`Resetting WSC error: `, error);
			} finally {
				setTimeout(() => {
					window.location.reload();
				}, 300);
			}
		}
	},
	enableWsc: async () => {
		try {
			const { updated, wsc_status, token_status } = await cmplz_api.doAction('enable_wsc').then((response) => {
				return response
			});
			set({
				updated: updated,
				tokenStatus: token_status,
				wscStatus: wsc_status,
				loaded: true,
			});
		} catch (error) {
			console.error(`Enabling WSC error: `, error);

		}
	},
	disableWsc: async () => {
		try {
			const { updated, wsc_status, token_status } = await cmplz_api.doAction('disable_wsc').then((response) => {
				return response
			});
			set({
				updated: updated,
				tokenStatus: token_status,
				wscStatus: wsc_status,
				loaded: true,
			});

		} catch (error) {
			console.error(`Disabling WSC error: `, error);
		}
	},
	requestActivationEmail: async () => {
		try {
			const response = await cmplz_api.doAction('request_activation_email');
			// fire again getStatus to update the status
			get().getStatus();
		} catch (error) {
			console.error(`Requesting activation email error: `, error);
		}
	}
}));

export default UseWebSiteScanData

