import { memo, useCallback, useEffect } from '@wordpress/element';
import UseWebSiteScanData from './UseWebSiteScanData';
import { __ } from '@wordpress/i18n';
import './WebSiteScanActions.scss';

const WebSiteScanActions = () => {
	const {
		loaded,
		wscStatus,
		tokenStatus,
		getStatus,
		startOnboarding,
		resetWsc,
		enableWsc,
		disableWsc,
	} = UseWebSiteScanData();

	// get the status before the render
	useEffect( () => {
		getStatus();
	}, [] );

	const handleActivationReset = useCallback( () => {
		if ( ! tokenStatus ) {
			return;
		}

		switch ( tokenStatus ) {
			case 'enabled':
				resetWsc();
				break;
			case 'disabled':
				startOnboarding();
				break;
			case 'pending':
				startOnboarding();
				break;
			// A token 'error' (auth/transient failure) must stay recoverable:
			// re-enter onboarding instead of leaving the button a no-op. Not the
			// destructive resetWsc() — that deregisters the account server-side.
			case 'error':
				startOnboarding();
				break;
			default:
				break;
		}
	}, [ tokenStatus ] );

	const handleEnableDisable = useCallback( () => {
		if ( ! wscStatus ) {
			return;
		}

		switch ( wscStatus ) {
			case 'enabled':
				disableWsc();
				break;
			case 'disabled':
				enableWsc();
				break;
			default:
				break;
		}
	}, [ wscStatus ] );

	// handle the first button - Enable/Disable Website Scan
	const enableDisableButtonText =
		wscStatus === 'enabled'
			? __( 'Disable Website Scan', 'complianz-gdpr' )
			: __( 'Enable Website Scan', 'complianz-gdpr' );
	const enableDisableButtonClass =
		wscStatus === 'enabled' ? 'button-secondary' : 'button-primary';
	// handle the second button - Activate (send Token) / Reset
	const activateResetButtonText =
		tokenStatus === 'enabled'
			? __( 'Reset Website Scan', 'complianz-gdpr' )
			: __( 'Activate Website Scan', 'complianz-gdpr' );
	const activateResetButtonClass =
		tokenStatus === 'enabled' ? 'button-danger' : 'button-primary';
	const activateResetButtonStatus = tokenStatus === 'pending';

	return (
		<div className="cmplz-wsc_actions-container">
			<div className="cmplz-wsc_actions-row">
				<div className="cmplz-wsc_actions-buttons">
					{ tokenStatus !== 'disabled' && wscStatus !== 'pending' && (
						<button
							className={ `button ${ enableDisableButtonClass }` }
							onClick={ handleEnableDisable }
						>
							{ enableDisableButtonText }
						</button>
					) }
					<button
						disabled={ activateResetButtonStatus }
						className={ `button ${ activateResetButtonClass }` }
						onClick={ handleActivationReset }
					>
						{ activateResetButtonText }
					</button>
				</div>
			</div>
		</div>
	);
};

export default memo( WebSiteScanActions );
