/**
 * @file
 * Paragraphs actions JS code for paragraphs actions button.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Process paragraph_actions elements.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches paragraphsActions behaviors.
   */
  Drupal.behaviors.tideGrantParagraphsActions = {
    attach: function (context, settings) {
      // Monitor if timeline paragraphs changes to update tab required label
      $(context).find('#edit-group-grant-timeline').bind("DOMSubtreeModified",function(){
        if (!$(context).find('tr.paragraph-type--timeline').length) {
          if($(context).find("#edit-group-body-content div.horizontal-tabs li.horizontal-tab-button-1 strong").hasClass( "form-required" )) {
            $(context).find("#edit-group-body-content div.horizontal-tabs li.horizontal-tab-button-1 strong").removeClass("form-required");
          }
        } else {
          if(!$(context).find("#edit-group-body-content div.horizontal-tabs li.horizontal-tab-button-1 strong").hasClass( "form-required" )) {
            $(context).find("#edit-group-body-content div.horizontal-tabs li.horizontal-tab-button-1 strong").addClass("form-required");
          }
        }
      });
    }
  };

})(jQuery, Drupal);
