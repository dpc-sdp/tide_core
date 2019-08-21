<?php

namespace Drupal\tide_core\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class TideCoreRouteAlter.
 *
 * @package Drupal\tide_core
 */
class TideCoreRouteAlter extends RouteSubscriberBase {

  /**
   * Alter scheduled_transitions route and path.
   *
   * {@inheritDoc}.
   */
  protected function alterRoutes(RouteCollection $collection) {
    $route = $collection->get('entity.node.scheduled_transitions');
    if ($route) {
      $route->setDefault('_title', 'Scheduled updates');
    }
    $route = $collection->get('entity.node.scheduled_transition_add');
    if ($route) {
      $route->setDefault('_title', 'Add Scheduled update');
    }
  }

}
