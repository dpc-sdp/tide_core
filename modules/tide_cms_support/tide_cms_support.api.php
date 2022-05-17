<?php

/**
 * @file
 * Hooks for the Tide CMS Support system.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Provide online user help.
 *
 * By implementing hook_tide_help(), a module can make documentation available
 * to CMS users for specific pages.
 *
 * The page-specific help information provided by this hook appears in the
 * Tide CMS Help block (provided by the core Help module), if the block is
 * displayed on that page.
 *
 * For detailed usage examples of:
 * - Page-specific help using only routes, see book_help().
 * - Page-specific help using routes and $request, see block_help().
 *
 * @param string $route_name
 *   For page-specific help, use the route name as identified in the
 *   module's routing.yml file.
 * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
 *   The current route match. This can be used to generate different help
 *   output for different pages that share the same route.
 *
 * @return string|array
 *   A render array, localized string, or object that can be rendered into
 *   a string, containing the help text.
 */
function hook_tide_help(string $route_name, \Drupal\Core\Routing\RouteMatchInterface $route_match) {
  switch ($route_name) {
    // Help for a path in the block module.
    case 'block.admin_display':
      return '<p>' . t('This page provides a drag-and-drop interface for assigning a block to a region, and for controlling the order of blocks within regions. Since not all themes implement the same regions, or display regions in the same way, blocks are positioned on a per-theme basis. Remember that your changes will not be saved until you click the <em>Save blocks</em> button at the bottom of the page.') . '</p>';
  }
}

/**
 * @} End of "addtogroup hooks".
 */
