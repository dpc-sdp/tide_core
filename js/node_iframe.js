/**
 * @file
 * Update iframe window in CKEditor to enable title field and put it at the top.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Update iframe widnow.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches CKEditor iframe behaviors.
   */
  Drupal.behaviors.nodeIFrame = {
    attach: function (context, settings) {
      if (!window.CKEDITOR) {
        return;
      }
      CKEDITOR.on( 'dialogDefinition', function( ev ) {
        // Take the dialog name and its definition from the event data.
        var dialogName = ev.data.name;
        var dialogDefinition = ev.data.definition;
        console.log(dialogName);
        if (dialogName == 'iframe') {
          var infoTab = dialogDefinition.getContents( 'info' );
          var name = infoTab.get( 'title' );
          infoTab.remove( 'title' );
          name['label'] = 'Title';
          infoTab['elements'].unshift(name);
        }
      });
    }
    
  };

})(jQuery, Drupal);
