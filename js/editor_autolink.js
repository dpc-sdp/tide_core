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
      let hrefScheme = 'mailto:';

      // This complex regex is meant to match on text that looks like an email
      // address. Taken from: https://stackoverflow.com/questions/46155
      let emailRegEx = /^(([^<>()\[\]\.,;:\s@\"]+(\.[^<>()\[\]\.,;:\s@\"]+)*)|(\".+\"))@(([^<>()[\]\.,;:\s@\"]+\.)+[^<>()[\]\.,;:\s@\"]{2,})$/i;

      $(inputSelector, context).on('change', function() {
        if (emailRegEx.test(this.value)) {
          $(this).val(hrefScheme + $(this).val());
        }
      });
    }
  };

  /**
   * Add tel scheme to editor telephone links.
   *
   * Automatically prefix input URL with the 'tel:' scheme when it looks
   * like the user is trying to link to a telephone number. Will also remove
   * space and separator characters not allowed in a tel: URL.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the autolink 'tel:' behavior to the editor link dialog.
   */
  Drupal.behaviors.EditorAutolinkTel = {
    attach: function (context, settings) {

      // Selector for our editor link href field.
      let inputSelector = '.editor-link-dialog input[data-drupal-selector="edit-attributes-href"]';

      // Scheme used for telephone number links.
      // https://www.rfc-editor.org/rfc/rfc2806
      let hrefScheme = 'tel:';

      // This complex regex is meant to match on text that looks like a
      // telephone number.
      let telRegEx = /^([+]?[(]?[0-9]{1,4}[)]?)?[-\s\d\./#*pw]{7,}$/i;

      $(inputSelector, context).on('change', function() {
        if (telRegEx.test(this.value)) {
          $(this).val(hrefScheme + $(this).val().replace(/[^+\d\.#*pw-]+/g, ''));
        }
      });
    }
  };

})(jQuery, Drupal);
