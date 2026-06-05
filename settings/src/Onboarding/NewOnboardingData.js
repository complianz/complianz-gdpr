import * as cmplz_api from "../utils/api";
import { create } from 'zustand';
import { __ } from '@wordpress/i18n';

const steps = {
	welcome: { // thanks for updating
		title: __("Welcome to Complianz", "complianz-gdpr"),
		prevButton: __("No, Thanks", "complianz-gdpr"),
		nextButton: __("Continue", "complianz-gdpr"),
		prevButtonGoTo: 'newsletter', // on No, thanks, go to newsletter step
		nextButtonGoTo: 'terms' // on Continue, go to terms step
	},
	terms: { // enable consent api
		title: __("Terms and Conditions", "complianz-gdpr"),
		prevButton: __("Dismiss", "complianz-gdpr"),
		nextButton: __("Continue", "complianz-gdpr"), // enable consent api
		prevButtonGoTo: 'newsletter', // on Skip, go to newsletter step
		nextButtonGoTo: 'newsletter' // on Continue, enable consent api, send activation email, go to newsletter step
	},
	newsletter: { // newsletter sign up
		title: __("Get tips and tricks", "complianz-gdpr"),
		prevButton: __("Skip", "complianz-gdpr"),
		nextButton: __("Continue", "complianz-gdpr"),
		prevButtonGoTo: 'plugins', // on Skip, go to plugins step
		nextButtonGoTo: 'plugins' // on Continue, newsletter sign up, go to plugins step
	},
	plugins: { // list plugins with checkbox
		title: __("Install quickly for free", "complianz-gdpr"),
		prevButton: __("Skip", "complianz-gdpr"),
		nextButton: __("Continue", "complianz-gdpr"), // wait the plugin installation, then go to thankyou step
		nextButtonSecondary: __("Install", "complianz-gdpr"), // wait the plugin installation, then go to thankyou step
		nextButtonThird: __("Installing ...", "complianz-gdpr"), // wait the plugin installation, then go to thankyou step
		prevButtonGoTo: 'thankYou', // on Skip, go to thankyou step
		nextButtonGoTo: 'thankYou' // on Install, wait the installation, then change button to thankYou step
	},
	thankYou: {
		title: __("You’re almost there...", "complianz-gdpr"),
		nextButton: __("Close", "complianz-gdpr"),
		nextButtonGoTo: false // on Install, wait the installation, then change button to thankYou step
		//  clsoe the onboarding
	}
}

const stepDismissOnPrev = {
	welcome:    'websitescan',
	terms:      'websitescan',
	newsletter: 'newsletter',
	plugins:    null,
	thankYou:   null,
};

const suggestedPlugins = [
	{
		'slug': 'complianz-terms-conditions',
		'description': __("Missing Terms & Conditions? Generate now", "complianz-gdpr"),
		'status': 'not-installed',
		'processing': false,
	},
	{
		'slug': 'really-simple-ssl',
		'description': __("Really Simple Security? Let’s go", "complianz-gdpr"),
		'status': 'not-installed',
		'processing': false,
	},
];

const handlePluginInstallation = async (defaultPlugins, action, plugin, set) => {
	set((state) => ({
		plugins: state.plugins.map((p) =>
			p.slug === plugin.slug ? { ...p, status: 'processing' } : p
		)
	}));

	// action = install_plugin // activate_plugin
	const data = { slug: plugin.slug, plugins: defaultPlugins };

	try {
		let pluginStatus = '';

		if (action === 'install_plugin') {
			const installResponse = await cmplz_api.doAction(action, data);
			if (!installResponse.request_success) {
				throw new Error(`API Error: installing plugin.`);
			}

			const pluginInstallationStatus = installResponse.plugins.find(p => p.slug === plugin.slug).status || 'not-installed';
			if (pluginInstallationStatus === 'not-installed') {
				throw new Error(`Error installing plugin.`);
			}
			pluginStatus = pluginInstallationStatus;
		}

		const response = await cmplz_api.doAction('activate_plugin', data);
		if (!response.request_success) {
			throw new Error(`API Error: installing plugin.`);
		}
		const newPluginStatus = response.plugins.find(p => p.slug === plugin.slug).status;

		if (newPluginStatus !== 'activated') {
			throw new Error(`Error activating plugin.`);
		}

		pluginStatus = newPluginStatus;

		set((state) => ({
			plugins: state.plugins.map((p) =>
				p.slug === plugin.slug ? { ...p, status: pluginStatus } : p
			)
		}));

	} catch (error) {
		set({ isInstalling: false });
		console.error('Plugin installation error:', error);
	}
};

