<?php

namespace Drupal\tide_core\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides an Custom Autocomplete implementation for RouteSubscriber.
 */
class AutocompleteRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('system.entity_autocomplete')) {
      $route->setDefault('_controller', '\Drupal\tide_core\Controller\TideCoreEntityAutocompleteController::handleAutocomplete');
    }
  }

}
