"use strict";
(self["webpackChunkphpmyadmin"] = self["webpackChunkphpmyadmin"] || []).push([[64],{

/***/ 1:
/***/ (function(module) {

module.exports = jQuery;

/***/ }),

/***/ 90:
/***/ (function(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(1);
/* harmony import */ var _modules_ajax_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(7);



/**
 * JSON syntax highlighting transformation plugin
 *
 * @package PhpMyAdmin
 */
_modules_ajax_js__WEBPACK_IMPORTED_MODULE_1__.AJAX.registerOnload('transformations/json_editor.js', function () {
  jquery__WEBPACK_IMPORTED_MODULE_0__('textarea.transform_json_editor').each(function () {
    window.CodeMirror.fromTextArea(this, {
      lineNumbers: true,
      matchBrackets: true,
      indentUnit: 4,
      mode: 'application/json',
      lineWrapping: true
    });
  });
});

/***/ })

},
/******/ function(__webpack_require__) { // webpackRuntimeModules
/******/ var __webpack_exec__ = function(moduleId) { return __webpack_require__(__webpack_require__.s = moduleId); }
/******/ __webpack_require__.O(0, [49], function() { return __webpack_exec__(90); });
/******/ var __webpack_exports__ = __webpack_require__.O();
/******/ }
]);
//# sourceMappingURL=json_editor.js.map