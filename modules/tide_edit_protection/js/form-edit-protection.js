/**
 * @file
 * Stops page from changing when user is posting.
 */

(function($, Drupal, drupalSettings) {
  // Allow Submit/Edit button.
  var click = false;
  // Dirty form flag.
  var edit = false;

  Drupal.behaviors.formEditProtection = {
    attach : function(context) {
      for (var key in drupalSettings.tide_edit_protection.forms) {
        // If they leave an input field, assume they changed it.
        $('#' + key + ' :input').blur(function() {
          edit = true;
        });
        $('#' + key + ' input:checkbox, #' + key + ' input:radio').click(function() {
          $(this).trigger('blur');
        });

        // Let all form submit buttons through.
        $('#' + key + " .form-submit").each(function() {
          $(this).click(function() {
            click = true;
          }).addClass('form-edit-protection-processed');
        });
      }

      // Catch all links and buttons EXCEPT for "#" links.
      $('a').click(function() {
        if (edit) {
          if ('edit-delete' == this.id || 'edit-cancel' == this.id && '#' == $(this).attr('href').substring(0, 1)) {
            edit = false;
          }
        }
      });

      // Handle backbutton, exit etc.
      window.onbeforeunload = function() {
        // Add CKEditor support.
        if (typeof (CKEDITOR) != 'undefined' && typeof (CKEDITOR.instances) != 'undefined') {
          for (var i in CKEDITOR.instances) {
            if (CKEDITOR.instances[i].checkDirty()) {
              edit = true;
              break;
            }
          }
        }
        if (edit && !click) {
          click = false;
          return (Drupal.t("You will lose all unsaved work."));
        }
      }
    }
  };
})(jQuery, Drupal, drupalSettings);
