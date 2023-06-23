import {memo, useEffect, useState} from 'react';
import './Input.scss';

const TextInput = ({
	value,
	onChange,
	required,
	defaultValue,
	disabled,
	id,
	name,
	placeholder
}) => {
	const inputId = id || name;
	const [inputValue, setInputValue] = useState('');

	//ensure that the initial value is set
	useEffect(() => {
		setInputValue(value || '');
	},[value]);

	//because an update on the entire Fields array is costly, we only update after the user has stopped typing
	useEffect(() => {
		// skip first render
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

	return (
		<div className="cmplz-input-group cmplz-text-input-group">
			<input
				type='text'
				id={inputId}
				name={name}
				value={inputValue}
				onChange={(event) => handleChange(event.target.value)}
				required={required}
				disabled={disabled}
				className="cmplz-text-input-group__input"
				placeholder={placeholder}
			/>
		</div>
	);
};

export default memo(TextInput);
