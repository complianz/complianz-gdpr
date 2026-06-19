import useProcessingAgreementsData from "./ProcessingAgreementsData";
import {useState, useEffect} from "@wordpress/element";
import { __ } from '@wordpress/i18n';
import {FormFileUpload} from "@wordpress/components";
import Field from "../Fields/Field";
import useFields from "../Fields/FieldsData";
import Icon from "../../utils/Icon";
import {upload} from "../../utils/upload";
import {memo} from "@wordpress/element";
import SelectInput from "../Inputs/SelectInput";
import TextInput from "../Inputs/TextInput";
import LabelWrapper from "../Fields/LabelWrapper";
const CreateProcessingAgreements = () => {
	const { fields, fileName, fetching, loadingFields, updating, regions, resetEditDocumentId, fetchData, fetchFields, updateField, save, editDocumentId, region, setRegion, serviceName, setServiceName} = useProcessingAgreementsData();
	const [ createBtnDisabled, setCreateBtnDisabled ] = useState( true );
	const [step, setStep] = useState(0);
	const {allRequiredFieldsCompleted, fetchAllFieldsCompleted, fieldsLoaded, addHelpNotice, showSavedSettingsNotice, removeHelpNotice} = useFields();

	let scrollAnchor = React.createRef();
	const [file, setFile] = useState(false)
	const [uploading, setUploading] = useState(false);
	const [uploadDisabled, setUploadDisabled] = useState(true);
	const [uploadError, setUploadError] = useState(false);
	const [errorNotice, setErrorNotice] = useState('');

	useEffect( () => {
		if ( editDocumentId && scrollAnchor.current ) {
			scrollAnchor.current.scrollIntoView({ behavior: 'smooth', block: 'start' });
		}
	},[editDocumentId]);

	const fieldsPerStep = 5;

	useEffect (() => {
		fetchAllFieldsCompleted();
	}, [fieldsLoaded])

	useEffect(() => {
		if (region!=='' && serviceName!=='') {
			setCreateBtnDisabled(false);
		} else {
			setCreateBtnDisabled(true);
		}
	}, [region, serviceName, fetching, editDocumentId])

	const onChangeHandler = (fieldId, value) => {
		updateField(fieldId, value);
	}


	useEffect(() => {
		const handleUpload = async () => {
			if (!file ) return;

			if (file.type!=='application/pdf' && file.type!=='application/msword' && file.type!=='application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
				setUploadDisabled(true);
				addHelpNotice('create-processing-agreements', 'warning', __("You can only upload .pdf, .doc or .docx files","complianz-gdpr"), __("Incorrect extension","complianz-gdpr"),false);
			} else {
				setUploadDisabled(false);
				removeHelpNotice('create-processing-agreements');
			}

			if (file){
				setCreateBtnDisabled(true);
			}
		}
		handleUpload();
	}, [file])

	const onUploadHandler = (e) => {
		setUploadDisabled(true);
		setUploading(true);

		upload('upload_processing_agreement', file, {region:region,serviceName:serviceName} ).then((response) => {
			if (response.data.success) {
				showSavedSettingsNotice(__("File uploaded successfully", "complianz-gdpr"));
			} else {
				// Display the error message from the backend
				setErrorNotice(response.data.error_message || __("Upload failed", "complianz-gdpr"));
				setUploadError(true);
			}
			setUploading(false);
			setFile(false);
			resetEditDocumentId();
			fetchData();
			return true;
		}).catch((error) => {
			console.error(error);
			addHelpNotice('upload_error', 'error', __("Upload failed due to network error", "complianz-gdpr"), __("Network Error","complianz-gdpr"), false);
			setUploading(false);
			setFile(false);
		});
	}

	const saveFields = async () => {
		await save(region, serviceName);
		showSavedSettingsNotice();
	}

	const saveAndExit = async () => {
		await save(region, serviceName);
		setStep(0);
		showSavedSettingsNotice();
		resetEditDocumentId();
	}

	useEffect(() => {
		if (region!=='' && serviceName!=='' && !fetching) {
			setCreateBtnDisabled(false);
		}
	}, [region, serviceName, fetching])

	const onCreateHandler = async () => {
		await fetchFields(region);
		setStep(1);
	}

	//select the next 5 fields from fields
	const getStepFields = (activeFields) => {
		const start = (step-1)*fieldsPerStep;
		const end = start+fieldsPerStep;
		return activeFields.slice(start, end);
	}
	let visibleFields = fields.filter(field =>field => typeof field.conditionallyDisabled==='undefined' || field.conditionallyDisabled ===false);
	let lastStep = Math.ceil(visibleFields.length/fieldsPerStep);
	let selectedFields = getStepFields(fields);
	return (
		<>
			{!allRequiredFieldsCompleted && <div className="cmplz-locked">
				<div className="cmplz-locked-overlay">
					<span className="cmplz-task-status cmplz-warning">{__("Incomplete","complianz-gdpr")}</span>
					<span>
						{__("The wizard has not been completed yet, but this field requires information from the wizard. Please complete the wizard first.","complianz-gdpr")}
					</span>
				</div>
			</div>}
			{step===0 &&
				<>
					{ editDocumentId &&
						<div className="cmplz-selected-document">
							{fileName}
						</div>
					}
					<LabelWrapper id={'region_for_processing_agreement'}
								  label={__("Region","complianz-gdpr")}
								  required={true} type={'select'}/>

					<SelectInput
						innerRef={scrollAnchor}
						disabled={updating}
						onChange={ ( fieldValue ) => setRegion(fieldValue) }
						options={ regions }
						value= { region }
					   required={true}
					/>
					<LabelWrapper id={'servicename_for_processing_agreement'}
								  label={__("Service name","complianz-gdpr")}
								  required={true} type={'text'}/>
					<TextInput
						placeholder={ __("e.g. Alphabet Inc", "complianz-gdpr") }
						onChange={ ( fieldValue ) => setServiceName(fieldValue) }
						value= { serviceName ? serviceName : '' }
						disabled={updating}
						required={true}
					/>
					<div className="cmplz-table-header">
						<div className='cmplz-table-header-controls'>
							{editDocumentId && <>
								<button disabled={updating} className="button button-default" onClick={()=>{resetEditDocumentId();setStep(0)}}>{__("Cancel",'complianz-gdpr')}</button>
								<button disabled={updating} className="button button-primary" onClick={()=>setStep(step+1)}>{__("Next",'complianz-gdpr')}</button>
								<button disabled={updating} className="button button-primary" onClick={()=>saveFields()}>{__("Save",'complianz-gdpr')}</button>
							</>}
							{!editDocumentId && <>
								{file && file.name}
								<FormFileUpload
									accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
									icon={<Icon name='upload' color='black' />}//formfile upload overrides size prop. We override that in the icon component
									onChange={ ( event ) => setFile(event.currentTarget.files[0]) }
								>
									{__("Select file","complianz-gdpr")}
								</FormFileUpload>
								<button disabled={uploadDisabled} className="button button-default"  onClick={(e) => onUploadHandler(e)}>
									{__("Upload","complianz-gdpr")}
									{uploading && <Icon name = "loading" color = 'grey' />}
								</button>

								<button disabled={createBtnDisabled || loadingFields} className="button cmplz-button button-primary" onClick={()=>onCreateHandler() }>
									{__("Create",'complianz-gdpr')}
									{loadingFields && <Icon name = "loading" color = 'grey' />}
								</button>
							</>}
						</div>
					</div>
					{uploadError &&
						<div className="cmplz-error-text" style={{display: uploadError ? 'block' : 'none'}}>
							<p>{errorNotice}</p>
						</div>
					}
				</>
			}
			{
				step>0 && <>
					{  step <= lastStep && selectedFields.map((field, i) =>
						<Field key={i} index={i} field={field} isCustomField={true} customChangeHandler={(field, value)=>onChangeHandler(field, value)}/>)
					}
					<div className="cmplz-table-header">
						<div className='cmplz-table-header-controls'>
							<button disabled={updating} className="button button-default" onClick={()=>{resetEditDocumentId();setStep(0)}}>{__("Cancel",'complianz-gdpr')}</button>
							<button className="button button-default" onClick={()=>setStep(step-1)}>{__("Previous","complianz-gdpr")}</button>

							{ step < lastStep &&
								<>
									<button className="button button-primary" onClick={()=>setStep(step+1)}>{__("Next","complianz-gdpr")}</button>
								</>
							}

							{  step === lastStep &&
								<>
									<button className="button button-primary" onClick={()=>saveAndExit()}>
										{__("Finish","complianz-gdpr")}
										{updating && <Icon name = "loading" color = 'grey' />}
									</button>
								</>
							}
							{editDocumentId && step < lastStep && <>
								<button disabled={updating} className="button button-primary" onClick={()=>saveFields()}>{__("Save",'complianz-gdpr')}</button>
							</>}
					</div></div>
				</>
		}
		</>
	)
}
export default memo(CreateProcessingAgreements);
