!function(e,t){"object"==typeof exports&&"object"==typeof module?module.exports=t():"function"==typeof define&&define.amd?define([],t):"object"==typeof exports?exports.CKEditor5=t():(e.CKEditor5=e.CKEditor5||{},e.CKEditor5.callout=t())}(self,(()=>(()=>{var e={"ckeditor5/src/core.js":(e,t,n)=>{e.exports=n("dll-reference CKEditor5.dll")("./src/core.js")},"ckeditor5/src/enter.js":(e,t,n)=>{e.exports=n("dll-reference CKEditor5.dll")("./src/enter.js")},"ckeditor5/src/typing.js":(e,t,n)=>{e.exports=n("dll-reference CKEditor5.dll")("./src/typing.js")},"ckeditor5/src/ui.js":(e,t,n)=>{e.exports=n("dll-reference CKEditor5.dll")("./src/ui.js")},"ckeditor5/src/utils.js":(e,t,n)=>{e.exports=n("dll-reference CKEditor5.dll")("./src/utils.js")},"dll-reference CKEditor5.dll":e=>{"use strict";e.exports=CKEditor5.dll}},t={};function n(r){var i=t[r];if(void 0!==i)return i.exports;var s=t[r]={exports:{}};return e[r](s,s.exports,n),s.exports}n.d=(e,t)=>{for(var r in t)n.o(t,r)&&!n.o(e,r)&&Object.defineProperty(e,r,{enumerable:!0,get:t[r]})},n.o=(e,t)=>Object.prototype.hasOwnProperty.call(e,t);var r={};return(()=>{"use strict";n.d(r,{default:()=>u});var e=n("ckeditor5/src/core.js"),t=n("ckeditor5/src/ui.js");class i extends e.Plugin{init(){const e=this.editor,n=e.t;e.ui.componentFactory.add("Callout",(r=>{const i=e.commands.get("CalloutCommand"),s=new t.ButtonView(r);return s.set({label:n("Callout"),icon:'<?xml version="1.0" encoding="UTF-8" standalone="no"?>\n\x3c!-- Created with Inkscape (http://www.inkscape.org/) --\x3e\n<svg\n    xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"\n    xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"\n    xmlns="http://www.w3.org/2000/svg"\n    xmlns:cc="http://creativecommons.org/ns#"\n    xmlns:dc="http://purl.org/dc/elements/1.1/"\n    xmlns:sodipodi="http://sodipodi.sourceforge.net/DTD/sodipodi-0.dtd"\n    id="svg548"\n    inkscape:version="0.48.3.1 r9886"\n    viewBox="0 0 503.77 479.07"\n    sodipodi:version="0.32"\n    version="1.1"\n    inkscape:output_extension="org.inkscape.output.svg.inkscape"\n    sodipodi:docname="_svgclean2.svg"\n  >\n  <defs\n      id="defs550"\n    >\n    <filter\n        id="filter4261"\n        inkscape:collect="always"\n        color-interpolation-filters="sRGB"\n      >\n      <feGaussianBlur\n          id="feGaussianBlur4263"\n          stdDeviation="6.8683288"\n          inkscape:collect="always"\n      />\n    </filter\n    >\n  </defs\n  >\n  <sodipodi:namedview\n      id="base"\n      inkscape:window-width="674"\n      inkscape:window-x="0"\n      inkscape:window-y="0"\n      inkscape:window-maximized="0"\n      inkscape:zoom="0.7725"\n      showgrid="false"\n      inkscape:current-layer="svg548"\n      inkscape:cx="332.33171"\n      inkscape:cy="-358.97719"\n      inkscape:window-height="645"\n  />\n  <path\n      id="path796"\n      style="filter:url(#filter4261);fill-rule:evenodd;stroke-width:2.5;fill:#000000;fill-opacity:.49804"\n      inkscape:connector-curvature="0"\n      d="m247.37 16.522c-109.16 1.754-205.44 62.596-225.88 148.91-23.367 98.64 61.132 193.36 188.62 211.43l1.28-0.06 0.7 0.56-2.89 0.1 181.07 85.12-73.39-89.72c88.17-19.47 149.99-72.14 166.52-141.93 23.36-98.64-61.17-193.36-188.66-211.44-15.93-2.26-31.77-3.22-47.37-2.969zm69.05 356.38h0.27v0.46l-0.27-0.46z"\n  />\n  <path\n      id="path795"\n      style="stroke-linejoin:round;fill-rule:evenodd;stroke:#000000;stroke-width:10;fill:#ffffff"\n      inkscape:connector-curvature="0"\n      d="m234.76 8.4309c-109.16 1.754-205.44 62.597-225.88 148.91-23.368 98.64 61.131 193.36 188.62 211.43l1.28-0.06 0.7 0.56-2.89 0.1 181.07 85.12-73.4-89.72c88.18-19.47 150-72.14 166.53-141.93 23.36-98.64-61.17-193.37-188.66-211.44-15.93-2.261-31.77-3.221-47.37-2.97zm69.05 356.37h0.27v0.47l-0.27-0.47z"\n  />\n  <metadata\n      id="metadata9"\n    >\n    <rdf:RDF\n      >\n      <cc:Work\n        >\n        <dc:format\n          >image/svg+xml</dc:format\n        >\n        <dc:type\n            rdf:resource="http://purl.org/dc/dcmitype/StillImage"\n        />\n        <cc:license\n            rdf:resource="http://creativecommons.org/licenses/publicdomain/"\n        />\n        <dc:publisher\n          >\n          <cc:Agent\n              rdf:about="http://openclipart.org/"\n            >\n            <dc:title\n              >Openclipart</dc:title\n            >\n          </cc:Agent\n          >\n        </dc:publisher\n        >\n      </cc:Work\n      >\n      <cc:License\n          rdf:about="http://creativecommons.org/licenses/publicdomain/"\n        >\n        <cc:permits\n            rdf:resource="http://creativecommons.org/ns#Reproduction"\n        />\n        <cc:permits\n            rdf:resource="http://creativecommons.org/ns#Distribution"\n        />\n        <cc:permits\n            rdf:resource="http://creativecommons.org/ns#DerivativeWorks"\n        />\n      </cc:License\n      >\n    </rdf:RDF\n    >\n  </metadata\n  >\n</svg\n>\n',tooltip:!0,isToggleable:!0}),s.bind("isOn","isEnabled").to(i,"value","isEnabled"),this.listenTo(s,"execute",(()=>{e.execute("CalloutCommand"),e.editing.view.focus()})),s}))}}var s=n("ckeditor5/src/enter.js"),o=n("ckeditor5/src/typing.js"),c=n("ckeditor5/src/utils.js");class l extends e.Command{refresh(){this.value=this._getValue(),this.isEnabled=this._checkEnabled()}execute(e={}){const t=this.editor.model,n=t.schema,r=t.document.selection,i=Array.from(r.getSelectedBlocks()),s=void 0===e.forceValue?!this.value:e.forceValue;t.change((e=>{if(s){const t=i.filter((e=>this._findCallOut(e)||this._checkCanBeCallOut(n,e)));this._applyCallOut(e,t)}else this._removeCallOut(e,i.filter(this._findCallOut))}))}_getValue(){const e=this.editor.model.document.selection,t=(0,c.first)(e.getSelectedBlocks());return!(!t||!this._findCallOut(t))}_checkEnabled(){if(this.value)return!0;const e=this.editor.model.document.selection,t=this.editor.model.schema,n=(0,c.first)(e.getSelectedBlocks());return!!n&&this._checkCanBeCallOut(t,n)}_removeCallOut(e,t){this._getRangesOfCallOutGroups(e,t).reverse().forEach((t=>{if(t.start.isAtStart&&t.end.isAtEnd)return void e.unwrap(t.start.parent);if(t.start.isAtStart){const n=e.createPositionBefore(t.start.parent);return void e.move(t,n)}t.end.isAtEnd||e.split(t.end);const n=e.createPositionAfter(t.end.parent);e.move(t,n)}))}_applyCallOut(e,t){const n=[];this._getRangesOfCallOutGroups(e,t).reverse().forEach((t=>{let r=this._findCallOut(t.start);r||(r=e.createElement("callOut"),e.wrap(t,r)),n.push(r)})),n.reverse().reduce(((t,n)=>t.nextSibling==n?(e.merge(e.createPositionAfter(t)),t):n))}_findCallOut(e){return"callOut"==e.parent.name?e.parent:null}_getRangesOfCallOutGroups(e,t){let n,r=0;const i=[];for(;r<t.length;){const s=t[r],o=t[r+1];n||(n=e.createPositionBefore(s)),o&&s.nextSibling==o||(i.push(e.createRange(n,e.createPositionAfter(s))),n=null),r++}return i}_checkCanBeCallOut(e,t){const n=e.checkChild(t.parent,"callOut"),r=e.checkChild(["$root","callOut"],t);return n&&r}}class a extends e.Plugin{static get requires(){return[s.Enter,o.Delete]}init(){const e=this.editor,t=e.model.schema;e.commands.add("CalloutCommand",new l(e)),t.register("callOut",{inheritAllFrom:"$container"}),e.conversion.elementToElement({model:"callOut",view:{name:"div",classes:"callout-wrapper"}}),e.model.document.registerPostFixer((n=>{const r=e.model.document.differ.getChanges();for(const e of r)if("insert"==e.type){const r=e.position.nodeAfter;if(!r)continue;if(r.is("element","callOut")&&r.isEmpty)return n.remove(r),!0;if(r.is("element","callOut")&&!t.checkChild(e.position,r))return n.unwrap(r),!0;if(r.is("element")){const e=n.createRangeIn(r);for(const r of e.getItems())if(r.is("element","callOut")&&!t.checkChild(n.createPositionBefore(r),r))return n.unwrap(r),!0}}else if("remove"==e.type){const t=e.position.parent;if(t.is("element","callOut")&&t.isEmpty)return n.remove(t),!0}return!1}));const n=this.editor.editing.view.document,r=e.model.document.selection,i=e.commands.get("callOut");this.listenTo(n,"enter",((t,n)=>{if(!r.isCollapsed||!i.value)return;r.getLastPosition().parent.isEmpty&&(e.execute("callOut"),e.editing.view.scrollToTheSelection(),n.preventDefault(),t.stop())}),{context:"callout"}),this.listenTo(n,"delete",((t,n)=>{if("backward"!=n.direction||!r.isCollapsed||!i.value)return;const s=r.getLastPosition().parent;s.isEmpty&&!s.previousSibling&&(e.execute("callOut"),e.editing.view.scrollToTheSelection(),n.preventDefault(),t.stop())}),{context:"callout"})}}class d extends e.Plugin{static get requires(){return[a,i]}}const u={Callout:d}})(),r=r.default})()));