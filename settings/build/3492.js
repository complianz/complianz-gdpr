"use strict";(globalThis.webpackChunkcomplianz_gdpr=globalThis.webpackChunkcomplianz_gdpr||[]).push([[3492],{13492:(e,t,s)=>{s.r(t),s.d(t,{default:()=>n});var l=s(69307);class a extends l.Component{constructor(){super(...arguments)}onChangeHandler(e){let t=this.props.fields,s=this.props.field;t[this.props.index].value=e,setChangedField(s.id,e),this.setState({fields:t})}render(){let e=this.props.field,t=e.value;return this.props.fields,(0,l.createElement)("div",{className:"components-base-control"},(0,l.createElement)("div",{className:"components-base-control__field"},(0,l.createElement)("label",{className:"components-base-control__label",htmlFor:e.id},e.label),(0,l.createElement)("input",{className:"components-text-control__input",type:"password",id:e.id,value:t,onChange:e=>this.onChangeHandler(e.target.value)})))}}const n=a}}]);