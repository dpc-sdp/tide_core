<?php

namespace Drupal\tide_data_pipeline\ResourceType;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\jsonapi_extras\ResourceType\ConfigurableResourceTypeRepository;

/**
 * Removes the routes of Data Pipeline resource types with an invalid name.
 *
 * Data Pipeline bundle IDs are source plugin IDs such as "csv:file". JSON:API
 * uses resource type names as link keys, where colons are invalid. A resource
 * type without routes is not linked, unlike a disabled resource type it can
 * still be the target of a relationship such as field_select_dataset.
 */
class DataPipelinesResourceTypeRepository extends ConfigurableResourceTypeRepository {

  /**
   * {@inheritdoc}
   */
  protected static function isMutableResourceType(EntityTypeInterface $entity_type, $bundle) {
    return !static::hasInvalidName($entity_type, $bundle) && parent::isMutableResourceType($entity_type, $bundle);
  }

  /**
   * {@inheritdoc}
   */
  protected static function isLocatableResourceType(EntityTypeInterface $entity_type, $bundle) {
    return !static::hasInvalidName($entity_type, $bundle) && parent::isLocatableResourceType($entity_type, $bundle);
  }

  /**
   * Whether the resource type name is invalid as a JSON:API link key.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   * @param string $bundle
   *   The bundle.
   *
   * @return bool
   *   TRUE if it is a Data Pipeline resource type with a colon in its bundle.
   */
  protected static function hasInvalidName(EntityTypeInterface $entity_type, $bundle) {
    return $entity_type->id() === 'data_pipelines' && str_contains($bundle, ':');
  }

}