// define zustand Store
const useNewOnboardingData = create((set, get) => ({
	isModalOpen: true,
	isOnboardingComplete: false,
	currentStep: 'welcome',
	stepProcessing: false,
	isLoading: false,
	isContentLoading: true,
	setIsContentLoading: (isContentLoading) => set({ isContentLoading }),
	//
	// Buttons status
	nextStepDisabled: false,
	prevStepDisabled: false,
	//
	// welcome step
	wscEmail: '',
	enableWsc: false,
	emailError: '',
	//
	// terms step
	termsAccepted: false,
	wscTerms: '',
	// newsletter
	newsletterAccepted: false,
	newsletterEmail: '',
	newsletterTerms: '',
	// fetch docs
	fetchError: false,
	fetchErrorMessage: '',
	fetchDoc: async () => {
		set({ isLoading: true, fetchError: false, fetchErrorMessage: '' })
		const currentStep = get().currentStep;
		let type = currentStep === 'terms' ? 'get_wsc_terms' : 'get_newsletter_terms';
		const response = await cmplz_api.doAction(type);

		if (!response.request_success) {
			set({ fetchError: true, fetchErrorMessage: __('Something went wrong while downloading the document.', 'complianz-gdpr') })
		}

		const doc = response.doc;

		if (!doc) {
			set({ fetchError: true, fetchErrorMessage: __('Something went wrong while downloading the document.', 'complianz-gdpr'), isLoading: false })
		} else {
			if (currentStep === 'terms') {
				set({ wscTerms: doc, isLoading: false });
			} else if (currentStep === 'newsletter') {
				set({ newsletterTerms: doc, isLoading: false });
			}
		}
	},
	//
	// plugins
	suggestedPlugins,
	plugins: [], // state updated after userEffect, then every checkboxes change
	fetchPlugins: async () => {
		try {
			const response = await cmplz_api.doAction('get_recommended_plugins_status', { plugins: suggestedPlugins });
			if (!response.request_success) {
				throw new Error(`Error fetching.`)
			}
			const data = response.plugins;
			const filteredPlugins = data.map((p) => ({
				...p,
				checked: p.status === 'activated' || false,
				toProcess: false // default false, then update the value with handleChange
			}));
			set({ plugins: filteredPlugins });

		} catch (error) {
			throw new Error('Api error:', error);
		}
	},
	enablePluginInstallation: false,
	isInstalling: false,
	//
	// setter
	setWscEmail: (wscEmail) => set({ wscEmail }),
	setEnableWsc: (enableWsc) => set({ enableWsc }),
	setEmailError: (emailError) => set({ emailError }),
	setNewsletterEmail: (newsletterEmail) => set({ newsletterEmail }),
	setNextStepDisabled: (nextStepDisabled) => set({ nextStepDisabled }),
	setPrevStepDisabled: (prevStepDisabled) => set({ prevStepDisabled }),
	setPlugins: (plugins) => set({ plugins }), // here check plugin statuses // already exists ?!
	setEnablePluginInstallation: (enablePluginInstallation) => set(({ enablePluginInstallation })), // skip installation if there are no plugin to install/activate
	//
	// close modal
	closeModal: async (dismissed) => {
		if (dismissed) {
			get().skipStep('onboarding');
		}

		const url = new URL(window.location.href);
		url.searchParams.delete('websitescan');
		window.history.pushState({}, '', url.href);
		set({ isModalOpen: false })
	},
	skipStep: async (step) => {
		const response = await cmplz_api.doAction('dismiss_wsc_onboarding', { step });
		if (!response.request_success) {
			throw new Error(`Error fetching.`)
		}
	},
	//
	// buttons actions
	goToPrevStep: () => set((state) => { // prev step or skip
		const newStep = steps[state.currentStep]?.prevButtonGoTo ?? null;
		if (!newStep) return state;

		const dismissStep = stepDismissOnPrev[state.currentStep];
		if (dismissStep) get().skipStep(dismissStep);

		return { ...state, currentStep: newStep, stepProcessing: false };
	}),
	goToNextStep: () => set(async (state) => {
		let newStep = steps[state.currentStep].nextButtonGoTo;

		set({ stepProcessing: true });
		try {
			switch (state.currentStep) {
				case 'welcome':
					if (state.wscEmail.length === 0) {
						set({ enableWsc: false });
						newStep = 'newsletter';
					} else {
						set({ enableWsc: true });
					}
					break;
				case 'terms':
					let email = get().wscEmail;
					set({ termsAccepted: true, newsletterEmail: email, isLoading: true });

					const responseTerms = await cmplz_api.doAction('signup_wsc', {
						email: email,
						timestamp: new Date().getTime(),
						url: window.location.href
					});
					if (!responseTerms.request_success) {
						throw new Error(`Error fetching.`)
					}
					set({ isLoading: false });
					break;
				case 'newsletter':
					let emailNewsletter = get().newsletterEmail;
					set({ newsletterAccepted: true, isLoading: true });

					const responseNewsletter = await cmplz_api.doAction('signup_newsletter', {
						email: emailNewsletter,
						timestamp: new Date().getTime(),
						url: window.location.href
					});
					if (!responseNewsletter.request_success) {
						throw new Error('Error fetching.')
					}
					set({ isLoading: false });

					break;
				case 'plugins':
					const isEnabled = get().enablePluginInstallation;
					if (!isEnabled) break;

					const newPlugins = get().plugins.filter((p) => p.toProcess);
					if (newPlugins.length <= 0) break;

					const defaultPlugins = get().suggestedPlugins;

					set({ isInstalling: true });
					for (const plugin of newPlugins) {
						if (plugin.status === 'not-installed') {
							// await installPlugin(defaultPlugins, plugin, set);
							await handlePluginInstallation(defaultPlugins, 'install_plugin', plugin, set);
						} else if (plugin.status === 'installed') {
							// await activatePlugin(defaultPlugins, plugin, set);
							await handlePluginInstallation(defaultPlugins, 'activate_plugin', plugin, set);
						}
					}
					set({ isInstalling: false, stepProcessing: false, enablePluginInstallation: false });

					return;
				// break;
				case 'thankYou':
					set({ isOnboardingComplete: true });
					get().closeModal();
					break;
				default:
					break;
			}
			set({ currentStep: newStep, stepProcessing: false, });
		} catch (error) {
			console.error('Error during step transition:', error);
			set({ stepProcessing: false });
		}
	}),
	//
	// Email validation
	isValidEmail: (email) => {
		// enable if the email is mandatory to proceed but if yes skip the terms and conditions step
		if (email.length === 0) return true;
		const regex = /^[\w.+_-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;

		return regex.test(email);
	},
	//
	// plugin installations
}));

export { useNewOnboardingData, steps };
