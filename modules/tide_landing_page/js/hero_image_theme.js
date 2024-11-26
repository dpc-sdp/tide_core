(function (Drupal) {
  "use strict";

  Drupal.behaviors.heroImageTheme = {
    attach: function (context, settings) {
      const imageTheme = context.querySelector(
        'select[data-drupal-selector^="edit-field-landing-page-hero-theme"]'
      );

      if (imageTheme !== null) {
        setImageTheme();

        const headerStyles = context.querySelectorAll(
          'input[data-drupal-selector^="edit-header-style-options"]'
        );

        if (headerStyles.length > 0) {
          headerStyles.forEach((style) => {
            style.addEventListener("change", setImageTheme);
          });
        }
      }

      function setImageTheme() {
        const selectedHeaderStyle = document.querySelector(
          'input[data-drupal-selector^="edit-header-style-options"]:checked'
        );

        if (!selectedHeaderStyle) {
          return; // Exit if no style option is selected.
        }

        let defaultHeaderStyle = selectedHeaderStyle.value;
        imageTheme.disabled = defaultHeaderStyle === "corner" ? false : true;
        imageTheme.value =
          defaultHeaderStyle === "fullwidth" ? "dark" : "light";
      }
    },
  };
})(Drupal);
