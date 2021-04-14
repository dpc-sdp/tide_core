/**
 * @file
 * JS paragraphs.enhanced_modal.js.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Click handler for clicking the enhance paragraph modal.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.paragraphsEnhancedModalAdd = {
    attach: function (context) {
      $('.paragraphs-add-dialog-enhanced .paragraphs-add-dialog-row', context).once('add-click-handler').on('click', function (event) {
        var $button = $(this).find('input.button').first();
        $button.trigger('mousedown');
        // Stop default execution of click event.
        event.preventDefault();
        event.stopPropagation();
      });
    }
  };

})(jQuery, Drupal);
