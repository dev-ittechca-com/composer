import $ from 'jquery';
import { AJAX } from './modules/ajax.js';
import { Functions } from './modules/functions.js';
import { KeyHandlerEvents } from './modules/keyhandler.js';
import { PageSettings } from './modules/page_settings.js';
import { crossFramingProtection } from './modules/cross_framing_protection.js';
import { Indexes } from './modules/indexes.js';
import { Config } from './modules/config.js';
import checkNumberOfFields from './modules/functions/checkNumberOfFields.js';
import onloadNavigation from './modules/navigation/event-loader.js';
import { onloadFunctions, teardownFunctions } from './modules/functions/event-loader.js';

AJAX.registerOnload('main.js', () => AJAX.removeSubmitEvents());
$(AJAX.loadEventHandler());

/**
 * Attach a generic event handler to clicks on pages and submissions of forms.
 */
$(document).on('click', 'a', AJAX.requestHandler);
$(document).on('submit', 'form', AJAX.requestHandler);

$(document).on('ajaxError', AJAX.getFatalErrorHandler());

AJAX.registerTeardown('main.js', KeyHandlerEvents.off());
AJAX.registerOnload('main.js', KeyHandlerEvents.on());

crossFramingProtection();

AJAX.registerTeardown('main.js', Config.off());
AJAX.registerOnload('main.js', Config.on());

$.ajaxPrefilter(Functions.addNoCacheToAjaxRequests());

AJAX.registerTeardown('main.js', teardownFunctions());
AJAX.registerOnload('main.js', onloadFunctions());

$(Functions.dismissNotifications());
$(Functions.initializeMenuResizer());
$(Functions.floatingMenuBar());
$(Functions.breadcrumbScrollToTop());

$(onloadNavigation());

AJAX.registerTeardown('main.js', Indexes.off());
AJAX.registerOnload('main.js', Indexes.on());

$(() => checkNumberOfFields());

AJAX.registerTeardown('main.js', () => {
    PageSettings.off();
});

AJAX.registerOnload('main.js', () => {
    PageSettings.on();
});
