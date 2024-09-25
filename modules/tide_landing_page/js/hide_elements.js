(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.hideParagraphsElements = {
    attach: function (context, settings) {
      $(context).find("table[id^=field-paragraph-keydates-values--] h4").hide()
      $(context).find("div[id^=edit-field-landing-page-component-] fieldset legend span").each(function () {
        if ($(this).text() === "CTA") {
          $(this).hide();
        }
      })
    }
  };
})(jQuery, Drupal);
