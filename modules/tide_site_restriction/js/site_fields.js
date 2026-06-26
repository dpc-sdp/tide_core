/**
 * @file
 * Couples the Primary Site and Site node fields.
 *
 */

(function (Drupal, drupalSettings, once) {

  'use strict';

  Drupal.behaviors.tideSiteRestrictionSiteFields = {
    attach: function (context) {
      var settings = drupalSettings.tideSiteRestriction;
      if (!settings || !settings.fields || !settings.fields.primary || !settings.fields.site) {
        return;
      }

      var primaryName = settings.fields.primary;
      var siteName = settings.fields.site;

      // Bind once per radio group; bail out if the Primary Site field is not on
      // this page (e.g. the widget is rendered on a non-node form).
      var primaryInputs = once('tide-site-restriction', 'input[name="' + primaryName + '"]', context);
      if (!primaryInputs.length) {
        return;
      }

      // Guards re-entrancy when the script toggles checkboxes programmatically.
      var suppress = false;

      function siteCheckboxes() {
        return Array.prototype.slice.call(
          document.querySelectorAll('input[name^="' + siteName + '["]')
        );
      }

      function setSite(tid, checked) {
        var cb = document.querySelector('input[name="' + siteName + '[' + tid + ']"]');
        if (cb && cb.checked !== checked) {
          cb.checked = checked;
          cb.dispatchEvent(new Event('change', { bubbles: true }));
        }
      }

      function getCheckedPrimary() {
        var checked = document.querySelector('input[name="' + primaryName + '"]:checked');
        return checked ? checked.value : null;
      }

      /**
       * Rebuilds the Site selection for the given Primary Site.
       */
      function applyForPrimary(primaryTid) {
        primaryTid = String(primaryTid);
        suppress = true;
        // Clear every existing Site selection
        siteCheckboxes().forEach(function (cb) {
          if (cb.checked) {
            cb.checked = false;
            cb.dispatchEvent(new Event('change', { bubbles: true }));
          }
        });
        suppress = false;

        // Auto-check the Primary Site itself, the editor may add a single child.
        setSite(primaryTid, true);
      }

      /**
       * Keeps the Site selection at "Primary Site + at most one child".
       */
      function enforceSiteSelection(event) {
        if (suppress) {
          return;
        }
        var primaryTid = getCheckedPrimary();
        if (!primaryTid) {
          return;
        }
        var cb = event.target;

        // The Primary Site must always stay selected.
        if (cb.value === String(primaryTid)) {
          if (!cb.checked) {
            cb.checked = true;
          }
          return;
        }

        // Selecting a child replaces any other (non-primary) selection.
        if (cb.checked) {
          suppress = true;
          siteCheckboxes().forEach(function (other) {
            if (other !== cb && other.checked && other.value !== String(primaryTid)) {
              other.checked = false;
              other.dispatchEvent(new Event('change', { bubbles: true }));
            }
          });
          suppress = false;
        }
      }

      primaryInputs.forEach(function (input) {
        input.addEventListener('change', function (event) {
          if (event.target.checked) {
            applyForPrimary(event.target.value);
          }
        });
      });

      siteCheckboxes().forEach(function (cb) {
        cb.addEventListener('change', enforceSiteSelection);
      });

      // Initial state
      if (settings.isNew && primaryInputs.length === 1) {
        var current = getCheckedPrimary();
        if (!current) {
          primaryInputs[0].checked = true;
          current = primaryInputs[0].value;
        }
        if (current) {
          applyForPrimary(current);
        }
      }
    }
  };

})(Drupal, drupalSettings, once);
