import SwitchInput from '../Inputs/SwitchInput';
import readMore from '../../utils/readMore';
import TextInput from '../Inputs/TextInput';
import SelectInput from '../Inputs/SelectInput';
import {__} from '@wordpress/i18n';
import useIntegrations from './IntegrationsData';
import CheckboxGroup from '../Inputs/CheckboxGroup';

const Category = (props) => {
	const {setScript, fetching, placeholders} = useIntegrations();
	const script = props.script;
	const type = props.type;

	const onChangeHandler = (value, property) => {
		let copyScript = {...script};
		copyScript[property] = value;
		setScript(copyScript, props.type);
	};

	return (
		<>
			<div className="cmplz-details-row cmplz-details-row__checkbox">
				<label>{__('Placeholder', 'complianz-gdpr')}</label>
				<CheckboxGroup
					id={script.id + 'placeholder'}
					disabled={fetching}
					value={script.enable_placeholder}
					onChange={(value) => onChangeHandler(value, 'enable_placeholder')}
					options={{true: __('Enable placeholder',
							'complianz-gdpr')}}
				/>
			</div>

			{!!script.enable_placeholder && <>

				{type === 'block_script' &&
					<div className="cmplz-details-row cmplz-details-row__checkbox">
						<CheckboxGroup
							id={script.id + 'iframe'}
							disabled={fetching}
							value={script.iframe || ''}
							onChange={(value) => onChangeHandler(value || '', 'iframe')}
							options={{true: __('The blocked content is an iframe',
								'complianz-gdpr')}}
						/>
					</div>
				}

				{!script.iframe &&
					<div className="cmplz-details-row cmplz-details-row">
						<p>{__('Enter the div class or ID that should be targeted.',
							'complianz-gdpr')}
							{readMore(
								'https://complianz.io/help/wordpress/configuration/integrating-plugins/#placeholder/')}</p>
						<TextInput
							disabled={fetching}
							value={script.placeholder_class || ''}
							onChange={(value) => onChangeHandler(value || '',
								'placeholder_class')}
							name={'placeholder_class'}
							placeholder={__('Your CSS class', 'complianz-gdpr')}
						/>
					</div>
				}

				<div className="cmplz-details-row cmplz-details-row__checkbox">
					<SelectInput
						disabled={fetching}
						value={script.placeholder ? script.placeholder : 'default'}
						options={placeholders}
						onChange={(value) => onChangeHandler(value || 'default',
							'placeholder')}
					/>
				</div>
			</>
			}
		</>
	);
};
export default Category;


