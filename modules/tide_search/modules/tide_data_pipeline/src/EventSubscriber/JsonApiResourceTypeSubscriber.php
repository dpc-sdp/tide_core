<?php

namespace Drupal\tide_data_pipeline\EventSubscriber;

use Drupal\jsonapi\ResourceType\ResourceTypeBuildEvent;
use Drupal\jsonapi\ResourceType\ResourceTypeBuildEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Prevents invalid Data Pipeline resource types from breaking JSON:API.
 */
class JsonApiResourceTypeSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      ResourceTypeBuildEvents::BUILD => ['disableInvalidResourceTypes'],
    ];
  }

  /**
   * Disables resource types whose Data Pipeline bundle contains a colon.
   *
   * Data Pipeline bundle IDs are source plugin IDs such as "json:file".
   * JSON:API uses resource type names as link keys, where colons are invalid.
   */
  public function disableInvalidResourceTypes(ResourceTypeBuildEvent $event): void {
    $resource_type_name = $event->getResourceTypeName();

    if (str_starts_with($resource_type_name, 'data_pipelines--') && str_contains($resource_type_name, ':')) {
      $event->disableResourceType();
    }
  }

}
