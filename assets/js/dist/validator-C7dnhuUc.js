import{_ as u}from"./lit-element-DOwO35PZ.js";import{c as s}from"./element-internals-Cm1FnwW7.js";import{n as p}from"./property-D4JB6CpA.js";/**
 * @license
 * Copyright 2021 Google LLC
 * SPDX-License-Identifier: Apache-2.0
 */function S(r,e){e.bubbles&&(!r.shadowRoot||e.composed)&&e.stopPropagation();const n=Reflect.construct(e.constructor,[e.type,e]),t=r.dispatchEvent(n);return t||e.preventDefault(),t}/**
 * @license
 * Copyright 2023 Google LLC
 * SPDX-License-Identifier: Apache-2.0
 */const m=Symbol("createValidator"),g=Symbol("getValidityAnchor"),c=Symbol("privateValidator"),a=Symbol("privateSyncValidity"),d=Symbol("privateCustomValidationMessage");function M(r){var e;class n extends r{constructor(){super(...arguments),this[e]=""}get validity(){return this[a](),this[s].validity}get validationMessage(){return this[a](),this[s].validationMessage}get willValidate(){return this[a](),this[s].willValidate}checkValidity(){return this[a](),this[s].checkValidity()}reportValidity(){return this[a](),this[s].reportValidity()}setCustomValidity(i){this[d]=i,this[a]()}requestUpdate(i,o,l){super.requestUpdate(i,o,l),this[a]()}firstUpdated(i){super.firstUpdated(i),this[a]()}[(e=d,a)](){this[c]||(this[c]=this[m]());const{validity:i,validationMessage:o}=this[c].getValidity(),l=!!this[d],V=this[d]||o;this[s].setValidity({...i,customError:l},V,this[g]()??void 0)}[m](){throw new Error("Implement [createValidator]")}[g](){throw new Error("Implement [getValidityAnchor]")}}return n}/**
 * @license
 * Copyright 2023 Google LLC
 * SPDX-License-Identifier: Apache-2.0
 */const h=Symbol("getFormValue"),y=Symbol("getFormState");function C(r){class e extends r{get form(){return this[s].form}get labels(){return this[s].labels}get name(){return this.getAttribute("name")??""}set name(t){this.setAttribute("name",t)}get disabled(){return this.hasAttribute("disabled")}set disabled(t){this.toggleAttribute("disabled",t)}attributeChangedCallback(t,i,o){if(t==="name"||t==="disabled"){const l=t==="disabled"?i!==null:i;this.requestUpdate(t,l);return}super.attributeChangedCallback(t,i,o)}requestUpdate(t,i,o){super.requestUpdate(t,i,o),this[s].setFormValue(this[h](),this[y]())}[h](){throw new Error("Implement [getFormValue]")}[y](){return this[h]()}formDisabledCallback(t){this.disabled=t}}return e.formAssociated=!0,u([p({noAccessor:!0})],e.prototype,"name",null),u([p({type:Boolean,noAccessor:!0})],e.prototype,"disabled",null),e}/**
 * @license
 * Copyright 2023 Google LLC
 * SPDX-License-Identifier: Apache-2.0
 */class w{constructor(e){this.getCurrentState=e,this.currentValidity={validity:{},validationMessage:""}}getValidity(){const e=this.getCurrentState();if(!(!this.prevState||!this.equals(this.prevState,e)))return this.currentValidity;const{validity:t,validationMessage:i}=this.computeValidity(e);return this.prevState=this.copy(e),this.currentValidity={validationMessage:i,validity:{badInput:t.badInput,customError:t.customError,patternMismatch:t.patternMismatch,rangeOverflow:t.rangeOverflow,rangeUnderflow:t.rangeUnderflow,stepMismatch:t.stepMismatch,tooLong:t.tooLong,tooShort:t.tooShort,typeMismatch:t.typeMismatch,valueMissing:t.valueMissing}},this.currentValidity}}export{w as V,C as a,g as b,m as c,y as d,h as g,M as m,S as r};
