<?php

/**
 * @file
 * Tide UI Restriction Hooks.
 */

/**
 * Check if a path should be excluded from _admin_config_access_check.
 *
 * Generally all paths starting with /admin/config will have an additional
 * access check to ensure the current user has the 'access administration pages'
 * permission, regardless the existing access checks of the paths.
 *
 * This hook allows modules to exclude certain paths from the additional access
 * check _admin_config_access_check.
 *
 * @param string $path
 *   The path to check.
 *
 * @return bool
 *   TRUE if the path should be excluded from the access check.
 *
 * @see \Drupal\tide_ui_restriction\Routing\RouteSubscriber::isExcluded()
 */
function hook_admin_config_access_check_exclude($path) : bool {
  return $path === '/admin/config/search/path';
}
