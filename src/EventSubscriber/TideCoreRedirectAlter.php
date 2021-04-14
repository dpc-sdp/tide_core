<?php

namespace Drupal\tide_core\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class TideCoreRedirectAlter.
 *
 * @package Drupal\tide_core
 */
class TideCoreRedirectAlter implements EventSubscriberInterface {

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents() {
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
