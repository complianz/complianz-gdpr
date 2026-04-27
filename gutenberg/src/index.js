
/**
 * BLOCK:Complianz Documents block
 *
 * Registering the Complianz Privacy Suite documents block with Gutenberg.
 */

import * as api from './utils/api';
const { __ } = wp.i18n;
const { registerBlockType } = wp.blocks;
const { InspectorControls, BlockControls, useBlockProps} = wp.blockEditor;
const { PanelBody, PanelRow, SelectControl, TextControl, TextareaControl, ToolbarButton, ToolbarGroup, Icon} = wp.components;
import {useState, useEffect} from "@wordpress/element";
import DOMPurify from 'dompurify';

/**
 *  Set custom Complianz Icon
 */

// SVG icon
const iconEl = () => (
	<Icon icon={
		<svg id="uuid-098657ec-4091-4c5d-b2f8-761b37dc9655" xmlns="http://www.w3.org/2000/svg"
			 viewBox="0 0 22 19.26">
			<path className="uuid-fb9dd603-42c0-4281-8513-899f01887edf"
				  d="m13.54,4.47c-.06-.06-.15-.06-.21,0l-4.9,4.9.21.21,4.9-4.9c.06-.06.06-.16,0-.21h0Zm-4.48,3.19c-.06-.06-.15-.06-.21,0l-1.06,1.06.21.21,1.06-1.06c.06-.06.06-.15,0-.21ZM15.14,1.21c.46,0,.9.19,1.22.53.61.66.56,1.7-.08,2.34l-2.89,2.89c-.06.06-.06.15,0,.21.06.06.15.06.21,0l2.89-2.89c.76-.76.8-2.05.04-2.81-.38-.38-.88-.57-1.39-.57s-1,.19-1.39.57l-6.6,6.6.21.21L13.96,1.7c.31-.32.73-.49,1.17-.49h0Zm-5.64,3.46c-.06-.06-.15-.06-.21,0l-2.77,2.78.21.21,2.78-2.78c.06-.06.06-.15,0-.21h0Zm.74-.52l3.09-3.09c.48-.48,1.13-.75,1.81-.75.52,0,1.01.16,1.43.44.06.04.14.04.19-.02.07-.07.06-.18-.02-.23-.48-.33-1.04-.49-1.6-.49-.73,0-1.47.28-2.03.84l-3.09,3.1c-.06.06-.06.15,0,.21.06.06.16.06.21,0h0Zm2.63,3.56c-.06-.06-.15-.06-.21,0l-2.3,2.3c-.06.06-.06.15,0,.21h0c.06.06.15.06.21,0l2.3-2.3c.06-.06.06-.15,0-.21h0Zm3.46-2.39l-5.97,5.97.21.21,5.97-5.97c.06-.06.06-.15,0-.21-.06-.06-.16-.06-.22,0Zm1.2-4.03c-.05-.08-.17-.09-.23-.03-.05.05-.06.13-.02.19.28.42.43.91.43,1.42,0,.64-.23,1.24-.65,1.71-.06.06-.06.15,0,.21.06.06.16.06.22,0,.88-.98.97-2.43.25-3.5h0Zm-3.24,2.66l.93-.93c.06-.06.06-.15,0-.21-.06-.06-.15-.06-.21,0l-.93.93c-.06.06-.06.15,0,.21.06.06.16.06.21,0h0Zm1.59-1.84c-.2-.19-.47-.29-.73-.29s-.54.1-.75.31l-4.8,4.81c-.06.06-.06.15,0,.21.06.06.15.06.21,0l4.78-4.79c.12-.12.28-.21.46-.24.24-.03.47.05.63.21.14.14.22.33.22.53s-.08.39-.22.53l-6.6,6.6.21.21,6.57-6.57c.42-.42.44-1.13.01-1.54h0Z"/>
			<g>
				<rect className="uuid-fb9dd603-42c0-4281-8513-899f01887edf" x="7.81" y="5.93" width="2.91" height="5.64"
					  transform="translate(-3.47 9.11) rotate(-45)"/>
				<path className="uuid-fb9dd603-42c0-4281-8513-899f01887edf"
					  d="m10.7,11.35l-4.62-4.62h0l-1.28-1.28c-.55-.55-1.27-.83-1.99-.83s-1.44.27-1.99.83c-1.1,1.1-1.1,2.89,0,3.99l3.54,3.54h0l1.6,1.6c.06.06.15.06.21,0,0,0,0,0,0,0s0,0,0,0l.36-.36.03.88s0,.04.01.06c0,.02.02.04.03.05.06.06.15.06.21,0h0s0-.01,0-.01l3.82-3.82h0s.04-.04.04-.04Z"/>
				<path className="uuid-fb9dd603-42c0-4281-8513-899f01887edf"
					  d="m11.09,10.9l4.62-4.62h0l1.28-1.28c.55-.55.83-1.27.83-1.99s-.27-1.44-.83-1.99c-1.1-1.1-2.89-1.1-3.99,0l-3.54,3.54h0l-1.6,1.6c-.06.06-.06.15,0,.21,0,0,0,0,0,0s0,0,0,0l.36.36-.88.03s-.04,0-.06.01c-.02,0-.04.02-.05.03-.06.06-.06.15,0,.21h0s0,.01,0,.01l3.82,3.82h0s.04.04.04.04Z"/>
			</g>
		</svg>

		 } />
);

