<?php

namespace Drupal\tide_publication\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class Route for EventSubscriber.
 *
 * @package Drupal\tide_publication\EventSubscriber
 */
class Route extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    // Force Entity Hierarchy Children form to load the published revision
    // instead of the latest one. It will allows the Children tab to show all
    // children when the parent node has an unpublished revision.
    $route = $collection->get('entity.node.entity_hierarchy_reorder');
    if ($route) {
      $parameters = $route->getOption('parameters');
      if (!empty($parameters['node'])) {
        $parameters['node']['load_latest_revision'] = FALSE;
        $route->setOption('parameters', $parameters);
      }
    }
  }

}
