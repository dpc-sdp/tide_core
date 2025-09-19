# Tide content collection UI

Module that provides a custom field type for integrating the content collection UI app.

## App

### Updating/building the app

To update and build the Vue app, update the version of `@dpc-sdp/tide-search-ui` in `app/package.json` and run `npm install && npm run build` from the app directory.

### Testing the app

The app can be tested within Drupal by adding the landing page component to a page. It can also be tested outside Drupal for preliminary testing by running `npm run dev` from the app directory. Make sure you have a `.env` file within the app directory with the following: `VITE_API_URL=http://content-sdp.docker.internal/`
