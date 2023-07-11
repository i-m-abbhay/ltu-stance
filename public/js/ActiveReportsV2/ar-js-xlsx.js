/*!
 * @grapecity/activereports 2.0.1
 * Description: ActiveReportsJS
 * https://www.npmjs.com/package/@grapecity/activereports
 * Copyright (c) 2020 GrapeCity, Inc.
 * Licensed under the Commercial license
 */
/*!
 * @grapecity/ar-js-xlsx v1.4.0-beta.103
 * Author: GrapeCity, Inc
 * Description: ActiveReports PageReport XSLX export module
 */
!function webpackUniversalModuleDefinition(G,J){"object"==typeof exports&&"object"==typeof module?module.exports=J(require("@grapecity/ar-js-pagereport")):"function"==typeof define&&define.amd?define(["@grapecity/ar-js-pagereport"],J):"object"==typeof exports?exports.XlsxExport=J(require("@grapecity/ar-js-pagereport")):(G.GC=G.GC||{},G.GC.ActiveReports=G.GC.ActiveReports||{},G.GC.ActiveReports.XlsxExport=J(G["@grapecity/ar-js-pagereport"]))}(window,(function(__WEBPACK_EXTERNAL_MODULE__82__){return function(G){var J={};function __webpack_require__(Q){if(J[Q])return J[Q].exports;var ee=J[Q]={i:Q,l:!1,exports:{}};return G[Q].call(ee.exports,ee,ee.exports,__webpack_require__),ee.l=!0,ee.exports}return __webpack_require__.m=G,__webpack_require__.c=J,__webpack_require__.d=function(G,J,Q){__webpack_require__.o(G,J)||Object.defineProperty(G,J,{enumerable:!0,get:Q})},__webpack_require__.r=function(G){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(G,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(G,"__esModule",{value:!0})},__webpack_require__.t=function(G,J){if(1&J&&(G=__webpack_require__(G)),8&J)return G;if(4&J&&"object"==typeof G&&G&&G.__esModule)return G;var Q=Object.create(null);if(__webpack_require__.r(Q),Object.defineProperty(Q,"default",{enumerable:!0,value:G}),2&J&&"string"!=typeof G)for(var ee in G)__webpack_require__.d(Q,ee,function(J){return G[J]}.bind(null,ee));return Q},__webpack_require__.n=function(G){var J=G&&G.__esModule?function getDefault(){return G.default}:function getModuleExports(){return G};return __webpack_require__.d(J,"a",J),J},__webpack_require__.o=function(G,J){return Object.prototype.hasOwnProperty.call(G,J)},__webpack_require__.p="",__webpack_require__(__webpack_require__.s=40)}([function(G,J){"function"==typeof Object.create?G.exports=function inherits(G,J){G.super_=J,G.prototype=Object.create(J.prototype,{constructor:{value:G,enumerable:!1,writable:!0,configurable:!0}})}:G.exports=function inherits(G,J){G.super_=J;var TempCtor=function(){};TempCtor.prototype=J.prototype,G.prototype=new TempCtor,G.prototype.constructor=G}},function(G,J,Q){var ee=Q(2),ae=ee.Buffer;function copyProps(G,J){for(var Q in G)J[Q]=G[Q]}function SafeBuffer(G,J,Q){return ae(G,J,Q)}ae.from&&ae.alloc&&ae.allocUnsafe&&ae.allocUnsafeSlow?G.exports=ee:(copyProps(ee,J),J.Buffer=SafeBuffer),copyProps(ae,SafeBuffer),SafeBuffer.from=function(G,J,Q){if("number"==typeof G)throw new TypeError("Argument must not be a number");return ae(G,J,Q)},SafeBuffer.alloc=function(G,J,Q){if("number"!=typeof G)throw new TypeError("Argument must be a number");var ee=ae(G);return void 0!==J?"string"==typeof Q?ee.fill(J,Q):ee.fill(J):ee.fill(0),ee},SafeBuffer.allocUnsafe=function(G){if("number"!=typeof G)throw new TypeError("Argument must be a number");return ae(G)},SafeBuffer.allocUnsafeSlow=function(G){if("number"!=typeof G)throw new TypeError("Argument must be a number");return ee.SlowBuffer(G)}},function(G,J,Q){"use strict";(function(G){
/*!
 * The buffer module from node.js, for the browser.
 *
 * @author   Feross Aboukhadijeh <feross@feross.org> <http://feross.org>
 * @license  MIT
 */