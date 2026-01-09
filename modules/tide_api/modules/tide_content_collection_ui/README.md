# Tide Content Collection UI

Module that provides a custom field type for integrating the content collection UI app.

## Structure
- app: this is the JS application
- config: this is the Drupal configuration
- src: the Drupal module code
  - plugin: code related to the field
  - controller: code for the node autocomplete search

### App

### Updating/building the app

To update and build the Vue app, update the version of `@dpc-sdp/tide-content-collection-ui` in `app/package.json` and run `npm install && npm run build` from the app directory.
Note: this workflow may change in the future, but for now it works the same as the other parts of Tide needing a JS build set. For example `webpack` is used to build a JS bundle for `tide_ckeditor`.

### Testing the app

The app can be tested within Drupal by adding the landing page component to a page.
