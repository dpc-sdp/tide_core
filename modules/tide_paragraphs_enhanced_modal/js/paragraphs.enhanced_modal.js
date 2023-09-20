/**
 * @file
 * JS paragraphs.enhanced_modal.js.
 */

(function ($, Drupal, once) {

  'use strict';

  Drupal.behaviors.paragraphsEnhancedModalAdd = {
    attach: function (context) {
      console.log('I am here');
      $(once('add-click-handler', '.paragraphs-add-dialog-enhanced .paragraphs-add-dialog-row', context)).on('click', function (event) {
        var $button = $(this).find('input.button').first();
        console.log($button);
        $button.trigger('mousedown');
        // Stop default execution of click event.
        event.preventDefault();
        event.stopPropagation();
      });
    }
  };

})(jQuery, Drupal, once);