const selectDocument = ({ className, isSelected, attributes, setAttributes }) => {
	const [documents, setDocuments] = useState([]);
	const [documentDataLoaded, setDocumentDataLoaded] = useState(false);
	const [selectedDocument, setSelectedDocument] = useState(attributes.selectedDocument);
	const [documentSyncStatus, setDocumentSyncStatus] = useState(attributes.documentSyncStatus);
	const [customDocumentHtml, setCustomDocumentHtml] = useState('');

	useEffect(() => {
		api.getDocuments().then( ( response ) => {
			let documents = response.data;
			setDocuments(documents);
			setDocumentDataLoaded(true);
			let documentData = false;
			if ( documents && 0 !== selectedDocument ) {
				// If we have a selected document, find that document and add it.
				documentData = documents.find( ( item ) => { return item.id === selectedDocument } );
				if (documents.length === 0) {
					setAttributes({
						hasDocuments: false,
					});
				}
			}

			let tempHtml = '';
			if ( documentData ) {
				tempHtml = documentData.content;
			}
			if ( attributes.customDocument && attributes.customDocument.length>0 ){
				tempHtml = attributes.customDocument;
			}
			setCustomDocumentHtml(tempHtml);
		});
	}, [])

	const onChangeSelectDocument = (value) => {
		// Set the state
		setSelectedDocument(value);

		// Set the attributes
		setAttributes({
			selectedDocument: value,
		});
	}

	const onChangeCustomDocument = (html) => {
		setAttributes({
			customDocument: html,
		});
	}

	const onChangeSelectDocumentSyncStatus = (value) =>{
		setDocumentSyncStatus(value);
		setAttributes({
			documentSyncStatus: value,
		});

		//we reset the customDocument data
		const selectedDocumentData = documents.find((item) => {
			return item.id === selectedDocument
		});
		setCustomDocumentHtml(selectedDocumentData.content);
		setAttributes({
			customDocument: selectedDocumentData.content,
		});
	}

	let options = [{value: 0, label: __('Select a document', 'complianz-gdpr')}];
	let output = __('Loading...', 'complianz-gdpr');
	let document_status_options = [
		{value: 'sync', label: __('Synchronize document with Complianz', 'complianz-gdpr')},
		{value: 'unlink', label: __('Edit document and stop synchronization', 'complianz-gdpr')},
	];

	if ( !attributes.hasDocuments ){
		output = __('No documents found. Please finish the Complianz Privacy Suite wizard to generate documents', 'complianz-gdpr');
	}

	//preview
	if ( attributes.preview ) {
		return(
			<img alt="preview" src={complianz.cmplz_preview} />
		);
	}

	if ( documentDataLoaded ) {
		output = isSelected ? __("Select a document type from the dropdownlist", 'complianz-gdpr') : __('Click this block to show the options', 'complianz-gdpr');
		documents.forEach((item) => {
			options.push({value: item.id, label: item.title});
		});
	}


	//load content
	if ( attributes.selectedDocument!==0 && documentDataLoaded && attributes.selectedDocument.length>0 ) {
		const documentData = documents.find((item) => {
			return item.id === attributes.selectedDocument
		});
		if (documentData) output = documentData.content;
	}

	if ( documentSyncStatus==='sync' ) {
		return [
			!!isSelected && (
				<InspectorControls key='inspector-document'>
					<PanelBody title={ __('Document settings', 'complianz-gdpr' ) } initialOpen={ true } >
						<PanelRow key="1">
							<SelectControl onChange={ (e) => onChangeSelectDocument(e) }
										   value={ selectedDocument }
										   label={__('Select a document', 'complianz-gdpr')}
										   options={options}
										   __next40pxDefaultSize
										   __nextHasNoMarginBottom/>
						</PanelRow>
						<PanelRow key="2">
							<SelectControl onChange={(e) => onChangeSelectDocumentSyncStatus(e) }
										   value={documentSyncStatus}
										   label={__('Document sync status', 'complianz-gdpr')}
										   options={document_status_options}
										   __next40pxDefaultSize
										   __nextHasNoMarginBottom/>
						</PanelRow>
					</PanelBody>
				</InspectorControls>
			),

			<div key={attributes.selectedDocument} className={className} dangerouslySetInnerHTML={{__html: DOMPurify.sanitize( output ) }}></div>,

		]
	} else {
		let html = documentDataLoaded ? customDocumentHtml : __('Loading...', 'complianz-gdpr');
		let syncClassName = className + ' cmplz-unlinked-mode';
		return [
			!!isSelected && (
				<InspectorControls key='inspector-document-settings'>
					<PanelBody title={ __('Document settings', 'complianz-gdpr' ) } initialOpen={ true } >
						<PanelRow>
							<SelectControl onChange={(e) => onChangeSelectDocument(e) }
										   value={attributes.selectedDocument}
										   label={__('Select a document', 'complianz-gdpr')}
										   options={options}
										   __next40pxDefaultSize
										   __nextHasNoMarginBottom/>
						</PanelRow>
						<PanelRow>

							<SelectControl onChange={ (e) => onChangeSelectDocumentSyncStatus(e) }
										   value={documentSyncStatus}
										   label={__('Document sync status', 'complianz-gdpr')}
										   options={document_status_options}
										   __next40pxDefaultSize
										   __nextHasNoMarginBottom/>
						</PanelRow>
					</PanelBody>
				</InspectorControls>
			),

			<div contentEditable={true}
				 onInput={(e)=>onChangeCustomDocument(e.currentTarget.innerHTML)}
				 dangerouslySetInnerHTML={{ __html: DOMPurify.sanitize(customDocumentHtml) }}
				 className={syncClassName}
			></div>

		]
	}

}

