<?php

namespace Drupal\tide_core\EventSubscriber;

use Drupal\tide_core\TideCoreFast404;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Drupal\Core\Site\Settings;
use Drupal\fast404\EventSubscriber\Fast404EventSubscriber;

/**
 * Class TideCoreFast404EventSubscriber.
 *
 * @package Drupal\tide_core\EventSubscriber
 */
class TideCoreFast404EventSubscriber extends Fast404EventSubscriber {

  /**
   * Ensures Tide Core Fast 404 output returned if applicable.
   */
  public function tideOnKernelRequest(GetResponseEvent $event) {
    $request = $this->requestStack->getCurrentRequest();
    $fast_404 = new TideCoreFast404($request);

    $fast_404->extensionCheck();
    if ($fast_404->isPathBlocked()) {
      $event->setResponse($fast_404->response(TRUE));
    }

    $fast_404->pathCheck();
    if ($fast_404->isPathBlocked()) {
      $event->setResponse($fast_404->response(TRUE));
    }
  }

  /**
   * Ensures Fast 404 output returned upon NotFoundHttpException.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The response for exception event.
   */
  public function tideOnNotFoundException(GetResponseForExceptionEvent $event) {
    // Check to see if we will completely replace the Drupal 404 page.
    if (Settings::get('fast404_not_found_exception', FALSE)) {
      if ($event->getException() instanceof NotFoundHttpException) {
        $fast_404 = new TideCoreFast404($event->getRequest());
        $event->setResponse($fast_404->response(TRUE));
      }
    }
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['tideOnKernelRequest', 101];
    $events[KernelEvents::EXCEPTION][] = ['tideOnNotFoundException', 1];
    return $events;
  }

}
