import {memo, useEffect, useState} from "@wordpress/element";;
import AceEditor from "react-ace";
import './AceEditor.scss';
import "ace-builds/src-noconflict/mode-css";
import "ace-builds/src-noconflict/theme-monokai";
import "ace-builds/src-noconflict/ext-language_tools";
import {__} from "@wordpress/i18n";
import Icon from "../../utils/Icon";

const AceEditorControl = (props) => {
	let mode = props.mode ? props.mode : 'css';
	let height=props.height?props.height:'200px';
	let placeholder = props.field && props.field.default ? props.field.default : props.placeholder;
	const [inputValue, setInputValue] = useState(props.value);
	const [scriptWarning, setScriptWarning] = useState(false);
	//because an update on the entire Fields array is costly, we only update after the user has stopped typing
	useEffect(() => {
		if ( inputValue === props.value){
			return;
		}
		const typingTimer = setTimeout(() => {
			props.onChange(inputValue);
		}, 500);

		return () => {
			clearTimeout(typingTimer);
		};
	}, [inputValue]);

	useEffect(() => {
		if ( inputValue !== props.value ) {
			setInputValue(props.value);
		}
	}, [props.value]);

	const handleChange = ( value ) => {
		//strip off <script> tags
		if (value.includes('<script>') || value.includes('</script>')) {
			setScriptWarning(true);
		}
		value = value.replace(/<script>/gi, "");
		value = value.replace(/<\/script>/gi, "");
		setInputValue(value);
	};
	let editorClass = props.disabled ? 'cmplz-editor-disabled' : '';

	return (
		<div className={editorClass}>
			{scriptWarning &&
				<div className="cmplz-error-text">
					<Icon name={'error'} size={13} color={'red'}/>
					<p>{__('Write your JavaScript without wrapping it in script tags.', 'complianz-gdpr')}</p>
				</div>
			}
			{ <AceEditor
				readOnly={props.disabled}
				placeholder={'//'+placeholder} //make it look like a comment
				mode={mode}
				theme="monokai"
				width="100%"
				height={height}
				onChange={(value) => handleChange(value)}
				fontSize={12}
				showPrintMargin={true}
				showGutter={true}
				highlightActiveLine={true}
				value={inputValue}
				setOptions={{
					enableBasicAutocompletion: false,
					enableLiveAutocompletion: false,
					enableSnippets: false,
					showLineNumbers: true,
					tabSize: 2	,
					useWorker: false
				}}/>
			}
		</div>
	);
}
export default memo(AceEditorControl)
