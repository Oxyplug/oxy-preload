import{n as p}from"./property-D4JB6CpA.js";import{T as E}from"./lit-element-DOwO35PZ.js";/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */function m(t){return p({...t,state:!0,attribute:!1})}/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const f=(t,e,i)=>(i.configurable=!0,i.enumerable=!0,Reflect.decorate&&typeof e!="object"&&Object.defineProperty(t,e,i),i);/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */function D(t,e){return(i,n,a)=>{const r=s=>{var o;return((o=s.renderRoot)==null?void 0:o.querySelector(t))??null};return f(i,n,{get(){return r(this)}})}}/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const T={ATTRIBUTE:1,CHILD:2,PROPERTY:3,BOOLEAN_ATTRIBUTE:4,EVENT:5,ELEMENT:6},S=t=>(...e)=>({_$litDirective$:t,values:e});class C{constructor(e){}get _$AU(){return this._$AM._$AU}_$AT(e,i,n){this._$Ct=e,this._$AM=i,this._$Ci=n}_$AS(e,i){return this.update(e,i)}update(e,i){return this.render(...i)}}/**
 * @license
 * Copyright 2018 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const v=S(class extends C{constructor(t){var e;if(super(t),t.type!==T.ATTRIBUTE||t.name!=="class"||((e=t.strings)==null?void 0:e.length)>2)throw Error("`classMap()` can only be used in the `class` attribute and must be the only part in the attribute.")}render(t){return" "+Object.keys(t).filter(e=>t[e]).join(" ")+" "}update(t,[e]){var n,a;if(this.st===void 0){this.st=new Set,t.strings!==void 0&&(this.nt=new Set(t.strings.join(" ").split(/\s/).filter(r=>r!=="")));for(const r in e)e[r]&&!((n=this.nt)!=null&&n.has(r))&&this.st.add(r);return this.render(e)}const i=t.element.classList;for(const r of this.st)r in e||(i.remove(r),this.st.delete(r));for(const r in e){const s=!!e[r];s===this.st.has(r)||(a=this.nt)!=null&&a.has(r)||(s?(i.add(r),this.st.add(r)):(i.remove(r),this.st.delete(r)))}return E}});/**
 * @license
 * Copyright 2021 Google LLC
 * SPDX-License-Identifier: Apache-2.0
 */const _={STANDARD:"cubic-bezier(0.2, 0, 0, 1)",STANDARD_ACCELERATE:"cubic-bezier(.3,0,1,1)",STANDARD_DECELERATE:"cubic-bezier(0,0,0,1)",EMPHASIZED:"cubic-bezier(.3,0,0,1)",EMPHASIZED_ACCELERATE:"cubic-bezier(.3,0,.8,.15)",EMPHASIZED_DECELERATE:"cubic-bezier(.05,.7,.1,1)"};/**
 * @license
 * Copyright 2023 Google LLC
 * SPDX-License-Identifier: Apache-2.0
 */const b=["role","ariaAtomic","ariaAutoComplete","ariaBusy","ariaChecked","ariaColCount","ariaColIndex","ariaColSpan","ariaCurrent","ariaDisabled","ariaExpanded","ariaHasPopup","ariaHidden","ariaInvalid","ariaKeyShortcuts","ariaLabel","ariaLevel","ariaLive","ariaModal","ariaMultiLine","ariaMultiSelectable","ariaOrientation","ariaPlaceholder","ariaPosInSet","ariaPressed","ariaReadOnly","ariaRequired","ariaRoleDescription","ariaRowCount","ariaRowIndex","ariaRowSpan","ariaSelected","ariaSetSize","ariaSort","ariaValueMax","ariaValueMin","ariaValueNow","ariaValueText"],R=b.map(A);function l(t){return R.includes(t)}function A(t){return t.replace("aria","aria-").replace(/Elements?/g,"").toLowerCase()}/**
 * @license
 * Copyright 2023 Google LLC
 * SPDX-License-Identifier: Apache-2.0
 */const u=Symbol("privateIgnoreAttributeChangesFor");function L(t){var e;class i extends t{constructor(){super(...arguments),this[e]=new Set}attributeChangedCallback(a,r,s){if(!l(a)){super.attributeChangedCallback(a,r,s);return}if(this[u].has(a))return;this[u].add(a),this.removeAttribute(a),this[u].delete(a);const o=h(a);s===null?delete this.dataset[o]:this.dataset[o]=s,this.requestUpdate(h(a),r)}getAttribute(a){return l(a)?super.getAttribute(d(a)):super.getAttribute(a)}removeAttribute(a){super.removeAttribute(a),l(a)&&(super.removeAttribute(d(a)),this.requestUpdate())}}return e=u,g(i),i}function g(t){for(const e of b){const i=A(e),n=d(i),a=h(i);t.createProperty(e,{attribute:i,noAccessor:!0}),t.createProperty(Symbol(n),{attribute:n,noAccessor:!0}),Object.defineProperty(t.prototype,e,{configurable:!0,enumerable:!0,get(){return this.dataset[a]??null},set(r){const s=this.dataset[a]??null;r!==s&&(r===null?delete this.dataset[a]:this.dataset[a]=r,this.requestUpdate(e,s))}})}}function d(t){return`data-${t}`}function h(t){return t.replace(/-\w/,e=>e[1].toUpperCase())}/**
 * @license
 * Copyright 2023 Google LLC
 * SPDX-License-Identifier: Apache-2.0
 */const y=Symbol("internals"),c=Symbol("privateInternals");function M(t){class e extends t{get[y](){return this[c]||(this[c]=this.attachInternals()),this[c]}}return e}export{_ as E,v as a,S as b,y as c,M as d,D as e,f,C as i,L as m,m as r,T as t};
