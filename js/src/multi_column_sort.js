import $ from 'jquery';
import { AJAX } from './modules/ajax.js';
import { CommonParams } from './modules/common.js';
import { ajaxShowMessage } from './modules/ajax-message.js';

/**
 * @fileoverview    Implements the shiftkey + click remove column
 *                  from order by clause functionality
 * @name            columndelete
 *
 * @requires    jQuery
 */

AJAX.registerOnload('multi_column_sort.js', function () {
    $('th.draggable.column_heading.pointer.marker a').on('click', function (event) {
        var orderUrlRemove = $(this).parent().find('input[name="url-remove-order"]').val();
        var orderUrlAdd = $(this).parent().find('input[name="url-add-order"]').val();
        var argsep = CommonParams.get('arg_separator');
        if (event.ctrlKey || event.altKey) {
            event.preventDefault();
            AJAX.source = $(this);
            ajaxShowMessage();
            orderUrlRemove += argsep + 'ajax_request=true' + argsep + 'ajax_page_request=true';
            $.post('index.php?route=/sql', orderUrlRemove, AJAX.responseHandler);
        } else if (event.shiftKey) {
            event.preventDefault();
            AJAX.source = $(this);
            ajaxShowMessage();
            orderUrlAdd += argsep + 'ajax_request=true' + argsep + 'ajax_page_request=true';
            $.post('index.php?route=/sql', orderUrlAdd, AJAX.responseHandler);
        }
    });
});

AJAX.registerTeardown('multi_column_sort.js', function () {
    $(document).off('click', 'th.draggable.column_heading.pointer.marker a');
});
