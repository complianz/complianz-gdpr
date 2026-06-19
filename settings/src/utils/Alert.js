import Icon from './Icon';
import useFields from '../Settings/Fields/FieldsData';

/**
 * Generic alert/notice block component.
 *
 * Props:
 *   title      {string}   - Bold heading text
 *   subtitle   {string}   - Short sub-heading below title (optional)
 *   body       {string}   - Longer description text (optional)
 *   icon       {string}   - Icon name (default 'warning')
 *   iconColor  {string}   - Icon color token (default 'orange')
 *   ctaLabel   {string}   - CTA button/link label (optional)
 *   ctaUrl     {string}   - External URL → renders <a>; omit for button CTA
 *   ctaFieldId {string}   - utm_content value for getReferralUrl (optional)
 *   onCtaClick {Function} - Click handler → renders <button>; ignored when ctaUrl set
 */
const Alert = ( { title, subtitle, body, icon = 'warning', iconColor = 'orange', ctaLabel, ctaUrl, ctaFieldId = '', onCtaClick } ) => {
	const resolvedUrl = ctaUrl
		? useFields.getState().getReferralUrl( 'warnings', ctaFieldId, ctaUrl )
		: null;

	return (
		<div className="cmplz-wscscan-alert">
			<Icon name={icon} color={iconColor} size={48}/>
			<div className="cmplz-wscscan-alert-group">
				<div className="cmplz-wscscan-alert-group-title">{title}</div>
				{subtitle && <div className="cmplz-wscscan-alert-group-desc">{subtitle}</div>}
			</div>
			{body && <div className="cmplz-wscscan-alert-desc-long">{body}</div>}
			{( resolvedUrl || onCtaClick ) && ctaLabel && (
				<div>
					{resolvedUrl ? (
						<a href={resolvedUrl} target="_blank" rel="noopener noreferrer" className="button button-default">
							{ctaLabel}
						</a>
					) : (
						<button type="button" onClick={onCtaClick} className="button button-default">
							{ctaLabel}
						</button>
					)}
				</div>
			)}
		</div>
	);
};

export default Alert;