"use strict";(globalThis.webpackChunkcomplianz_gdpr=globalThis.webpackChunkcomplianz_gdpr||[]).push([[5664,6231],{76231:(e,t,n)=>{n.r(t),n.d(t,{steps:()=>r,useNewOnboardingData:()=>c});var s=n(9588),o=n(81621),i=n(27723);const r={welcome:{title:(0,i.__)("Welcome to Complianz","complianz-gdpr"),prevButton:(0,i.__)("No, Thanks","complianz-gdpr"),nextButton:(0,i.__)("Continue","complianz-gdpr"),prevButtonGoTo:"newsletter",nextButtonGoTo:"terms"},terms:{title:(0,i.__)("Terms and Conditions","complianz-gdpr"),prevButton:(0,i.__)("Dismiss","complianz-gdpr"),nextButton:(0,i.__)("Continue","complianz-gdpr"),prevButtonGoTo:"newsletter",nextButtonGoTo:"newsletter"},newsletter:{title:(0,i.__)("Get tips and tricks","complianz-gdpr"),prevButton:(0,i.__)("Skip","complianz-gdpr"),nextButton:(0,i.__)("Continue","complianz-gdpr"),prevButtonGoTo:"plugins",nextButtonGoTo:"plugins"},plugins:{title:(0,i.__)("Install quickly for free","complianz-gdpr"),prevButton:(0,i.__)("Skip","complianz-gdpr"),nextButton:(0,i.__)("Continue","complianz-gdpr"),nextButtonSecondary:(0,i.__)("Install","complianz-gdpr"),nextButtonThird:(0,i.__)("Installing ...","complianz-gdpr"),prevButtonGoTo:"thankYou",nextButtonGoTo:"thankYou"},thankYou:{title:(0,i.__)("You’re almost there...","complianz-gdpr"),nextButton:(0,i.__)("Close","complianz-gdpr"),nextButtonGoTo:!1}},l=[{slug:"complianz-terms-conditions",description:(0,i.__)("Missing Terms & Conditions? Generate now","complianz-gdpr"),status:"not-installed",processing:!1},{slug:"really-simple-ssl",description:(0,i.__)("Really Simple Security? Let’s go","complianz-gdpr"),status:"not-installed",processing:!1}],a=async(e,t,n,o)=>{o((e=>({plugins:e.plugins.map((e=>e.slug===n.slug?{...e,status:"processing"}:e))})));const i={slug:n.slug,plugins:e};try{let e="";if("install_plugin"===t){const o=await s.doAction(t,i);if(!o.request_success)throw new Error("API Error: installing plugin.");const r=o.plugins.find((e=>e.slug===n.slug)).status||"not-installed";if("not-installed"===r)throw new Error("Error installing plugin.");e=r}const r=await s.doAction("activate_plugin",i);if(!r.request_success)throw new Error("API Error: installing plugin.");const l=r.plugins.find((e=>e.slug===n.slug)).status;if("activated"!==l)throw new Error("Error activating plugin.");e=l,o((t=>({plugins:t.plugins.map((t=>t.slug===n.slug?{...t,status:e}:t))})))}catch(e){o({isInstalling:!1}),console.error("Plugin installation error:",e)}},c=(0,o.vt)(((e,t)=>({isModalOpen:!0,isOnboardingComplete:!1,currentStep:"welcome",stepProcessing:!1,isLoading:!1,isContentLoading:!0,setIsContentLoading:t=>e({isContentLoading:t}),nextStepDisabled:!1,prevStepDisabled:!1,wscEmail:"",enableWsc:!1,emailError:"",termsAccepted:!1,wscTerms:"",newsletterAccepted:!1,newsletterEmail:"",newsletterTerms:"",fetchError:!1,fetchErrorMessage:"",fetchDoc:async()=>{e({isLoading:!0,fetchError:!1,fetchErrorMessage:""});const n=t().currentStep;let o="terms"===n?"get_wsc_terms":"get_newsletter_terms";const r=await s.doAction(o);r.request_success||e({fetchError:!0,fetchErrorMessage:(0,i.__)("Something went wrong while downloading the document.","complianz-gdpr")});const l=r.doc;l?"terms"===n?e({wscTerms:l,isLoading:!1}):"newsletter"===n&&e({newsletterTerms:l,isLoading:!1}):e({fetchError:!0,fetchErrorMessage:(0,i.__)("Something went wrong while downloading the document.","complianz-gdpr"),isLoading:!1})},suggestedPlugins:l,plugins:[],fetchPlugins:async()=>{try{const t=await s.doAction("get_recommended_plugins_status",{plugins:l});if(!t.request_success)throw new Error("Error fetching.");const n=t.plugins.map((e=>({...e,checked:"activated"===e.status||!1,toProcess:!1})));e({plugins:n})}catch(e){throw new Error("Api error:",e)}},enablePluginInstallation:!1,isInstalling:!1,setWscEmail:t=>e({wscEmail:t}),setEnableWsc:t=>e({enableWsc:t}),setEmailError:t=>e({emailError:t}),setNewsletterEmail:t=>e({newsletterEmail:t}),setNextStepDisabled:t=>e({nextStepDisabled:t}),setPrevStepDisabled:t=>e({prevStepDisabled:t}),setPlugins:t=>e({plugins:t}),setEnablePluginInstallation:t=>e({enablePluginInstallation:t}),closeModal:async n=>{n&&t().skipStep("onboarding");const s=new URL(window.location.href);s.searchParams.delete("websitescan"),window.history.pushState({},"",s.href),e({isModalOpen:!1})},skipStep:async e=>{if(!(await s.doAction("dismiss_wsc_onboarding",{step:e})).request_success)throw new Error("Error fetching.")},goToPrevStep:()=>e((e=>{const n="welcome"===e.currentStep?"newsletter":r[e.currentStep].prevButtonGoTo;if("newsletter"===n||"plugins"===n){const e="newsletter"===n?"websitescan":"newsletter";t().skipStep(e)}return{...e,currentStep:n,stepProcessing:!1}})),goToNextStep:()=>e((async n=>{let o=r[n.currentStep].nextButtonGoTo;e({stepProcessing:!0});try{switch(n.currentStep){case"welcome":0===n.wscEmail.length?(e({enableWsc:!1}),o="newsletter"):e({enableWsc:!0});break;case"terms":let i=t().wscEmail;if(e({termsAccepted:!0,newsletterEmail:i,isLoading:!0}),!(await s.doAction("signup_wsc",{email:i,timestamp:(new Date).getTime(),url:window.location.href})).request_success)throw new Error("Error fetching.");e({isLoading:!1});break;case"newsletter":let r=t().newsletterEmail;if(e({newsletterAccepted:!0,isLoading:!0}),!(await s.doAction("signup_newsletter",{email:r,timestamp:(new Date).getTime(),url:window.location.href})).request_success)throw new Error("Error fetching.");e({isLoading:!1});break;case"plugins":if(!t().enablePluginInstallation)break;const l=t().plugins.filter((e=>e.toProcess));if(l.length<=0)break;const c=t().suggestedPlugins;e({isInstalling:!0});for(const t of l)"not-installed"===t.status?await a(c,"install_plugin",t,e):"installed"===t.status&&await a(c,"activate_plugin",t,e);return void e({isInstalling:!1,stepProcessing:!1,enablePluginInstallation:!1});case"thankYou":e({isOnboardingComplete:!0}),t().closeModal()}e({currentStep:o,stepProcessing:!1})}catch(t){console.error("Error during step transition:",t),e({stepProcessing:!1})}})),isValidEmail:e=>0===e.length||/^[\w.+_-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(e)})))},95664:(e,t,n)=>{n.r(t),n.d(t,{default:()=>l});var s=n(51609),o=(n(86087),n(45111)),i=n(76231);const r={"not-installed":{label:"Check to Install!",iconColor:"blue",iconName:"info",checked:!1},installed:{label:"Check to Activate!",iconColor:"orange",iconName:"info",checked:!1},activated:{label:"Installed!",iconColor:"green",iconName:"circle-check",checked:!0},processing:{label:"Processing ...",iconColor:"grey",iconName:"loading",checked:!0}},l=({plugin:e,className:t="",handleChange:n,...l})=>{const{isInstalling:a}=(0,i.useNewOnboardingData)();return(0,s.createElement)("div",{className:`cmplz-websitescan-input-wrapper plugin-checkbox ${e.slug}`},(0,s.createElement)("label",null,(0,s.createElement)("input",{className:`${t} cmplz-websitescan-input`,checked:e.checked,disabled:!!a||r[e.status].checked,onChange:t=>{n(e,t.target.checked)},type:"checkbox",...l}),(0,s.createElement)("span",{className:"checkmark"}),(0,s.createElement)("span",{className:"description"},e.description)),e.status&&(0,s.createElement)(o.default,{name:r[e.status]?.iconName,color:r[e.status]?.iconColor,size:14,tooltip:r[e.status]?.label}))}}}]);