/**
 * @file
 * Autolink behavior for fixing schemes into editor links.
 */

(function ($, Drupal) {

    'use strict';

    /**
     * Add mailto scheme to editor email links.
     *
     * Automatically prefix input URL with the 'mailto:' scheme when it looks
     * like the user is trying to link to an email address.
     *
     * @type {Drupal~behavior}
     *
     * @prop {Drupal~behaviorAttach} attach
     *   Attaches the autolink 'mailto:' behavior to the editor link dialog.
     */
    Drupal.behaviors.EditorAutolinkMailto = {
        attach: function (context, settings) {

            // Selector for our editor link href field.
            let inputSelector = '.editor-link-dialog input[data-drupal-selector="edit-attributes-href"]';

            // Scheme used for email address links.
            // https://www.rfc-editor.org/rfc/rfc2368
            let emailHrefScheme = 'mailto:';

            // Scheme used for telephone number links.
            // https://www.rfc-editor.org/rfc/rfc2806
            let telHrefScheme = 'tel:';

            // This complex regex is meant to match on text that looks like an email
            // address. Taken from: https://stackoverflow.com/questions/46155
            let emailRegEx = /^(([^<>()\[\]\.,;:\s@\"]+(\.[^<>()\[\]\.,;:\s@\"]+)*)|(\".+\"))@(([^<>()[\]\.,;:\s@\"]+\.)+[^<>()[\]\.,;:\s@\"]{2,})$/i;

            let telRegEx = /^([+]?[(]?[0-9]{1,4}[)]?)?[\s\d\./#*,;-]{7,}$/i;

            $(inputSelector, context).on('change keyup', function () {
                if (emailRegEx.test(this.value)) {
                    $(this).val(emailHrefScheme + $(this).val());
                } else if (telRegEx.test(this.value)) {
                    // Valid telephone href characters are:
                    // - international prefix +
                    // - digits 0-9
                    // - pound #
                    // - asterisk *
                    // - pause ,
                    // - wait ;
                    // - separator -
                    // https://www.rfc-editor.org/rfc/rfc2806
                    let telVal = $(this).val()
                        // Replace separators.
                        .replace(/[\s\./-]+/g, '-')
                        // Remove other invalid characters.
                        .replace(/[^+\d#*,;-]+/g, '');
                    $(this).val(telHrefScheme + telVal);
                }
            });
        }
    };
})(jQuery, Drupal);
