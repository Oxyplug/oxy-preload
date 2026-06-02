import"./lit-element-DOwO35PZ.js";import{c as o}from"./element-internals-Cm1FnwW7.js";/**
 * @license
 * Copyright 2023 Google LLC
 * SPDX-License-Identifier: Apache-2.0
 */function l(u){u.addInitializer(s=>{const e=s;e.addEventListener("click",async a=>{const{type:n,[o]:i}=e,{form:t}=i;if(!(!t||n==="button")&&(await new Promise(r=>{setTimeout(r)}),!a.defaultPrevented)){if(n==="reset"){t.reset();return}t.addEventListener("submit",r=>{Object.defineProperty(r,"submitter",{configurable:!0,enumerable:!0,get:()=>e})},{capture:!0,once:!0}),i.setFormValue(e.value),t.requestSubmit()}})})}export{l as s};
