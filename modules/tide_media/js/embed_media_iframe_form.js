/**
 * @file
 * Retrieve the name user input for embed media select iframe.
 * Everytime the user goes to different page within the media embed select modal
 * it will save the name field value to retrive the search result when user
 * comes back to the first page.
 */

 (function ($, Drupal) {
  'use strict';

  Drupal.behaviors.tide_media_media_form = {
    attach: function (context, settings) {
      const nameField = $('#views-exposed-form-tide-media-browser-media-browser input[name=name]')
      const licenseType = $('#views-exposed-form-tide-media-browser-media-browser select[name=field_license_type_target_id_1]')
      const mediaType = $('#views-exposed-form-tide-media-browser-media-browser select[name=bundle]')
      const status = $('#views-exposed-form-tide-media-browser-media-browser select[name=status]')
      const site = $('#views-exposed-form-tide-media-browser-media-browser select[name=field_media_site_target_id]')
      const formSubmit = $('#views-exposed-form-tide-media-browser-media-browser input[type=submit]')
      // On every submit it will save the namefield value to the localstorage.
      formSubmit.click(function () {
        // Store user set values on every click.
        localStorage.setItem('tideMediaBrowserNameFilterVal', nameField.val());
        localStorage.setItem('tideMediaBrowserLicenseTypeFilterVal', licenseType.val());
        localStorage.setItem('tideMediaBrowserMediaTypeFilterVal', mediaType.val());
        localStorage.setItem('tideMediaBrowserStatusFilterVal', status.val());
        localStorage.setItem('tideMediaBrowserSiteFilterVal', site.val());
      })
    }
  };
}(jQuery, Drupal));
