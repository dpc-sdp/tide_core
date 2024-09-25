<?php

namespace Drupal\tide_api\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class TideApi Version Subscriber.
 *
 * @package Drupal\tide_api\EventSubscriber.
 */
class TideApiVersionSubscriber implements EventSubscriberInterface {

  /**
   * Defines current API version.
   */
  const API_VERSION = '1.0';

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::RESPONSE => [['setApiVersion', -10]],
    ];
  }

  /**
   * Add API version to the index() endpoint.
   *
   * Note that this is not JSONAPI spec version (such version added to each
   * response automatically).
   *
   * @see \Drupal\jsonapi\Controller\EntryPoint::index()
   * @see http://jsonapi.org/format/
   */
  public function setApiVersion(ResponseEvent $event) {
    if ($event->getRequest()->getRequestUri() == '/api/v1') {
      $response = $event->getResponse();
      $content = json_decode($response->getContent());
      $content->api_version = self::API_VERSION;
      $response->setContent(json_encode($content));
      $event->setResponse($response);
    }
  }

}
