"use strict";(globalThis.webpackChunkcomplianz_gdpr=globalThis.webpackChunkcomplianz_gdpr||[]).push([[9837,382],{20382:(e,t,n)=>{n.r(t),n.d(t,{default:()=>i});var a=n(30270),o=n(48399);const i=(0,a.Ue)(((e,t)=>({documents:[],documentDataLoaded:!1,processingAgreementOptions:[],proofOfConsentOptions:[],dataBreachOptions:[],region:"",setRegion:t=>{"undefined"!=typeof Storage&&(sessionStorage.cmplzSelectedRegion=t),e((e=>({region:t})))},getRegion:()=>{let t="all";"undefined"!=typeof Storage&&sessionStorage.cmplzSelectedRegion&&(t=sessionStorage.cmplzSelectedRegion),e((e=>({region:t})))},getDocuments:async()=>{const{documents:t,processingAgreementOptions:n,proofOfConsentOptions:a,dataBreachOptions:i}=await o.doAction("documents_block_data").then((e=>e));e((e=>({documentDataLoaded:!0,documents:t,processingAgreementOptions:n,proofOfConsentOptions:a,dataBreachOptions:i})))}})))},19837:(e,t,n)=>{n.r(t),n.d(t,{default:()=>s});var a=n(69307),o=n(65736),i=(n(43684),n(20382));const r=e=>{let{document:t}=e;const{region:n}=(0,i.default)();let o=t.regions.filter((e=>e!==n));return(0,a.createElement)(a.Fragment,null,(0,a.createElement)("div",{className:"cmplz-single-document-other-regions"},(0,a.createElement)("a",{href:t.readmore,target:"_blank"},t.title),o.map(((e,t)=>(0,a.createElement)(a.Fragment,null,(0,a.createElement)("div",{key:t,className:"cmplz-region-indicator"},(0,a.createElement)("img",{alt:e,width:"16px",height:"16px",src:cmplz_settings.plugin_url+"/assets/images/"+e+".svg"})))))))},s=e=>(0,a.createElement)(a.Fragment,null,(0,a.createElement)("div",{className:"cmplz-document-header"},(0,a.createElement)("h3",{className:"cmplz-h4"},(0,o.__)("Other regions")),(0,a.createElement)("a",{href:"https://complianz.io/features/",target:"_blank"},(0,o.__)("Read more","complianz-gdpr"))),[{id:"privacy-statement",title:"Privacy Statements",regions:["eu","us","uk","ca","za","au","br"],readmore:"https://complianz.io/definition/what-is-a-privacy-statement/"},{id:"cookie-statement",title:"Cookie Policy",regions:["eu","us","uk","ca","za","au","br"],readmore:" https://complianz.io/definition/what-is-a-cookie-policy/"},{id:"impressum",title:"Impressum",regions:["eu"],readmore:"https://complianz.io/definition/what-is-an-impressum/"},{id:"do-not-sell-my-info",title:"Opt-out preferences",regions:["us"],readmore:"https://complianz.io/definition/what-is-do-not-sell-my-personal-information/"},{id:"privacy-statement-for-children",title:"Privacy Statement for Children",regions:["us","uk","ca","za","au","br"],readmore:"https://complianz.io/definition/what-is-a-privacy-statement-for-children/"}].map(((e,t)=>(0,a.createElement)(r,{key:t,document:e}))))}}]);