<?php

namespace Drupal\tide_dashboard\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Route Subscriber.
 *
 * @package Drupal\tide_dashboard\Routing
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Change the title of Workbench main page.
    if ($route = $collection->get('workbench.content')) {
      $route->setDefault('_title_callback', '_tide_dashboard_workbench_content_title_callback');
    }
  }

}