/**
 * Register: a Gutenberg Block.
 *
 * Registers a new block provided a unique name and an object defining its
 * behavior. Once registered, the block is made editor as an option to any
 * editor interface where blocks are implemented.
 *
 * @link https://wordpress.org/gutenberg/handbook/block-api/
 * @param  {string}   name     Block name.
 * @param  {Object}   settings Block settings.
 * @return {?WPBlock}          The block, if it has been successfully
 *                             registered; otherwise `undefined`.
 */



registerBlockType('complianz/document', {
	apiVersion: 3,
	title: __('Legal document - Complianz', 'complianz-gdpr'), // Block title.
	icon: iconEl, // Block icon from Dashicons → https://developer.wordpress.org/resource/dashicons/.
	category: 'widgets',
	example: {
		attributes: {
			'preview' : true,
		},
	},
	keywords: [
		__('Privacy Statement', 'complianz-gdpr'),
		__('Cookie Policy', 'complianz-gdpr'),
		__('Disclaimer', 'complianz-gdpr'),
	],
	//className: 'cmplz-document',
	attributes: {
		documentSyncStatus: {
			type: 'string',
			default: 'sync'
		},
		customDocument: {
			type: 'string',
			default: ''
		},
		hasDocuments: {
			type: 'string',
			default: 'false',
		},
		content: {
			type: 'string',
			source: 'html',
			selector: 'p',
		},
		selectedDocument: {
			type: 'string',
			default: '',
		},
		documents: {
			type: 'array',
		},
		document: {
			type: 'array',
		},
		preview: {
			type: 'boolean',
			default: false,
		}
	},
	/**
	 * The edit function describes the structure of your block in the context of the editor.
	 * This represents what the editor will render when the block is used.
	 *
	 * The "edit" property must be a valid function.
	 *
	 * @link https://wordpress.org/gutenberg/handbook/block-api/block-edit-save/
	 */

	edit:selectDocument,

	/**
	 * The save function defines the way in which the different attributes should be combined
	 * into the final markup, which is then serialized by Gutenberg into post_content.
	 *
	 * The "save" property must be specified and must be a valid function.
	 *
	 * @link https://wordpress.org/gutenberg/handbook/block-api/block-edit-save/
	 */

	save: function() {
		// Rendering in PHP
		return null;
	},
});

