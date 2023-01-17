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
        if (dialogName == 'iframe') {
          var info = dialogDefinition.getContents( 'info' );
          // Get the title field.
          var iframeTitle = info.get( 'title' );
          // Remove it from the element array.
          info.remove( 'title' );
          iframeTitle['label'] = 'Title';
          iframeTitle['required'] = true;
          iframeTitle['validate'] = CKEDITOR.dialog.validate.notEmpty( 'Please type the iframe title' )
          // Assign the title field at the beginning of the array.
          info['elements'].unshift(iframeTitle);
        }
      });
    }
    
  };

})(jQuery, Drupal);
