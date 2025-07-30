#!/usr/bin/env bash
##
# Run tests in CI.
#
set -e

echo "==> Lint code"
ahoy lint

echo "==> Run Behat tests"
mkdir -p /tmp/artifacts/behat
ahoy cli "./vendor/bin/behat --strict --colors --tags="~@skipped" tests"
ahoy cli "drush en tide_api -y"
ahoy cli "./vendor/bin/behat --strict --colors --tags="~@skipped" modules/tide_api"
ahoy cli "drush en tide_webform -y"
ahoy cli "./vendor/bin/behat --strict --colors --tags="~@skipped" modules/tide_webform"
ahoy cli "drush en tide_media -y"
ahoy cli "./vendor/bin/behat --strict --colors --tags="~@skipped" modules/tide_media"
ahoy cli "drush en tide_event -y"
ahoy cli "./vendor/bin/behat --strict --colors --tags="~@skipped" modules/tide_event"
ahoy cli "drush en tide_grant -y"
ahoy cli "./vendor/bin/behat --strict --colors --tags="~@skipped" modules/tide_grant"
ahoy cli "drush en tide_news -y"
ahoy cli "./vendor/bin/behat --strict --colors --tags="~@skipped" modules/tide_news"
ahoy cli "drush en tide_landing_page -y"
ahoy cli "./vendor/bin/behat --strict --colors --tags="~@skipped" modules/tide_landing_page"
ahoy cli "drush en tide_site -y"
ahoy cli "./vendor/bin/behat --strict --colors modules/tide_site"
ahoy cli "drush en tide_ui_restriction -y"
ahoy cli "./vendor/bin/behat --strict --colors modules/tide_ui_restriction"

