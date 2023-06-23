import {useState, useEffect} from "@wordpress/element";
import { __ } from '@wordpress/i18n';
import useRecordsOfConsentData from "./useRecordsOfConsentData";
import {memo} from "react";
const RecordsOfConsentControl = () => {
	const paginationPerPage = 10;
	const [ searchValue, setSearchValue ] = useState( '' );
	const [ pagination, setPagination] = useState({});
	const [ indeterminate, setIndeterminate] = useState(false);
	const [ entirePageSelected, setEntirePageSelected ] = useState( false );
	const handlePageChange = (page) => {
		setPagination({ ...pagination, currentPage: page });
	};
	const { records, downloadUrl, deleteRecords, recordsLoaded, fetchData} = useRecordsOfConsentData();
	const [ btnDisabled, setBtnDisabled ] = useState( '' );
	const [ selectedRecords, setSelectedRecords ] = useState( [] );
	const disabled = !cmplz_settings.is_premium;
	const [DataTable, setDataTable] = useState(null);
	useEffect( () => {
		import('react-data-table-component').then(({ default: DataTable }) => {
			setDataTable(() => DataTable);
		});
	}, []);

	useEffect(() => {
		if (!recordsLoaded && cmplz_settings.is_premium) fetchData();
	}, [recordsLoaded])

	const customStyles = {
		headCells: {
			style: {
				paddingLeft: '0',
				paddingRight: '0',
			},
		},
		cells: {
			style: {
				paddingLeft: '0',
				paddingRight: '0',
			},
		},
	};

	const onDeleteRecords  = async (ids) => {
		setSelectedRecords([]);
		await deleteRecords(ids);
	}

	const downloadRecords = async () => {
		let selectedRecordsCopy = records.filter((record) => {return selectedRecords.includes(record.id) && record.poc_url!== '' });
		setSelectedRecords([]);
		const downloadNext = async () => {
			if (selectedRecordsCopy.length > 0) {
				const record = selectedRecordsCopy.shift();
				const url = downloadUrl + '/' + record.poc_url;;
				setBtnDisabled(true);
				try {
					let request = new XMLHttpRequest();
					request.responseType = 'blob';
					request.open('get', url, true);
					request.send();
					request.onreadystatechange = function() {
						if (this.readyState === 4 && this.status === 200) {
							let obj = window.URL.createObjectURL(this.response);
							let element = window.document.createElement('a');
							element.setAttribute('href',obj);
							element.setAttribute('download', record.filename);
							window.document.body.appendChild(element);
							//onClick property
							element.click();
							setTimeout(function() {
								window.URL.revokeObjectURL(obj);
							}, 60 * 1000);
						}
					};

					await downloadNext();
				} catch (error) {
					console.error(error);
					setBtnDisabled(false);
				}
			}
		};

		await downloadNext();
		setBtnDisabled(false);
	};

	const handleSelectEntirePage = (e) => {
		let selected = e.target.checked;
		if ( selected ) {
			setEntirePageSelected(true);
			//add all records on this page to the selectedRecords array
			let currentPage = pagination.currentPage ? pagination.currentPage : 1;
			let filtered = handleFiltering(records);
			let recordsOnPage = filtered.slice((currentPage-1) * paginationPerPage, currentPage * paginationPerPage);
			setSelectedRecords(recordsOnPage.map(record => record.id));
		} else {
			setEntirePageSelected(false);
			setSelectedRecords([]);
		}
		setIndeterminate(false);
	}

	const onSelectRecord = (e, id) => {
		let selected = e.target.checked;
		let docs = [...selectedRecords];
		if (selected) {
			if ( !docs.includes(id) ){
				docs.push(id);
				setSelectedRecords(docs);
			}
		} else {
			//remove the record from the selected records
			docs = [...selectedRecords.filter(recordId => recordId!==id)];
			setSelectedRecords(docs);
		}
		//check if all records on this page are selected
		let currentPage = pagination.currentPage ? pagination.currentPage : 1;
		let filtered = handleFiltering(records);
		let recordsOnPage = filtered.slice((currentPage-1) * paginationPerPage, currentPage * paginationPerPage);
		let allSelected = true;
		let hasOneSelected = false;
		recordsOnPage.forEach(record => {
			if ( !docs.includes(record.id) ) {
				allSelected = false;
			} else {
				hasOneSelected = true;
			}
		});

		if (allSelected) {
			setEntirePageSelected(true);
			setIndeterminate(false);
		} else if (!hasOneSelected) {
			setIndeterminate(false);
		} else {
			setEntirePageSelected(false);
			setIndeterminate(true);
		}
	}

	const consentLabels = {
		'optin': __('Opt-in', 'complianz-gdpr'),
		'optout': __('Opt-out', 'complianz-gdpr'),
		'other': __('Other', 'complianz-gdpr'),
	}

	const handleFiltering = (records) => {
		// //sort the plugins alphabetically by filename
		records = records.sort((a, b) => {
			if (a.file < b.file) {
				return -1;
			}
			if (a.file > b.file) {
				return 1;
			}
			return 0;
		});

		//filter the plugins by search value
		records = records.filter(document => {
			return document.ip.toLowerCase().includes(searchValue.toLowerCase()) || document.services.toLowerCase().includes(searchValue.toLowerCase()) || document.id.toLowerCase().includes(searchValue.toLowerCase());
		})
		return records;
	}

	const getCategories = (row) => {
		let availableCategories = {
			do_not_track: __("DNT/GPC", "complianz-gdpr"),
			no_choice: __("No Choice", "complianz-gdpr"),
			no_warning: __("No Warning", "complianz-gdpr"),
			functional: __("Functional", "complianz-gdpr"),
			preferences: __("Preferences", "complianz-gdpr"),
			statistics: __("Statistics", "complianz-gdpr"),
			marketing: __("Marketing", "complianz-gdpr")
		}
		let categories = [];
		//for each availableCategories item, check if  row.category is set to true
		Object.keys(availableCategories).forEach(category => {
			if (parseInt(row[category]) === 1) {
				categories.push(availableCategories[category]);
			}
		});
		return categories.join(', ');
	}

	const columns = [
		{
			name: <input type="checkbox" className={indeterminate? 'indeterminate' : ''} checked={entirePageSelected} onChange={(e) => handleSelectEntirePage(e)} />,
			selector: row => row.selectControl,
		},
		{
			name: __('User ID',"complianz-gdpr"),
			selector: row => row.id,
			sortable: true,
		},
		{
			name: __('IP Adress',"complianz-gdpr"),
			selector: row => row.ip,
			sortable: true,
		},
		{
			name: __('Region',"complianz-gdpr"),
			selector: row => row.region!=='' ? <img alt="region" width="20px" height="20px" src={cmplz_settings.plugin_url+'assets/images/'+row.region+'.svg'} /> : '',
			sortable: true,
		},
		{
			name: __('Services',"complianz-gdpr"),
			selector: row => row.services,
			sortable: true,
		},
		{
			name: __('Consent',"complianz-gdpr"),
			selector: row => row.consenttype ? consentLabels[row.consenttype] : '',
			sortable: true,
		},
		{
			name: __('Categories',"complianz-gdpr"),
			selector: row => getCategories(row),
			sortable: true,
		},
		{
			name: __('Date',"complianz-gdpr"),
			selector: row => row.time,
			sortable: true,
		},
	];
	let filteredRecords = [...records];
	filteredRecords = handleFiltering(filteredRecords);

	//add the controls to the plugins
	let data = [];
	filteredRecords.forEach(record => {
		let recordCopy = {...record}
		recordCopy.selectControl = <input type="checkbox" checked={selectedRecords.includes(recordCopy.id)} onChange={(e) => onSelectRecord(e,recordCopy.id )} />
		data.push(recordCopy);
	});
	return (
		<>
			{ (disabled) && <>
				<div className="cmplz-settings-overlay">
					<div className="cmplz-settings-overlay-message"></div>
				</div>
			</>}
			<div className="cmplz-table-header">
				<div className="cmplz-table-header-controls">
					<input className="cmplz-datatable-search" type="text" placeholder={__("Search", "complianz-gdpr")} value={searchValue} onChange={ ( e ) => setSearchValue(e.target.value) } />
				</div>
			</div>

			{
				selectedRecords.length>0 &&
				<div className="cmplz-selected-document">
					{selectedRecords.length>1 && __("%s items selected", "complianz-gdpr").replace("%s", selectedRecords.length)}
					{selectedRecords.length===1 && __("1 item selected", "complianz-gdpr")}
					<div className="cmplz-selected-document-controls">
						{records.filter((record) => {return selectedRecords.includes(record.id) && record.poc_url!== '' }).length>0 && <button disabled={btnDisabled} className="button button-default cmplz-btn-reset" onClick={() => downloadRecords()}>{__("Download Proof of Consent", "complianz-gdpr")}</button> }
						<button className="button button-default cmplz-reset-button" onClick={() => onDeleteRecords(selectedRecords)}>{__("Delete", "complianz-gdpr")}</button>
					</div>
				</div>
			}
			{!disabled && DataTable && <>
				<DataTable
					columns={columns}
					data={data}
					dense
					pagination
					noDataComponent={<div className="cmplz-no-documents">{__("No records", "really-simple-ssl")}</div>}
					persistTableHead
					theme="really-simple-plugins"
					customStyles={customStyles}
					paginationPerPage={paginationPerPage}
					onChangePage={handlePageChange}
					paginationState={pagination}
				/></>
			}
		</>
	)
}
export default memo(RecordsOfConsentControl);
