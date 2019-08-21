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
      $route->setPath('/node/{node}/scheduled-publishing');
      $route->setDefault('_title', 'Scheduled publishing');
      $collection->add('entity.node.scheduled_transitions', $route);
    }
    $route = $collection->get('entity.node.scheduled_transition_add');
    if ($route) {
      $route->setDefault('_title', 'Add Scheduled publishing');
      $route->setPath('/node/{node}/scheduled-publishing/add');
      $collection->add('entity.node.scheduled_transition_add', $route);
    }
    $route = $collection->get('entity.scheduled_transition.delete_form');
    if ($route) {
      $route->setPath('/admin/scheduled-publishing/{scheduled_transition}/delete');
      $collection->add('entity.scheduled_transition.delete_form', $route);
    }
  }

}
