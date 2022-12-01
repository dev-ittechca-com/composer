"use strict";
(self["webpackChunkphpmyadmin"] = self["webpackChunkphpmyadmin"] || []).push([[68],{

/***/ 1:
/***/ (function(module) {

module.exports = jQuery;

/***/ }),

/***/ 82:
/***/ (function(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(1);
/* harmony import */ var _modules_ajax_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(4);
/* harmony import */ var _modules_functions_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(5);



_modules_ajax_js__WEBPACK_IMPORTED_MODULE_1__.AJAX.registerOnload('u2f.js', function () {
  var $inputReg = jquery__WEBPACK_IMPORTED_MODULE_0__('#u2f_registration_response');

  if ($inputReg.length > 0) {
    var $formReg = $inputReg.parents('form');
    $formReg.find('input[type=submit]').hide();
    setTimeout(function () {
      // A magic JS function that talks to the USB device. This function will keep polling for the USB device until it finds one.
      var request = JSON.parse($inputReg.attr('data-request'));
      window.u2f.register(request.appId, [request], JSON.parse($inputReg.attr('data-signatures')), function (data) {
        // Handle returning error data
        if (data.errorCode && data.errorCode !== 0) {
          switch (data.errorCode) {
            case 5:
              _modules_functions_js__WEBPACK_IMPORTED_MODULE_2__.Functions.ajaxShowMessage(window.Messages.strU2FTimeout, false, 'error');
              break;

            case 4:
              _modules_functions_js__WEBPACK_IMPORTED_MODULE_2__.Functions.ajaxShowMessage(window.Messages.strU2FErrorRegister, false, 'error');
              break;

            case 3:
              _modules_functions_js__WEBPACK_IMPORTED_MODULE_2__.Functions.ajaxShowMessage(window.Messages.strU2FInvalidClient, false, 'error');
              break;

            case 2:
              _modules_functions_js__WEBPACK_IMPORTED_MODULE_2__.Functions.ajaxShowMessage(window.Messages.strU2FBadRequest, false, 'error');
              break;

            default:
              _modules_functions_js__WEBPACK_IMPORTED_MODULE_2__.Functions.ajaxShowMessage(window.Messages.strU2FUnknown, false, 'error');
              break;
          }

          return;
        } // Fill and submit form.


        $inputReg.val(JSON.stringify(data));
        $formReg.trigger('submit');
      });
    }, 1000);
  }

  var $inputAuth = jquery__WEBPACK_IMPORTED_MODULE_0__('#u2f_authentication_response');

  if ($inputAuth.length > 0) {
    var $formAuth = $inputAuth.parents('form');
    $formAuth.find('input[type=submit]').hide();
    setTimeout(function () {
      // Magic JavaScript talking to your HID
      // appid, challenge, authenticateRequests
      var request = JSON.parse($inputAuth.attr('data-request'));
      window.u2f.sign(request[0].appId, request[0].challenge, request, function (data) {
        // Handle returning error data
        if (data.errorCode && data.errorCode !== 0) {
          switch (data.errorCode) {
            case 5:
              _modules_functions_js__WEBPACK_IMPORTED_MODULE_2__.Functions.ajaxShowMessage(window.Messages.strU2FTimeout, false, 'error');
              break;

            case 4:
              _modules_functions_js__WEBPACK_IMPORTED_MODULE_2__.Functions.ajaxShowMessage(window.Messages.strU2FErrorAuthenticate, false, 'error');
              break;

            case 3:
              _modules_functions_js__WEBPACK_IMPORTED_MODULE_2__.Functions.ajaxShowMessage(window.Messages.strU2FInvalidClient, false, 'error');
              break;

            case 2:
              _modules_functions_js__WEBPACK_IMPORTED_MODULE_2__.Functions.ajaxShowMessage(window.Messages.strU2FBadRequest, false, 'error');
              break;

            default:
              _modules_functions_js__WEBPACK_IMPORTED_MODULE_2__.Functions.ajaxShowMessage(window.Messages.strU2FUnknown, false, 'error');
              break;
          }

          return;
        } // Fill and submit form.


        $inputAuth.val(JSON.stringify(data));
        $formAuth.trigger('submit');
      });
    }, 1000);
  }
});

/***/ })

},
/******/ function(__webpack_require__) { // webpackRuntimeModules
/******/ var __webpack_exec__ = function(moduleId) { return __webpack_require__(__webpack_require__.s = moduleId); }
/******/ __webpack_require__.O(0, [49], function() { return __webpack_exec__(82); });
/******/ var __webpack_exports__ = __webpack_require__.O();
/******/ }
]);
//# sourceMappingURL=u2f.js.map