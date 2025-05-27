<?php

namespace Drupal\tide_ui_restriction\Routing;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Route Subscriber.
 *
 * @package Drupal\tide_ui_restriction\Routing\RouteSubscriber
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * RouteSubscriber constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) : void {
    $customPermissions = [
      '/admin/people/create' => 'access user creation page',
      '/admin/people/roles' => 'access role management page',
      '/admin/people/permissions' => 'access permissions management page',
    ];
    foreach ($collection as $route) {
      $path = $route->getPath();
      // Set _admin_config_access_check.
      // For all routes starting with admin/config.
      if (strpos($path, '/admin/config/') === 0 && !$this->isExcluded($path)) {
        $route->setRequirement('_admin_config_access_check', 'TRUE');
      }
      // Restricts admin routes with custom permissions.
      foreach ($customPermissions as $routePath => $permission) {
        if (strpos($path, $routePath) === 0) {
          $route->setRequirement('_permission', $permission);
        }
      }
    }
  }

  /**
   * Check if a path is excluded.
   *
   * @param string $path
   *   The path to check.
   *
   * @return bool
   *   TRUE if the path is excluded.
   */
  protected function isExcluded($path) : bool {
    $returns = $this->moduleHandler->invokeAll('admin_config_access_check_exclude', [$path]);

    return !empty(array_filter($returns));
  }

}
