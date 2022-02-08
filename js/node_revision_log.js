/**
 * @file
 * Add custom function to node revision log message in node edit form and content moderation.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Add revision log message required fields.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches nodeRevisionLog behaviors.
   */
  Drupal.behaviors.nodeRevisionLog = {
    attach: function (context, settings) {
      // Show red star after the lable if field is require.
      var requiredLog = ['Needs Review', 'Archive pending'];

      if (requiredLog.includes($('#edit-new-state option:selected').text())) {
        $('.form-item-revision-log label').addClass('form-required');
      }
      $('select#edit-new-state').change(function(){
        if (requiredLog.includes($('#edit-new-state option:selected').text())) {
          $('.form-item-revision-log label').addClass('form-required');
        }
        else {
          $('.form-item-revision-log label').removeClass('form-required');
        }
      });

      if (requiredLog.includes($('#edit-moderation-state-0-state option:selected').text())) {
        $('.form-item-comment-log-message label').addClass('form-required');
      }
      $('select#edit-moderation-state-0-state').change(function(){
        if (requiredLog.includes($('#edit-moderation-state-0-state option:selected').text())) {
          $('.form-item-comment-log-message label').addClass('form-required');
        }
        else {
          $('.form-item-comment-log-message label').removeClass('form-required');
        }
      });

      // Copy comment log message to revision log.
      $('.form-item-comment-log-message textarea').blur(function(e){
        $('.field--name-revision-log textarea').val(e.target.value);
      });

      $('.field--name-revision-log textarea').blur(function(e){
        $('.form-item-comment-log-message textarea').val(e.target.value);
      });

    }
  };

})(jQuery, Drupal);
