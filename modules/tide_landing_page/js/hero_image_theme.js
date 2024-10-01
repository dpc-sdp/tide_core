(function (Drupal) {
  "use strict";

  Drupal.behaviors.heroImageTheme = {
    attach: function (context, settings) {
      const imageTheme = document.querySelector(
        'select[data-drupal-selector^="edit-field-landing-page-hero-theme"]'
      );

      if (imageTheme !== null) {
        setImageTheme();

        const headerStyles = document.querySelectorAll(
          'input[data-drupal-selector^="edit-header-style-options"]'
        );

        if (headerStyles !== null) {
          headerStyles.forEach((style) => {
            style.addEventListener("change", () => {
              setImageTheme();
            });
          });
        }
      }

      function setImageTheme() {
        let defaultHeaderStyle = document.querySelector(
          'input[data-drupal-selector^="edit-header-style-options"]:checked'
        ).value;

        if (defaultHeaderStyle === "fullwidth") {
          imageTheme.value = "dark";
        } else {
          imageTheme.value = "light";
        }
      }
    },
  };
})(Drupal);
