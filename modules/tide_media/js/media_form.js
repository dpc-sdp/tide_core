/**
 * @file
 * Media form.
 */

(function ($, Drupal) {
    'use strict';

    Drupal.behaviors.tide_media_media_form = {
        attach: function (context, settings) {
            $(document).ajaxSend(function (event, xhr, settings) {
                $('.form-submit').prop('disabled', true);
            });
            $(document).ajaxComplete(function (event, xhr, settings) {
                $('.form-submit').prop('disabled', false);
            });
        }
    };
}(jQuery, Drupal));