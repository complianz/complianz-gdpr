import {create} from 'zustand';
import * as cmplz_api from "../../utils/api";
const useRecordsOfConsentData = create(( set, get ) => ({
	recordsLoaded: false,
	fetching:false,
	generating:false,
	progress:false,
	records: [],
	exportLink: '',
	downloadUrl: '',
	regions: [],
	fields: [],
	noData:false,
	totalRecords:0,
	searchValue: '',
	setSearchValue: (value) => set({searchValue: value}),
	paginationPerPage: 10,
	pagination:{ currentPage: 1 },
	setPagination: (value) => set({pagination: value}),
	orderBy: 'ID',
	setOrderBy: (value) => set({orderBy: value}),
	order: 'DESC',
	setOrder: (value) => set({order: value}),
	deleteRecords: async (ids) => {
		//get array of records to delete
		let deleteRecords = get().records.filter(record => ids.includes(record.id));
		//remove the ids from the records array
		set((state) => ({
			records: state.records.filter(record => !ids.includes(record.id)),
		}));
		let data = {};
		data.records = deleteRecords;
		await cmplz_api.doAction('delete_records_of_consent', data).then((response) => {
			return response;
		}).catch((error) => {
			console.error(error);
		});
	},
	fetchData: async ( ) => {
		if (get().fetching) return;
		set({fetching:true});
		let data = {}
		data.per_page = get().paginationPerPage;
		data.page = get().pagination.currentPage;
		data.order = get().order.toUpperCase();
		data.orderBy = get().orderBy;
		data.search = get().searchValue;
		const { records, totalRecords, regions, download_url} = await cmplz_api.doAction('get_records_of_consent', data).then((response) => {
			return response;
		}).catch((error) => {
			console.error(error);
		});
		set(() => ({
			recordsLoaded: true,
			records: records,
			regions: regions,
			totalRecords: totalRecords,
			downloadUrl: download_url,
			fetching:false,
		}));
	},
	downloadRecordProof: async (id) => {
		const response = await cmplz_api.doAction('get_record_of_consent_pdf_url', { id }).catch((error) => {
			console.error(error);
		});
		return response?.url || '';
	},
	startExport: async () => {
		set({
			generating: true,
			progress:0,
			exportLink: '',
		})
	},
	fetchExportRecordsOfConsentProgress: async (statusOnly, startDate, endDate) => {
		statusOnly = typeof statusOnly !== 'undefined' ? statusOnly : false;
		if (!statusOnly) {
			set({ generating:true });
		}

		let data = {};
		data.startDate = startDate;
		data.endDate = endDate;
		data.statusOnly = statusOnly;
		const {progress, exportLink, noData} = await cmplz_api.doAction('export_records_of_consent', data).then((response) => {
			return response;
		}).catch((error) => {
			console.error(error);
		});
		let generating = false;
		if (progress<100 ){
			generating = true;
		}
		set({progress:progress, exportLink:exportLink, generating:generating,noData:noData});

	},
}));

export default useRecordsOfConsentData;
