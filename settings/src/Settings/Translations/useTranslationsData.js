import {create} from 'zustand';
import * as cmplz_api from "../../utils/api";
import produce from "immer";

export const UseTranslationsData = create(( set, get ) => ({
	translationDataLoaded: false,
	translationDataLoading: false,
	saving: false,
	error: null,
	
	// Translation status data
	translationStatus: null,
	
	// Cron automation data
	cronStatus: null,
	cronInterval: 'cmplz_weekly',
	
	// Test results
	testResults: null,
	
	fetchTranslationStatus: async () => {
		if ( get().translationDataLoading ) {
			return;
		}

		set({ translationDataLoading: true, error: null });
		
		try {
			const response = await cmplz_api.doAction('get_translation_status', {});
			if (response.request_success) {
				set({
					translationStatus: response.data,
					translationDataLoaded: true,
					translationDataLoading: false,
					error: null
				});
			} else {
				set({
					translationDataLoaded: true,
					translationDataLoading: false,
					error: response.message || 'Failed to load translation status'
				});
			}
		} catch (err) {
			set({
				translationDataLoaded: true,
				translationDataLoading: false,
				error: 'Error loading translation status'
			});
		}
	},
	
	fetchCronStatus: async () => {
		set({ translationDataLoading: true, error: null });
		
		try {
			const response = await cmplz_api.doAction('get_cron_status', {});
			if (response.request_success) {
				set({
					cronStatus: response.data,
					cronInterval: response.data.interval_setting || 'cmplz_weekly',
					translationDataLoaded: true,
					translationDataLoading: false,
					error: null
				});
			} else {
				set({
					translationDataLoaded: true,
					translationDataLoading: false,
					error: response.message || 'Failed to load cron status'
				});
			}
		} catch (err) {
			set({
				translationDataLoaded: true,
				translationDataLoading: false,
				error: 'Error loading cron status'
			});
		}
	},
	
	updateCronInterval: async (interval) => {
		set({ saving: true, error: null });
		
		try {
			const response = await cmplz_api.doAction('update_cron_interval', {
				interval: interval
			});
			if (response.request_success) {
				set({
					cronInterval: interval,
					saving: false,
					error: null
				});
				// Refresh cron status after update
				await get().fetchCronStatus();
				return { success: true, message: response.message };
			} else {
				set({
					saving: false,
					error: response.message || 'Failed to update interval'
				});
				return { success: false, message: response.message };
			}
		} catch (err) {
			set({
				saving: false,
				error: 'Error updating interval'
			});
			return { success: false, message: 'Error updating interval' };
		}
	},
	
	fetchTranslations: async () => {
		set({ saving: true, error: null });
		
		try {
			const response = await cmplz_api.doAction('fetch_translations', {});
			if (response.request_success) {
				set({ saving: false, error: null });
				// Refresh status after successful fetch
				await get().fetchTranslationStatus();
				return { success: true, message: response.message || 'Translations fetched successfully!' };
			} else {
				set({
					saving: false,
					error: response.message || 'Failed to fetch translations'
				});
				return { success: false, message: response.message };
			}
		} catch (err) {
			set({
				saving: false,
				error: 'Error fetching translations'
			});
			return { success: false, message: 'Error fetching translations' };
		}
	},
	
	// Manual refresh function for cron status
	refreshCronStatus: async () => {
		await get().fetchCronStatus();
	},
	
	testTranslations: async () => {
		set({ saving: true, error: null });
		
		try {
			const response = await cmplz_api.doAction('test_translations', {});
			if (response.request_success) {
				set({
					testResults: response.data,
					saving: false,
					error: null
				});
				return { success: true, message: 'Translation test completed!' };
			} else {
				set({
					saving: false,
					error: response.message || 'Failed to test translations'
				});
				return { success: false, message: response.message };
			}
		} catch (err) {
			set({
				saving: false,
				error: 'Error testing translations'
			});
			return { success: false, message: 'Error testing translations' };
		}
	},
	
	clearError: () => {
		set({ error: null });
	},
	
	reset: () => {
		set({
			translationDataLoaded: false,
			translationDataLoading: false,
			saving: false,
			error: null,
			translationStatus: null,
			cronStatus: null,
			cronInterval: 'cmplz_weekly',
			testResults: null
		});
	}
})); 