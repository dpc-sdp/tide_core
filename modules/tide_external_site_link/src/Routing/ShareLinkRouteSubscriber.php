<?php

namespace Drupal\tide_external_site_link\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Alters Tide Share Link routes for selected node bundles.
 */
class ShareLinkRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $routes = [
      'share_link_token.share_node',
      'share_link_token.share_node_revision',
      'entity.share_link_token.node_collection',
      'entity.share_link_token.revision_collection',
    ];

    foreach ($routes as $route_name) {
      if ($route = $collection->get($route_name)) {
        $route->setRequirement('_share_link_access_check_for_external_site_link', 'TRUE');
      }
    }
  }

}
