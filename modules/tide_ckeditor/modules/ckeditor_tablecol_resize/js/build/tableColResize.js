!function(e,t){"object"==typeof exports&&"object"==typeof module?module.exports=t():"function"==typeof define&&define.amd?define([],t):"object"==typeof exports?exports.CKEditor5=t():(e.CKEditor5=e.CKEditor5||{},e.CKEditor5.tableColResize=t())}(self,(()=>(()=>{var e={"ckeditor5/src/core.js":(e,t,r)=>{e.exports=r("dll-reference CKEditor5.dll")("./src/core.js")},"dll-reference CKEditor5.dll":e=>{"use strict";e.exports=CKEditor5.dll}},t={};function r(o){var i=t[o];if(void 0!==i)return i.exports;var s=t[o]={exports:{}};return e[o](s,s.exports,r),s.exports}r.d=(e,t)=>{for(var o in t)r.o(t,o)&&!r.o(e,o)&&Object.defineProperty(e,o,{enumerable:!0,get:t[o]})},r.o=(e,t)=>Object.prototype.hasOwnProperty.call(e,t);var o={};return(()=>{"use strict";r.d(o,{default:()=>s});var e=r("ckeditor5/src/core.js");class t extends e.Plugin{static get pluginName(){return"TableColResizeEditing"}afterInit(){this.editor.plugins.has("TableColumnResizeEditing")&&this._registerConverters()}_registerConverters(){const e=this.editor,t=e.config._config.tableColResize.dataAttribute;e.conversion.for("downcast").add((e=>e.on("attribute:columnWidth:tableColumn",((e,r,o)=>{const i=o.writer,s=o.mapper.toViewElement(r.item);null!==r.attributeNewValue?i.setAttribute(t,r.attributeNewValue,s):i.removeAttribute(t,s)})))),e.conversion.for("upcast").add((e=>e.on("element:col",((e,r,o)=>{const{schema:i,writer:s}=o,n=r.viewItem.getAttribute(t);console.log("col upcast",n);for(const e of r.modelRange.getItems({shallow:!0}))i.checkAttribute(e,"columnWidth")&&s.setAttribute("columnWidth",n,e)})))),e.conversion.for("downcast").add((e=>e.on("attribute:tableWidth:table",((e,r,o)=>{const i=o.writer,s=o.mapper.toViewElement(r.item);null!==r.attributeNewValue?i.setAttribute(t,r.attributeNewValue,s):i.removeAttribute(t,s)})))),e.conversion.for("upcast").add((e=>e.on("element:table",((e,r,o)=>{const{schema:i,writer:s}=o,n=r.viewItem.getAttribute(t);for(const e of r.modelRange.getItems({shallow:!0}))i.checkAttribute(e,"tableWidth")&&s.setAttribute("tableWidth",n,e)}))))}}class i extends e.Plugin{static get requires(){return[t]}static get pluginName(){return"TableColResize"}}const s={TableColResize:i}})(),o=o.default})()));