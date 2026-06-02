import{_ as t,r,i as n,t as d}from"./lit-element-DOwO35PZ.js";import{n as s}from"./property-D4JB6CpA.js";/**
 * @license
 * Copyright 2023 Google LLC
 * SPDX-License-Identifier: Apache-2.0
 */class e extends r{constructor(){super(...arguments),this.inset=!1,this.insetStart=!1,this.insetEnd=!1}}t([s({type:Boolean,reflect:!0})],e.prototype,"inset",void 0);t([s({type:Boolean,reflect:!0,attribute:"inset-start"})],e.prototype,"insetStart",void 0);t([s({type:Boolean,reflect:!0,attribute:"inset-end"})],e.prototype,"insetEnd",void 0);/**
 * @license
 * Copyright 2024 Google LLC
 * SPDX-License-Identifier: Apache-2.0
 */const a=n`:host{box-sizing:border-box;color:var(--md-divider-color, var(--md-sys-color-outline-variant, #cac4d0));display:flex;height:var(--md-divider-thickness, 1px);width:100%}:host([inset]),:host([inset-start]){padding-inline-start:16px}:host([inset]),:host([inset-end]){padding-inline-end:16px}:host::before{background:currentColor;content:"";height:100%;width:100%}@media(forced-colors: active){:host::before{background:CanvasText}}
`;/**
 * @license
 * Copyright 2023 Google LLC
 * SPDX-License-Identifier: Apache-2.0
 */let i=class extends e{};i.styles=[a];i=t([d("md-divider")],i);
