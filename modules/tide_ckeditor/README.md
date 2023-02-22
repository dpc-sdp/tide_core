# Tide ckeditor
tide_ckeditor is a submodule of tide_core, it contains ckeditor functions, 
plugins that provided by tide project. 

## Development process
 - Install [node](https://nodejs.org/en/) running environment.
 - `cd` to `tide_ckeditor` directory
 - `npm run build`
 - If you want webpack to monitor your changes constantly, try `npm run watch`
 - More reading before working on the development of CKEDITOR for Drupal
   - [Drupal CKEditor 5 API overview](https://www.drupal.org/docs/drupal-apis/ckeditor-5-api/overview)
   - [CKEDITOR5 API](https://ckeditor.com/docs/ckeditor5/latest/api/index.html)
## Usage
 - Enable `tide_ckeditor`
   - `drush en tide_ckeditor`
 - The module ONLY support ckeditor5.
