(function ($, Drupal) {

    'use strict';
  
    Drupal.behaviors.siteQuickExitFields = {
      attach: function (context, settings) {
        var parent = ($('.taxonomy-term-sites-form  #edit-parent'));
        var parentVal = parent.val();
        if (parentVal == 0) {
          $('.field--name-field-show-exit-site-specific').hide();
        }
        else {
          $('.field--name-field-site-show-exit-site').hide();
        }
        $(parent).change(function() {
          var selectedValue = $(this).val();
          if (selectedValue == 0) {
            $('#edit-field-show-exit-site-specific option:selected').prop('selected', false);
            $('#edit-field-show-exit-site-specific').val('_none');
            $('.field--name-field-show-exit-site-specific').hide();
            $('.field--name-field-site-show-exit-site').show();
          }
          else {
            $('#edit-field-site-show-exit-site-value').prop('checked', false);
            $('.field--name-field-site-show-exit-site').hide();
            $('.field--name-field-show-exit-site-specific').show();
          }
      });
      }
    };
  })(jQuery, Drupal);