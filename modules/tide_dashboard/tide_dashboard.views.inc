<?php

/**
 * @file
 * Provides views data.
 */

use Drupal\tide_dashboard\Hook\TideDashboardHooks;

/**
 * Implements hook_views_data().
 */
#[\Drupal\Core\Hook\Attribute\LegacyHook]
function tide_dashboard_views_data() {
  return \Drupal::service(TideDashboardHooks::class)->viewsData();
}
