<?php

namespace Drupal\tide_publication\EventSubscriber;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableResponseInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class Response for EventSubscriber.
 *
 * @package Drupal\tide_publication\EventSubscriber
 */
class Response implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Run after JSON API ResourceResponseSubscriber (priority 128) and
    // before DynamicPageCacheSubscriber (priority 100).
    $events = [];
    $events[KernelEvents::RESPONSE][] = [
      'addPublicationNavigationCacheContext',
      127,
    ];

    return $events;
  }

  /**
   * Add Publication Nav cache context and tags of JSON API response.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The event object.
   */
  public function addPublicationNavigationCacheContext(ResponseEvent $event) {
    $request = $event->getRequest();

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $request->attributes->get('entity');
    if ($entity && in_array($entity->bundle(), [
      'publication',
      'publication_page',
    ])) {
      $response = $event->getResponse();
      if (!$response instanceof CacheableResponseInterface) {
        return;
      }

      $fields = [
        'field_publication',
        'publication_children',
        'publication_navigation_root',
        'publication_navigation_prev',
        'publication_navigation_next',
      ];

      $publication_tags = [];
      foreach ($fields as $field_name) {
        if ($entity->hasField($field_name) && !$entity->get($field_name)->isEmpty()) {
          $field_values = $entity->get($field_name)->getValue();
          foreach ($field_values as $value) {
            $publication_tags[] = 'node:' . $value['target_id'];
          }
        }
      }

      if (!empty($publication_tags)) {
        $cache_tags = $response->getCacheableMetadata()->getCacheTags();
        $cache_tags = Cache::mergeTags($cache_tags, $publication_tags);
        $response->getCacheableMetadata()->setCacheTags($cache_tags);
      }
    }
  }

}
