<?php

namespace Drupal\tide_landing_page\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('block.admin_display')) {
      $route->setRequirement('_custom_access', 'Drupal\tide_landing_page\Access\BlockAccessCheck::access');
    }

  }

}
