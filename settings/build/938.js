"use strict";(globalThis.webpackChunkcomplianz_gdpr=globalThis.webpackChunkcomplianz_gdpr||[]).push([[938,3252],{50938:(a,e,t)=>{t.r(e),t.d(e,{default:()=>n});var o=t(69307),r=t(65736),l=t(56293),s=t(23252);const n=()=>{const{consentType:a,setConsentType:e,consentTypes:t,fetchStatisticsData:n,loaded:i}=(0,s.default)(),{fields:c,getFieldValue:d}=(0,l.default)(),[b,g]=(0,o.useState)(!1);return(0,o.useEffect)((()=>{let a=1==d("a_b_testing");g(a)}),[d("a_b_testing")]),(0,o.useEffect)((()=>{b&&!i&&n()}),[b]),(0,o.createElement)(o.Fragment,null,(0,o.createElement)("h3",{className:"cmplz-grid-title cmplz-h4"},b&&(0,r.__)("Statistics","complianz-gdpr"),!b&&(0,r.__)("Tools","complianz-gdpr")),(0,o.createElement)("div",{className:"cmplz-grid-item-controls"},b&&t.length>1&&(0,o.createElement)("select",{onChange:a=>e(a.target.value),value:a},t.map(((a,e)=>(0,o.createElement)("option",{key:e,value:a.id},a.label))))))}},23252:(a,e,t)=>{t.r(e),t.d(e,{default:()=>n});var o=t(30270),r=t(48399);const l={optin:{labels:["Functional","Statistics","Marketing","Do Not Track","No choice","No warning"],categories:["functional","statistics","marketing","do_not_track","no_choice","no_warning"],datasets:[{data:["0","0","0","0","0","0"],backgroundColor:"rgba(255, 99, 132, 1)",borderColor:"rgba(255, 99, 132, 1)",label:"A (default)",fill:"false",borderDash:[0,0]},{data:["0","0","0","0","0","0"],backgroundColor:"rgba(255, 159, 64, 1)",borderColor:"rgba(255, 159, 64, 1)",label:"B",fill:"false",borderDash:[0,0]}],max:5},optout:{labels:["Functional","Statistics","Marketing","Do Not Track","No choice","No warning"],categories:["functional","statistics","marketing","do_not_track","no_choice","no_warning"],datasets:[{data:["0","0","0","0","0","0"],backgroundColor:"rgba(255, 99, 132, 1)",borderColor:"rgba(255, 99, 132, 1)",label:"A (default)",fill:"false",borderDash:[0,0]},{data:["0","0","0","0","0","0"],backgroundColor:"rgba(255, 159, 64, 1)",borderColor:"rgba(255, 159, 64, 1)",label:"B",fill:"false",borderDash:[0,0]}],max:5}},s={optin:{labels:["Functional","Statistics","Marketing","Do Not Track","No choice","No warning"],categories:["functional","statistics","marketing","do_not_track","no_choice","no_warning"],datasets:[{data:["29","747","174","292","30","10"],backgroundColor:"rgba(255, 99, 132, 1)",borderColor:"rgba(255, 99, 132, 1)",label:"Demo A (default)",fill:"false",borderDash:[0,0]},{data:["3","536","240","389","45","32"],backgroundColor:"rgba(255, 159, 64, 1)",borderColor:"rgba(255, 159, 64, 1)",label:"Demo B",fill:"false",borderDash:[0,0]}],max:5},optout:{labels:["Functional","Statistics","Marketing","Do Not Track","No choice","No warning"],categories:["functional","statistics","marketing","do_not_track","no_choice","no_warning"],datasets:[{data:["29","747","174","292","30","10"],backgroundColor:"rgba(255, 99, 132, 1)",borderColor:"rgba(255, 99, 132, 1)",label:"A (default)",fill:"false",borderDash:[0,0]},{data:["3","536","240","389","45","32"],backgroundColor:"rgba(255, 159, 64, 1)",borderColor:"rgba(255, 159, 64, 1)",label:"Demo B",fill:"false",borderDash:[0,0]}],max:5}},n=(0,o.Ue)(((a,e)=>({consentType:"optin",setConsentType:e=>{a({consentType:e})},statisticsLoading:!1,consentTypes:[],regions:[],defaultConsentType:"optin",loaded:!1,statisticsData:s,emptyStatisticsData:l,bestPerformerEnabled:!1,daysLeft:"",abTrackingCompleted:!1,labels:[],setLabels:e=>{a({labels:e})},fetchStatisticsData:async()=>{if(a({saving:!0}),e().loaded)return;const{daysLeft:t,abTrackingCompleted:o,consentTypes:l,statisticsData:s,defaultConsentType:n,regions:i,bestPerformerEnabled:c}=await r.doAction("get_statistics_data",{}).then((a=>a)).catch((a=>{console.error(a)}));a({saving:!1,loaded:!0,consentType:n,consentTypes:l,statisticsData:s,defaultConsentType:n,bestPerformerEnabled:c,regions:i,daysLeft:t,abTrackingCompleted:o})}})))}}]);