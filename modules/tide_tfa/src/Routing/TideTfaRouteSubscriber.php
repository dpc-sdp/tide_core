<?php

namespace Drupal\tide_tfa\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 *
 * Class TideTfaRouteSubscriber.
 *
 * @package Drupal\tide_tfa\Routing
 */
class TideTfaRouteSubscriber extends RouteSubscriberBase {

  /**
   * Alters existing routes for TFA user password reset login.
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   Route collection to be altered.
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Override the user reset pass login route to use TideTfaUserController.
    if ($route = $collection->get('user.reset.login')) {
      $route->setDefault('_controller', '\Drupal\tide_tfa\Controller\TideTfaUserController::doResetPassLogin');
    }
    // TFA overview page (User → Security → TFA).
    if ($route = $collection->get('tfa.overview')) {
      $route->setDefault('_title', 'Multi-factor authentication');
      $route->setDefault('_form', '\Drupal\tide_tfa\Form\TideTfaOverviewForm');
    }
  }

}
