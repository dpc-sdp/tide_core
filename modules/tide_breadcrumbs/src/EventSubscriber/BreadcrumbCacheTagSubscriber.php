<?php

namespace Drupal\tide_breadcrumbs\EventSubscriber;

use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\jsonapi\ResourceResponse;
use Drupal\node\NodeInterface;
use Drupal\tide_breadcrumbs\TideBreadcrumbBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class BreadcrumbCacheTagSubscriber implements EventSubscriberInterface {

  protected $breadcrumbBuilder;

  public function __construct(TideBreadcrumbBuilder $breadcrumb_builder) {
    $this->breadcrumbBuilder = $breadcrumb_builder;
  }

  public static function getSubscribedEvents() {
    // Run after JSON:API has built the response but before it's sent.
    $events[KernelEvents::RESPONSE][] = ['onResponse', 0];
    return $events;
  }

  public function onResponse(ResponseEvent $event) {
    $response = $event->getResponse();

    // Use the same interface check as your working site filter.
    if (!$response instanceof CacheableResponseInterface) {
      return;
    }

    // If this is a JSON:API response, we need to find the node inside.
    if ($response instanceof ResourceResponse) {
      $data = $response->getResponseData();
      $entities = $data->getData();
      $items = is_array($entities) ? $entities : [$entities];

      foreach ($items as $item) {
        $entity = ($item instanceof ResourceObject) 
          ? $item->getEntity() 
          : $item;

        if ($entity instanceof NodeInterface) {
          $tags = $this->breadcrumbBuilder->getCacheTags($entity);
          
          // Use the same method as your site filter.
          $metadata = $response->getCacheableMetadata();
          $metadata->addCacheTags($tags);
          // Note: You don't always need setCacheTags if you use addCacheTags on the object.
        }
      }
    }
  }
}
