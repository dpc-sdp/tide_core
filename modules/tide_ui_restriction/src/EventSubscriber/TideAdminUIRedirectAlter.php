<?php

namespace Drupal\tide_ui_restriction\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Provides facility to alter url reidrect for admin ui.
 *
 * @package Drupal\tide_ui_restriction
 */
class TideAdminUIRedirectAlter implements EventSubscriberInterface {

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[KernelEvents::REQUEST][] = ['onAdminModules'];
    return $events;
  }

  /**
   * Redirects modules management page to 404.
   */
  public function onAdminModules(GetResponseEvent $event) {
    $uri = $event->getRequest()->getRequestUri();
    if ($uri == '/admin/modules' || $uri == '/admin/modules/uninstall') {
      $response = new Response();
      $response->setContent('Page not found');
      $response->setStatusCode(404);
      $response->headers->set('Status', 'Page not found');
      $response->send();
    }
  }

}
