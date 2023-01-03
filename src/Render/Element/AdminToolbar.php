<?php

namespace Drupal\tide_core\Render\Element;

use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Define an admin toolbar.
 *
 * @package Drupal\tide_core\Render\Element
 */
class AdminToolbar implements TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['preRenderTray'];
  }

  /**
   * Renders the toolbar's administration tray.
   *
   * This is a clone of admin_toolbar's
   * admin_toolbar_prerender_toolbar_administration_tray() function, which uses
   * setMaxDepth(5) instead of setMaxDepth(4).
   *
   * @param array $build
   *   A renderable array.
   *
   * @return array
   *   The updated renderable array.
   *
   * @see toolbar_prerender_toolbar_administration_tray()
   * @see admin_toolbar_prerender_toolbar_administration_tray()
   */
  public static function preRenderTray(array $build) {
    $element = [];
    $menu_tree = \Drupal::service('toolbar.menu_tree');
    $parameters = new MenuTreeParameters();
    $parameters->setRoot('system.admin')->excludeRoot()->setMaxDepth(5)->onlyEnabledLinks();
    $tree = $menu_tree->load(NULL, $parameters);
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
      ['callable' => 'tide_core_menu_navigation_links'],
    ];
    $tree = $menu_tree->transform($tree, $manipulators);
    $element['administration_menu'] = $menu_tree->build($tree);

    return $element;
  }

}
