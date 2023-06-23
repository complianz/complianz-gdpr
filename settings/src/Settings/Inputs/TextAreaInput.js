import {memo, useEffect, useRef, useState} from 'react';
import './Input.scss';
import './TextAreaInput.scss';

const TextAreaInput = ({
	value,
	onChange,
	required,
	placeholder,
	disabled,
	id,
	name,
}) => {
	const inputId = id || name;
	const textareaRef = useRef(null);
	const [inputValue, setInputValue] = useState('');

	//ensure that the initial value is set
	useEffect(() => {
		setInputValue(value);
	},[]);

	//because an update on the entire Fields array is costly, we only update after the user has stopped typing
	useEffect(() => {
		const typingTimer = setTimeout(() => {
			onChange(inputValue);
		}, 500);

		return () => {
			clearTimeout(typingTimer);
		};
	}, [inputValue]);

	const handleChange = ( value ) => {
		setInputValue(value);
	};

	const autoGrow = (element) => {
		element.style.height = 'auto';
		element.style.height = element.scrollHeight + 'px';
	};

	useEffect(() => {
		if (textareaRef.current) {
			autoGrow(textareaRef.current);
		}
	}, [value]);
	return (
		<div className="cmplz-input-group cmplz-text-area-input-group">
			<textarea
				ref={textareaRef}
				id={inputId}
				name={name}
				value={inputValue}
				onChange={
				(event) => {
					handleChange(event.target.value);
				}}
				required={required}
				placeholder={placeholder}
				disabled={disabled}
				className="cmplz-text-area-input-group__input"
			/>
		</div>
	);
};

export default memo(TextAreaInput);