registerBlockType( 'complianz/consent-area', {
	apiVersion: 3,
	title: __( 'Consent Area Block' ),
	icon: iconEl,
	category: 'widgets',
	example: {
		attributes: {
			'preview' : true,
		},
	},
	attributes: {
		category:{
			type: 'string',
			default: 'marketing',
		},
		service:{
			type: 'service',
			default: 'general',
		},
		blockId:{
			type: 'string',
			default: ''
		},
		postId:{
			type: 'integer',
			default: -1,
		},
		placeholderContent: {
			type: 'string',
			default: '',
		},
		consentedContent: {
			type: 'string',
			default: '',
		},
	},

	edit: ( props ) => {
		const { getCurrentPostId } = wp.data.select("core/editor");
		const postId = getCurrentPostId();
		const { attributes, setAttributes , isSelected, className} = props;
		const [ view, setView ] = useState( 'consented' );
		const [ isPreview, setIsPreview ] = useState( false );
		const onViewChange = ( value ) => {
			setView( value );
		};

		useEffect( () => {
			setAttributes( { postId: postId, category:attributes.category, service:attributes.service } );
			if (attributes.blockId==='') {
				let blockId = (Math.random() + 1).toString(36).substring(7);
				setAttributes( { blockId: blockId } );
			}
		}, [attributes] );

		const blockProps = useBlockProps();
		let disabled = !complianz.user_can_unfiltered_html;

		return [
			!!isSelected && (
				<InspectorControls key='inspector-document-settings'>
					<PanelBody title={ __('Document settings', 'complianz-gdpr' ) } initialOpen={ true } >
						<PanelRow key="1">
							<SelectControl
								disabled={disabled}
								label={ __( 'Category','complianz-gdpr' ) }
								value={ attributes.category }
								options={ [
									{ label: __( 'Preferences','complianz-gdpr' ), value: 'preferences' },
									{ label: __( 'Statistics','complianz-gdpr' ), value: 'statistics' },
									{ label: __( 'Marketing','complianz-gdpr' ), value: 'marketing' },
								] }
								onChange={ ( value ) => setAttributes( { category: value } ) }
								__next40pxDefaultSize
								__nextHasNoMarginBottom
							/>
						</PanelRow>
						<PanelRow key="2">
							<TextControl
								disabled={disabled}
								label={ __( 'Service','complianz-gdpr' ) }
								value={ attributes.service }
								onChange={ ( value ) => setAttributes( { service: value } ) }
							/>
						</PanelRow>
						<PanelRow key="3">
							<SelectControl
								disabled={disabled}
								label={ __( 'View' ) }
								value={ view }
								options={ [
									{ label: __( 'Placeholder','complianz-gdpr' ), value: 'placeholder' },
									{ label: __( 'Default','complianz-gdpr' ), value: 'consented' },
								] }
								onChange={ (value )=>onViewChange(value) }
								__next40pxDefaultSize
								__nextHasNoMarginBottom
							/>
						</PanelRow>
					</PanelBody>
				</InspectorControls>
			),
			<div {...blockProps} key="cmplz-consent-area">
				{
					<BlockControls>
						<ToolbarGroup>
							<ToolbarButton
								className="components-tab-button"
								isPressed={ ! isPreview }
								onClick={ () => setIsPreview(false) }
							>
								HTML
							</ToolbarButton>
							<ToolbarButton
								className="components-tab-button"
								isPressed={ isPreview }
								onClick={ () => setIsPreview(true) }
							>
								{ __( 'Preview' ) }
							</ToolbarButton>
						</ToolbarGroup>
					</BlockControls>
				}
				{disabled && <div>{__("You do not have sufficient permissions to edit this block.","complianz-gdpr")}</div>}
				{ !isPreview && <>
					{
						view==='placeholder' &&
						<TextareaControl key="1"
							 disabled={disabled}
							 placeholder={ __( 'You can add custom HTML to create your own placeholder. This placeholder is visible before consent.','complianz-gdpr' ) }
							 className={className}
							 value={attributes.placeholderContent}
							 onChange={ ( value ) => setAttributes( { placeholderContent: value } ) }
						/>
					}
					{
						view === 'consented' &&
						<TextareaControl key="2"
							 disabled={disabled}
							 placeholder={ __( 'You can add custom HTML that requires consent. In the right-side bar you will find the options for this custom block. For instructions, please go to complianz.io/gutenberg for more information.','complianz-gdpr' ) }
							 className={className}
							 value={attributes.consentedContent}
							 onChange={ ( value ) => setAttributes( { consentedContent: value } ) }
						/>
					}

				</>}
				{ !!isPreview && <>
					{ view === 'placeholder' &&
						<div>
							<div dangerouslySetInnerHTML={{__html: DOMPurify.sanitize( attributes.placeholderContent ) } }></div>
						</div>
					}
					{ view === 'consented' &&
						<div>
							<div dangerouslySetInnerHTML={{__html: DOMPurify.sanitize( attributes.consentedContent ) } }></div>
						</div>
					}

				</>}

			</div>
		]
	},
	save: function() {
		return null;
	},

} );

