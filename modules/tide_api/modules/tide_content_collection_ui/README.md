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

It can also be tested outside Drupal for preliminary testing by running `npm run dev` from the app directory. Make sure you have a `.env.development` file within the app directory with the following: `VITE_API_URL=http://content-sdp.docker.internal/`. Important note; do not use `.env` for this file it must be `.env.development` so it doesn't interfere with the prod build.
