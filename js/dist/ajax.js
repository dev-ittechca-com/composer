"use strict";

/* global ErrorReport */
// js/error_report.js

/**
 * This object handles ajax requests for pages. It also
 * handles the reloading of the main menu and scripts.
 *
 * @test-module AJAX
 */
var AJAX = {
  /**
   * @var {boolean} active Whether we are busy
   */
  active: false,

  /**
   * @var {object} source The object whose event initialized the request
   */
  source: null,

  /**
   * @var {object} xhr A reference to the ajax request that is currently running
   */
  xhr: null,

  /**
   * @var {object} lockedTargets, list of locked targets
   */
  lockedTargets: {},
  // eslint-disable-next-line valid-jsdoc

  /**
   * @var {Function} callback Callback to execute after a successful request
   *                          Used by PMA_commonFunctions from common.js
   */
  callback: function callback() {},

  /**
   * @var {boolean} debug Makes noise in your Firebug console
   */
  debug: false,

  /**
   * @var {object} $msgbox A reference to a jQuery object that links to a message
   *                     box that is generated by Functions.ajaxShowMessage()
   */
  $msgbox: null,

  /**
   * Given the filename of a script, returns a hash to be
   * used to refer to all the events registered for the file
   *
   * @param {string} key key The filename for which to get the event name
   *
   * @return {number}
   */
  hash: function hash(key) {
    var newKey = key;
    /* https://burtleburtle.net/bob/hash/doobs.html#one */

    newKey += '';
    var len = newKey.length;
    var hash = 0;
    var i = 0;

    for (; i < len; ++i) {
      hash += newKey.charCodeAt(i);
      hash += hash << 10;
      hash ^= hash >> 6;
    }

    hash += hash << 3;
    hash ^= hash >> 11;
    hash += hash << 15;
    return Math.abs(hash);
  },

  /**
   * Registers an onload event for a file
   *
   * @param {string} file   The filename for which to register the event
   * @param {Function} func The function to execute when the page is ready
   *
   * @return {self} For chaining
   */
  registerOnload: function registerOnload(file, func) {
    var eventName = 'onload_' + AJAX.hash(file);
    $(document).on(eventName, func);

    if (this.debug) {
      // eslint-disable-next-line no-console
      console.log( // no need to translate
      'Registered event ' + eventName + ' for file ' + file);
    }

    return this;
  },

  /**
   * Registers a teardown event for a file. This is useful to execute functions
   * that unbind events for page elements that are about to be removed.
   *
   * @param {string} file   The filename for which to register the event
   * @param {Function} func The function to execute when
   *                        the page is about to be torn down
   *
   * @return {self} For chaining
   */
  registerTeardown: function registerTeardown(file, func) {
    var eventName = 'teardown_' + AJAX.hash(file);
    $(document).on(eventName, func);

    if (this.debug) {
      // eslint-disable-next-line no-console
      console.log( // no need to translate
      'Registered event ' + eventName + ' for file ' + file);
    }

    return this;
  },

  /**
   * Called when a page has finished loading, once for every
   * file that registered to the onload event of that file.
   *
   * @param {string} file The filename for which to fire the event
   *
   * @return {void}
   */
  fireOnload: function fireOnload(file) {
    var eventName = 'onload_' + AJAX.hash(file);
    $(document).trigger(eventName);

    if (this.debug) {
      // eslint-disable-next-line no-console
      console.log( // no need to translate
      'Fired event ' + eventName + ' for file ' + file);
    }
  },

  /**
   * Called just before a page is torn down, once for every
   * file that registered to the teardown event of that file.
   *
   * @param {string} file The filename for which to fire the event
   *
   * @return {void}
   */
  fireTeardown: function fireTeardown(file) {
    var eventName = 'teardown_' + AJAX.hash(file);
    $(document).triggerHandler(eventName);

    if (this.debug) {
      // eslint-disable-next-line no-console
      console.log( // no need to translate
      'Fired event ' + eventName + ' for file ' + file);
    }
  },

  /**
   * function to handle lock page mechanism
   *
   * @param event the event object
   *
   * @return {void}
   */
  lockPageHandler: function lockPageHandler(event) {
    // don't consider checkbox event
    if (typeof event.target !== 'undefined') {
      if (event.target.type === 'checkbox') {
        return;
      }
    }

    var newHash = null;
    var oldHash = null;
    var lockId; // CodeMirror lock

    if (event.data.value === 3) {
      newHash = event.data.content;
      oldHash = true;
      lockId = 'cm';
    } else {
      // Don't lock on enter.
      if (0 === event.charCode) {
        return;
      }

      lockId = $(this).data('lock-id');

      if (typeof lockId === 'undefined') {
        return;
      }
      /*
       * @todo Fix Code mirror does not give correct full value (query)
       * in textarea, it returns only the change in content.
       */


      if (event.data.value === 1) {
        newHash = AJAX.hash($(this).val());
      } else {
        newHash = AJAX.hash($(this).is(':checked'));
      }

      oldHash = $(this).data('val-hash');
    } // Set lock if old value !== new value
    // otherwise release lock


    if (oldHash !== newHash) {
      AJAX.lockedTargets[lockId] = true;
    } else {
      delete AJAX.lockedTargets[lockId];
    } // Show lock icon if locked targets is not empty.
    // otherwise remove lock icon


    if (!jQuery.isEmptyObject(AJAX.lockedTargets)) {
      $('#lock_page_icon').html(Functions.getImage('s_lock', Messages.strLockToolTip).toString());
    } else {
      $('#lock_page_icon').html('');
    }
  },

  /**
   * resets the lock
   *
   * @return {void}
   */
  resetLock: function resetLock() {
    AJAX.lockedTargets = {};
    $('#lock_page_icon').html('');
  },
  handleMenu: {
    replace: function replace(content) {
      $('#floating_menubar').html(content) // Remove duplicate wrapper
      // TODO: don't send it in the response
      .children().first().remove();
      $('#topmenu').menuResizer(Functions.mainMenuResizerCallback);
    }
  },

  /**
   * Event handler for clicks on links and form submissions
   *
   * @param {KeyboardEvent} event Event data
   *
   * @return {boolean | void}
   */
  requestHandler: function requestHandler(event) {
    // In some cases we don't want to handle the request here and either
    // leave the browser deal with it natively (e.g: file download)
    // or leave an existing ajax event handler present elsewhere deal with it
    var href = $(this).attr('href');

    if (typeof event !== 'undefined' && (event.shiftKey || event.ctrlKey)) {
      return true;
    } else if ($(this).attr('target')) {
      return true;
    } else if ($(this).hasClass('ajax') || $(this).hasClass('disableAjax')) {
      // reset the lockedTargets object, as specified AJAX operation has finished
      AJAX.resetLock();
      return true;
    } else if (href && href.match(/^#/)) {
      return true;
    } else if (href && href.match(/^mailto/)) {
      return true;
    } else if ($(this).hasClass('ui-datepicker-next') || $(this).hasClass('ui-datepicker-prev')) {
      return true;
    }

    if (typeof event !== 'undefined') {
      event.preventDefault();
      event.stopImmediatePropagation();
    } // triggers a confirm dialog if:
    // the user has performed some operations on loaded page
    // the user clicks on some link, (won't trigger for buttons)
    // the click event is not triggered by script


    if (typeof event !== 'undefined' && event.type === 'click' && event.isTrigger !== true && !jQuery.isEmptyObject(AJAX.lockedTargets) && confirm(Messages.strConfirmNavigation) === false) {
      return false;
    }

    AJAX.resetLock();
    var isLink = !!href || false;
    var previousLinkAborted = false;

    if (AJAX.active === true) {
      // Cancel the old request if abortable, when the user requests
      // something else. Otherwise silently bail out, as there is already
      // a request well in progress.
      if (AJAX.xhr) {
        // In case of a link request, attempt aborting
        AJAX.xhr.abort();

        if (AJAX.xhr.status === 0 && AJAX.xhr.statusText === 'abort') {
          // If aborted
          AJAX.$msgbox = Functions.ajaxShowMessage(Messages.strAbortedRequest);
          AJAX.active = false;
          AJAX.xhr = null;
          previousLinkAborted = true;
        } else {
          // If can't abort
          return false;
        }
      } else {
        // In case submitting a form, don't attempt aborting
        return false;
      }
    }

    AJAX.source = $(this);
    $('html, body').animate({
      scrollTop: 0
    }, 'fast');
    var url = isLink ? href : $(this).attr('action');
    var argsep = CommonParams.get('arg_separator');
    var params = 'ajax_request=true' + argsep + 'ajax_page_request=true';
    var dataPost = AJAX.source.getPostData();

    if (!isLink) {
      params += argsep + $(this).serialize();
    } else if (dataPost) {
      params += argsep + dataPost;
      isLink = false;
    }

    if (AJAX.debug) {
      // eslint-disable-next-line no-console
      console.log('Loading: ' + url); // no need to translate
    }

    if (isLink) {
      AJAX.active = true;
      AJAX.$msgbox = Functions.ajaxShowMessage(); // Save reference for the new link request

      AJAX.xhr = $.get(url, params, AJAX.responseHandler);
      var state = {
        url: href
      };

      if (previousLinkAborted) {
        // hack: there is already an aborted entry on stack
        // so just modify the aborted one
        history.replaceState(state, null, href);
      } else {
        history.pushState(state, null, href);
      }
    } else {
      /**
       * Manually fire the onsubmit event for the form, if any.
       * The event was saved in the jQuery data object by an onload
       * handler defined below. Workaround for bug #3583316
       */
      var onsubmit = $(this).data('onsubmit'); // Submit the request if there is no onsubmit handler
      // or if it returns a value that evaluates to true

      if (typeof onsubmit !== 'function' || onsubmit.apply(this, [event])) {
        AJAX.active = true;
        AJAX.$msgbox = Functions.ajaxShowMessage();

        if ($(this).attr('id') === 'login_form') {
          $.post(url, params, AJAX.loginResponseHandler);
        } else {
          $.post(url, params, AJAX.responseHandler);
        }
      }
    }
  },

  /**
   * Response handler to handle login request from login modal after session expiration
   *
   * To refer to self use 'AJAX', instead of 'this' as this function
   * is called in the jQuery context.
   *
   * @param {object} data Event data
   *
   * @return {void}
   */
  loginResponseHandler: function loginResponseHandler(data) {
    if (typeof data === 'undefined' || data === null) {
      return;
    }

    Functions.ajaxRemoveMessage(AJAX.$msgbox);
    CommonParams.set('token', data.new_token);
    AJAX.scriptHandler.load([]);

    if (data.displayMessage) {
      $('#page_content').prepend(data.displayMessage);
      Functions.highlightSql($('#page_content'));
    }

    $('#pma_errors').remove();
    var msg = '';

    if (data.errSubmitMsg) {
      msg = data.errSubmitMsg;
    }

    if (data.errors) {
      $('<div></div>', {
        id: 'pma_errors',
        class: 'clearfloat'
      }).insertAfter('#selflink').append(data.errors); // bind for php error reporting forms (bottom)

      $('#pma_ignore_errors_bottom').on('click', function (e) {
        e.preventDefault();
        Functions.ignorePhpErrors();
      });
      $('#pma_ignore_all_errors_bottom').on('click', function (e) {
        e.preventDefault();
        Functions.ignorePhpErrors(false);
      }); // In case of 'sendErrorReport'='always'
      // submit the hidden error reporting form.

      if (data.sendErrorAlways === '1' && data.stopErrorReportLoop !== '1') {
        $('#pma_report_errors_form').trigger('submit');
        Functions.ajaxShowMessage(Messages.phpErrorsBeingSubmitted, false);
        $('html, body').animate({
          scrollTop: $(document).height()
        }, 'slow');
      } else if (data.promptPhpErrors) {
        // otherwise just prompt user if it is set so.
        msg = msg + Messages.phpErrorsFound; // scroll to bottom where all the errors are displayed.

        $('html, body').animate({
          scrollTop: $(document).height()
        }, 'slow');
      }
    }

    Functions.ajaxShowMessage(msg, false); // bind for php error reporting forms (popup)

    $('#pma_ignore_errors_popup').on('click', function () {
      Functions.ignorePhpErrors();
    });
    $('#pma_ignore_all_errors_popup').on('click', function () {
      Functions.ignorePhpErrors(false);
    });

    if (typeof data.success !== 'undefined' && data.success) {
      // reload page if user trying to login has changed
      if (CommonParams.get('user') !== data.params.user) {
        window.location = 'index.php';
        Functions.ajaxShowMessage(Messages.strLoading, false);
        AJAX.active = false;
        AJAX.xhr = null;
        return;
      } // remove the login modal if the login is successful otherwise show error.


      if (typeof data.logged_in !== 'undefined' && data.logged_in === 1) {
        if ($('#modalOverlay').length) {
          $('#modalOverlay').remove();
        }

        $('fieldset.disabled_for_expiration').removeAttr('disabled').removeClass('disabled_for_expiration');
        AJAX.fireTeardown('functions.js');
        AJAX.fireOnload('functions.js');
      }

      if (typeof data.new_token !== 'undefined') {
        $('input[name=token]').val(data.new_token);
      }
    } else if (typeof data.logged_in !== 'undefined' && data.logged_in === 0) {
      $('#modalOverlay').replaceWith(data.error);
    } else {
      Functions.ajaxShowMessage(data.error, false);
      AJAX.active = false;
      AJAX.xhr = null;
      Functions.handleRedirectAndReload(data);

      if (data.fieldWithError) {
        $(':input.error').removeClass('error');
        $('#' + data.fieldWithError).addClass('error');
      }
    }
  },

  /**
   * Called after the request that was initiated by this.requestHandler()
   * has completed successfully or with a caught error. For completely
   * failed requests or requests with uncaught errors, see the .ajaxError
   * handler at the bottom of this file.
   *
   * To refer to self use 'AJAX', instead of 'this' as this function
   * is called in the jQuery context.
   *
   * @param {object} data Event data
   *
   * @return {void}
   */
  responseHandler: function responseHandler(data) {
    if (typeof data === 'undefined' || data === null) {
      return;
    }

    if (typeof data.success !== 'undefined' && data.success) {
      $('html, body').animate({
        scrollTop: 0
      }, 'fast');
      Functions.ajaxRemoveMessage(AJAX.$msgbox);

      if (data.redirect) {
        Functions.ajaxShowMessage(data.redirect, false);
        AJAX.active = false;
        AJAX.xhr = null;
        return;
      }

      AJAX.scriptHandler.reset(function () {
        if (data.reloadNavigation) {
          Navigation.reload();
        }

        if (data.title) {
          $('title').replaceWith(data.title);
        }

        if (data.menu) {
          var state = {
            url: data.selflink,
            menu: data.menu
          };
          history.replaceState(state, null);
          AJAX.handleMenu.replace(data.menu);
        }

        if (data.disableNaviSettings) {
          Navigation.disableSettings();
        } else {
          Navigation.ensureSettings(data.selflink);
        } // Remove all containers that may have
        // been added outside of #page_content


        $('body').children().not('#pma_navigation').not('#floating_menubar').not('#page_nav_icons').not('#page_content').not('#selflink').not('#pma_header').not('#pma_footer').not('#pma_demo').not('#pma_console_container').not('#prefs_autoload').remove(); // Replace #page_content with new content

        if (data.message && data.message.length > 0) {
          $('#page_content').replaceWith('<div id=\'page_content\'>' + data.message + '</div>');
          Functions.highlightSql($('#page_content'));
          Functions.checkNumberOfFields();
        }

        if (data.selflink) {
          var source = data.selflink.split('?')[0]; // Check for faulty links

          var $selflinkReplace = {
            'index.php?route=/import': 'index.php?route=/table/sql',
            'index.php?route=/table/chart': 'index.php?route=/sql',
            'index.php?route=/table/gis-visualization': 'index.php?route=/sql'
          };

          if ($selflinkReplace[source]) {
            var replacement = $selflinkReplace[source];
            data.selflink = data.selflink.replace(source, replacement);
          }

          $('#selflink').find('> a').attr('href', data.selflink);
        }

        if (data.params) {
          CommonParams.setAll(data.params);
        }

        if (data.scripts) {
          AJAX.scriptHandler.load(data.scripts);
        }

        if (data.displayMessage) {
          $('#page_content').prepend(data.displayMessage);
          Functions.highlightSql($('#page_content'));
        }

        $('#pma_errors').remove();
        var msg = '';

        if (data.errSubmitMsg) {
          msg = data.errSubmitMsg;
        }

        if (data.errors) {
          $('<div></div>', {
            id: 'pma_errors',
            class: 'clearfloat'
          }).insertAfter('#selflink').append(data.errors); // bind for php error reporting forms (bottom)

          $('#pma_ignore_errors_bottom').on('click', function (e) {
            e.preventDefault();
            Functions.ignorePhpErrors();
          });
          $('#pma_ignore_all_errors_bottom').on('click', function (e) {
            e.preventDefault();
            Functions.ignorePhpErrors(false);
          }); // In case of 'sendErrorReport'='always'
          // submit the hidden error reporting form.

          if (data.sendErrorAlways === '1' && data.stopErrorReportLoop !== '1') {
            $('#pma_report_errors_form').trigger('submit');
            Functions.ajaxShowMessage(Messages.phpErrorsBeingSubmitted, false);
            $('html, body').animate({
              scrollTop: $(document).height()
            }, 'slow');
          } else if (data.promptPhpErrors) {
            // otherwise just prompt user if it is set so.
            msg = msg + Messages.phpErrorsFound; // scroll to bottom where all the errors are displayed.

            $('html, body').animate({
              scrollTop: $(document).height()
            }, 'slow');
          }
        }

        Functions.ajaxShowMessage(msg, false); // bind for php error reporting forms (popup)

        $('#pma_ignore_errors_popup').on('click', function () {
          Functions.ignorePhpErrors();
        });
        $('#pma_ignore_all_errors_popup').on('click', function () {
          Functions.ignorePhpErrors(false);
        });

        if (typeof AJAX.callback === 'function') {
          AJAX.callback.call();
        }

        AJAX.callback = function () {};
      });
    } else {
      Functions.ajaxShowMessage(data.error, false);
      Functions.ajaxRemoveMessage(AJAX.$msgbox);
      var $ajaxError = $('<div></div>');
      $ajaxError.attr({
        'id': 'ajaxError'
      });
      $('#page_content').append($ajaxError);
      $ajaxError.html(data.error);
      $('html, body').animate({
        scrollTop: $(document).height()
      }, 200);
      AJAX.active = false;
      AJAX.xhr = null;
      Functions.handleRedirectAndReload(data);

      if (data.fieldWithError) {
        $(':input.error').removeClass('error');
        $('#' + data.fieldWithError).addClass('error');
      }
    }
  },

  /**
   * This object is in charge of downloading scripts,
   * keeping track of what's downloaded and firing
   * the onload event for them when the page is ready.
   */
  scriptHandler: {
    /**
     * @var {string[]} scripts The list of files already downloaded
     */
    scripts: [],

    /**
     * @var {string} scriptsVersion version of phpMyAdmin from which the
     *                              scripts have been loaded
     */
    scriptsVersion: null,

    /**
     * @var {string[]} scriptsToBeLoaded The list of files that
     *                                   need to be downloaded
     */
    scriptsToBeLoaded: [],

    /**
     * @var {string[]} scriptsToBeFired The list of files for which
     *                                  to fire the onload and unload events
     */
    scriptsToBeFired: [],
    scriptsCompleted: false,

    /**
     * Records that a file has been downloaded
     *
     * @param {string} file The filename
     * @param {string} fire Whether this file will be registering
     *                      onload/teardown events
     *
     * @return {self} For chaining
     */
    add: function add(file, fire) {
      this.scripts.push(file);

      if (fire) {
        // Record whether to fire any events for the file
        // This is necessary to correctly tear down the initial page
        this.scriptsToBeFired.push(file);
      }

      return this;
    },

    /**
     * Download a list of js files in one request
     *
     * @param {string[]} files An array of filenames and flags
     * @param {Function} callback
     *
     * @return {void}
     */
    load: function load(files, callback) {
      var self = this;
      var i; // Clear loaded scripts if they are from another version of phpMyAdmin.
      // Depends on common params being set before loading scripts in responseHandler

      if (self.scriptsVersion === null) {
        self.scriptsVersion = CommonParams.get('version');
      } else if (self.scriptsVersion !== CommonParams.get('version')) {
        self.scripts = [];
        self.scriptsVersion = CommonParams.get('version');
      }

      self.scriptsCompleted = false;
      self.scriptsToBeFired = []; // We need to first complete list of files to load
      // as next loop will directly fire requests to load them
      // and that triggers removal of them from
      // self.scriptsToBeLoaded

      for (i in files) {
        self.scriptsToBeLoaded.push(files[i].name);

        if (files[i].fire) {
          self.scriptsToBeFired.push(files[i].name);
        }
      }

      for (i in files) {
        var script = files[i].name; // Only for scripts that we don't already have

        if ($.inArray(script, self.scripts) === -1) {
          this.add(script);
          this.appendScript(script, callback);
        } else {
          self.done(script, callback);
        }
      } // Trigger callback if there is nothing else to load


      self.done(null, callback);
    },

    /**
     * Called whenever all files are loaded
     *
     * @param {string} script
     * @param {Function?} callback
     *
     * @return {void}
     */
    done: function done(script, callback) {
      if (typeof ErrorReport !== 'undefined') {
        ErrorReport.wrapGlobalFunctions();
      }

      if ($.inArray(script, this.scriptsToBeFired)) {
        AJAX.fireOnload(script);
      }

      if ($.inArray(script, this.scriptsToBeLoaded)) {
        this.scriptsToBeLoaded.splice($.inArray(script, this.scriptsToBeLoaded), 1);
      }

      if (script === null) {
        this.scriptsCompleted = true;
      }
      /* We need to wait for last signal (with null) or last script load */


      AJAX.active = this.scriptsToBeLoaded.length > 0 || !this.scriptsCompleted;
      /* Run callback on last script */

      if (!AJAX.active && typeof callback === 'function') {
        callback();
      }
    },

    /**
     * Appends a script element to the head to load the scripts
     *
     * @param {string} name
     * @param {Function} callback
     *
     * @return {void}
     */
    appendScript: function appendScript(name, callback) {
      var head = document.head || document.getElementsByTagName('head')[0];
      var script = document.createElement('script');
      var self = this;
      script.type = 'text/javascript';
      var file = name.indexOf('vendor/') !== -1 ? name : 'dist/' + name;
      script.src = 'js/' + file + '?' + 'v=' + encodeURIComponent(CommonParams.get('version'));
      script.async = false;

      script.onload = function () {
        self.done(name, callback);
      };

      head.appendChild(script);
    },

    /**
     * Fires all the teardown event handlers for the current page
     * and rebinds all forms and links to the request handler
     *
     * @param {Function} callback The callback to call after resetting
     *
     * @return {void}
     */
    reset: function reset(callback) {
      for (var i in this.scriptsToBeFired) {
        AJAX.fireTeardown(this.scriptsToBeFired[i]);
      }

      this.scriptsToBeFired = [];
      /**
       * Re-attach a generic event handler to clicks
       * on pages and submissions of forms
       */

      $(document).off('click', 'a').on('click', 'a', AJAX.requestHandler);
      $(document).off('submit', 'form').on('submit', 'form', AJAX.requestHandler);
      callback();
    }
  }
};
/**
 * Here we register a function that will remove the onsubmit event from all
 * forms that will be handled by the generic page loader. We then save this
 * event handler in the "jQuery data", so that we can fire it up later in
 * AJAX.requestHandler().
 *
 * See bug #3583316
 */

AJAX.registerOnload('functions.js', function () {
  // Registering the onload event for functions.js
  // ensures that it will be fired for all pages
  $('form').not('.ajax').not('.disableAjax').each(function () {
    if ($(this).attr('onsubmit')) {
      $(this).data('onsubmit', this.onsubmit).attr('onsubmit', '');
    }
  });
  var $pageContent = $('#page_content');
  /**
   * Workaround for passing submit button name,value on ajax form submit
   * by appending hidden element with submit button name and value.
   */

  $pageContent.on('click', 'form input[type=submit]', function () {
    var buttonName = $(this).attr('name');

    if (typeof buttonName === 'undefined') {
      return;
    }

    $(this).closest('form').append($('<input>', {
      'type': 'hidden',
      'name': buttonName,
      'value': $(this).val()
    }));
  });
  /**
   * Attach event listener to events when user modify visible
   * Input,Textarea and select fields to make changes in forms
   */

  $pageContent.on('keyup change', 'form.lock-page textarea, ' + 'form.lock-page input[type="text"], ' + 'form.lock-page input[type="number"], ' + 'form.lock-page select', {
    value: 1
  }, AJAX.lockPageHandler);
  $pageContent.on('change', 'form.lock-page input[type="checkbox"], ' + 'form.lock-page input[type="radio"]', {
    value: 2
  }, AJAX.lockPageHandler);
  /**
   * Reset lock when lock-page form reset event is fired
   * Note: reset does not bubble in all browser so attach to
   * form directly.
   */

  $('form.lock-page').on('reset', function () {
    AJAX.resetLock();
  });
});
/**
 * Page load event handler
 */

$(function () {
  var menuContent = $('<div></div>').append($('#server-breadcrumb').clone()).append($('#topmenucontainer').clone()).html(); // set initial state reload

  var initState = 'state' in window.history && window.history.state !== null;
  var initURL = $('#selflink').find('> a').attr('href') || location.href;
  var state = {
    url: initURL,
    menu: menuContent
  };
  history.replaceState(state, null);
  $(window).on('popstate', function (event) {
    var initPop = !initState && location.href === initURL;
    initState = true; // check if popstate fired on first page itself

    if (initPop) {
      return;
    }

    var state = event.originalEvent.state;

    if (state && state.menu) {
      AJAX.$msgbox = Functions.ajaxShowMessage();
      var params = 'ajax_request=true' + CommonParams.get('arg_separator') + 'ajax_page_request=true';
      var url = state.url || location.href;
      $.get(url, params, AJAX.responseHandler); // TODO: Check if sometimes menu is not retrieved from server,
      // Not sure but it seems menu was missing only for printview which
      // been removed lately, so if it's right some dead menu checks/fallbacks
      // may need to be removed from this file and Header.php
      // AJAX.handleMenu.replace(event.originalEvent.state.menu);
    }
  });
});
/**
 * Attach a generic event handler to clicks
 * on pages and submissions of forms
 */

$(document).on('click', 'a', AJAX.requestHandler);
$(document).on('submit', 'form', AJAX.requestHandler);
/**
 * Gracefully handle fatal server errors
 * (e.g: 500 - Internal server error)
 */

$(document).on('ajaxError', function (event, request) {
  if (AJAX.debug) {
    // eslint-disable-next-line no-console
    console.log('AJAX error: status=' + request.status + ', text=' + request.statusText);
  } // Don't handle aborted requests


  if (request.status !== 0 || request.statusText !== 'abort') {
    var details = '';
    var state = request.state();

    if (request.status !== 0) {
      details += '<div>' + Functions.escapeHtml(Functions.sprintf(Messages.strErrorCode, request.status)) + '</div>';
    }

    details += '<div>' + Functions.escapeHtml(Functions.sprintf(Messages.strErrorText, request.statusText + ' (' + state + ')')) + '</div>';

    if (state === 'rejected' || state === 'timeout') {
      details += '<div>' + Functions.escapeHtml(Messages.strErrorConnection) + '</div>';
    }

    Functions.ajaxShowMessage('<div class="alert alert-danger" role="alert">' + Messages.strErrorProcessingRequest + details + '</div>', false);
    AJAX.active = false;
    AJAX.xhr = null;
  }
});