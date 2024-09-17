/**
 * @file
 * Content Rating webform.
 */

(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.tide_core_content_rating = {
    attach: function (context) {
      $('#webform-content-rating-cancel').on('click', function (e) {
        var form = $(this).parents('form:first');
        form.trigger('reset');
        form.find('input[name="was_this_page_helpful"]')
          .prop('checked', false)
          .trigger('blur', ['webform.states'])
          .trigger('change', ['webform.states']);
        e.preventDefault();
      });
    }
  };
}(jQuery, Drupal));
