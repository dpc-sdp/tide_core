#!/usr/bin/env bash
##
# Run tests in CI.
#
set -eu -o pipefail

echo "==> Lint code"
ddev exec vendor/bin/phpcs .

echo "==> Run Behat tests"
mkdir -p /tmp/artifacts/behat
ddev exec vendor/bin/behat --strict --colors --tags='~@skipped' tests
ddev drush en -y tide_webform
ddev exec vendor/bin/behat --strict --colors --tags='~@skipped' modules/tide_webform
ddev drush en -y tide_media
ddev drush en -y tide_media_secure_files
ddev drush en -y tide_media_file_overwrite
ddev exec vendor/bin/behat --strict --colors --tags='~@skipped' modules/tide_media
ddev drush en -y tide_event
ddev exec vendor/bin/behat --strict --colors --tags='~@skipped' modules/tide_event
ddev drush en -y tide_grant
ddev exec vendor/bin/behat --strict --colors --tags='~@skipped' modules/tide_grant
ddev drush en -y tide_news
ddev exec vendor/bin/behat --strict --colors --tags='~@skipped' modules/tide_news
ddev drush en -y tide_landing_page
ddev exec vendor/bin/behat --strict --colors --tags='~@skipped' modules/tide_landing_page
ddev drush en -y tide_site
ddev exec vendor/bin/behat --strict --colors modules/tide_site
ddev drush en -y tide_ui_restriction
ddev exec vendor/bin/behat --strict --colors modules/tide_ui_restriction
ddev drush en -y tide_api
ddev drush en -y tide_content_collection_ui
ddev drush en -y tide_share_link
ddev exec vendor/bin/behat --strict --colors --tags='~@skipped' modules/tide_api
ddev drush en -y tide_external_site_link
ddev exec vendor/bin/behat --strict --colors --tags='~@skipped' modules/tide_external_site_link
