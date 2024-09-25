/**
 * @file
 * Adds the back button for embeded media select modal in CKEditor.
 * Everytime the user goes to different page within the media embed select modal
 * it will save the filter values to retrive the search result when user
 * comes back to the first page.
 */

 (function ($, Drupal) {
  'use strict';

  Drupal.behaviors.tide_media_browser_iframe = {
    attach: function (context, settings) {
      if (!window.CKEditor5) {
        return;
      }
      let iframePath, itemName, licenseType, mediaType, status, site
      const iframeName = 'entity_browser_iframe_tide_media_browser_iframe'
      const homePagePath = '/entity-browser/iframe/tide_media_browser_iframe'
      const backButton = $('#entity-embed-dialog-tide-media-browser-home')
      $(document).ready(function () {
        // Remove the filter values on modal first load if any.
        localStorage.removeItem('tideMediaBrowserNameFilterVal');
        localStorage.removeItem('tideMediaBrowserLicenseTypeFilterVal');
        localStorage.removeItem('tideMediaBrowserMediaTypeFilterVal');
        localStorage.removeItem('tideMediaBrowserStatusFilterVal');
        localStorage.removeItem('tideMediaBrowserSiteFilterVal');
        $('iframe').on('load', function() {
          if ($('iframe') && $('iframe').length > 0) {
            for (let i = 0; i < $('iframe').length; i++) {
              if ($('iframe')[i].name && $('iframe')[i].name === iframeName) {
                iframePath = $('iframe')[i].contentWindow.location.pathname
                // Shows the back button when iframe is not in the first page.
                if (iframePath && iframePath !== homePagePath) {
                  backButton.css('display', 'block');
                }
                // Hides the back button for the first page.
                if (iframePath && iframePath === homePagePath) {
                  backButton.css('display', 'none');
                }
              }
            }
          }
        });
      });
      // Disable back button when it is on the first page.
      backButton.click(function() {
        history.back()
        $('iframe').on('load', function() {
          // Get the filter values on iframe load.
          itemName = localStorage.getItem('tideMediaBrowserNameFilterVal')
          $(this).contents().find('input[name=name]').val(itemName)
          licenseType = checkFilterValueInStorage('tideMediaBrowserLicenseTypeFilterVal')
          $(this).contents().find('select[name=field_license_type_target_id_1]').val(licenseType)
          mediaType = checkFilterValueInStorage('tideMediaBrowserMediaTypeFilterVal')
          $(this).contents().find('select[name=bundle]').val(mediaType)
          status = checkFilterValueInStorage('tideMediaBrowserStatusFilterVal')
          $(this).contents().find('select[name=status]').val(status)
          site = checkFilterValueInStorage('tideMediaBrowserSiteFilterVal')
          $(this).contents().find('select[name=field_media_site_target_id]').val(site)
          // Trigger ajax call submit to retrive the user search for current session.
          // Will only trigger if there is any filter value set for this session.
          if (checkFilterValuesOnLoad (itemName, licenseType, mediaType, status, site)) {
            $(this).contents().find('input[value=Apply]').trigger('click');
          }
        })
      });
      // Check filter values on load.
      function checkFilterValuesOnLoad (itemName, licenseType, mediaType, status, site) {
        const defaultValue = "All"
        if ((itemName && itemName !== 'undefined') ||
          (licenseType && licenseType !== defaultValue) || 
          (mediaType && mediaType !== defaultValue) || 
          (status && status !== defaultValue) || 
          (site && site !== defaultValue)) {
            return true
        }
        return false
      }
      // Value check on localStorage, if empty set to default.
      function checkFilterValueInStorage (filterValName) {
        if (localStorage.getItem(filterValName)) {
          return localStorage.getItem(filterValName)
        }
        return 'All'
      }
    },
  };
}(jQuery, Drupal));
