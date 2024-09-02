<?php

namespace Drupal\tide_api;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\RevisionableStorageInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\entity_reference_revisions\Plugin\Field\FieldType\EntityReferenceRevisionsItem;
use Drupal\jsonapi\Exception\EntityAccessDeniedHttpException;
use Drupal\jsonapi\IncludeResolver as JsonapiIncludeResolver;
use Drupal\jsonapi\JsonApiResource\Data;
use Drupal\jsonapi\JsonApiResource\IncludedData;
use Drupal\jsonapi\JsonApiResource\LabelOnlyResourceObject;
use Drupal\jsonapi\JsonApiResource\ResourceIdentifierInterface;
use Drupal\jsonapi\JsonApiResource\ResourceObject;

/**
 * Class Include Resolver.
 *
 * @package Drupal\tide_api
 */
class IncludeResolver extends JsonapiIncludeResolver {

  /**
   * {@inheritdoc}
   *
   * Allows JSON:API to load the correct revision of included Paragraphs
   * when loading an unpublished revision of a Node.
   *
   * @see \Drupal\jsonapi\IncludeResolver::resolveIncludeTree()
   */
  protected function resolveIncludeTree(array $include_tree, Data $data, Data $includes = NULL) {
    $includes = is_null($includes) ? new IncludedData([]) : $includes;
    foreach ($include_tree as $field_name => $children) {
      $references = [];
      foreach ($data as $resource_object) {
        // Some objects in the collection may be LabelOnlyResourceObjects or
        // EntityAccessDeniedHttpException objects.
        assert($resource_object instanceof ResourceIdentifierInterface);
        if ($resource_object instanceof LabelOnlyResourceObject) {
          $message = "The current user is not allowed to view this relationship.";
          $exception = new EntityAccessDeniedHttpException($resource_object->getEntity(), AccessResult::forbidden("The user only has authorization for the 'view label' operation."), '', $message, $field_name);
          $includes = IncludedData::merge($includes, new IncludedData([$exception]));
          continue;
        }
        elseif (!$resource_object instanceof ResourceObject) {
          continue;
        }
        $public_field_name = $resource_object->getResourceType()->getPublicName($field_name);
        // Not all entities in $entity_collection will be of the same bundle and
        // may not have all of the same fields. Therefore, calling
        // $resource_object->get($a_missing_field_name) will result in an
        // exception.
        if (!$resource_object->hasField($public_field_name)) {
          continue;
        }
        $field_list = $resource_object->getField($public_field_name);
        // Config entities don't have real fields and can't have relationships.
        if (!$field_list instanceof FieldItemListInterface) {
          continue;
        }
        $field_access = $field_list->access('view', NULL, TRUE);
        if (!$field_access->isAllowed()) {
          $message = 'The current user is not allowed to view this relationship.';
          $exception = new EntityAccessDeniedHttpException($field_list->getEntity(), $field_access, '', $message, $public_field_name);
          $includes = IncludedData::merge($includes, new IncludedData([$exception]));
          continue;
        }
        $target_type = $field_list->getFieldDefinition()->getFieldStorageDefinition()->getSetting('target_type');
        assert(!empty($target_type));
        foreach ($field_list as $field_item) {
          assert($field_item instanceof EntityReferenceItem);
          // Include the target_revision_id in the references of paragraphs.
          $target_type = $field_item->getDataDefinition()->getSetting('target_type');
          if ($field_item instanceof EntityReferenceRevisionsItem && $target_type == 'paragraph') {
            $references[$target_type][$field_item->get($field_item::mainPropertyName())->getValue()] = $field_item->get('target_revision_id')->getValue();
          }
          else {
            $references[$target_type][] = $field_item->get($field_item::mainPropertyName())->getValue();
          }
        }
      }
      foreach ($references as $target_type => $ids) {
        $entity_storage = $this->entityTypeManager->getStorage($target_type);
        // Load the revisions of the included Paragraphs.
        if ($entity_storage instanceof RevisionableStorageInterface && $target_type == 'paragraph') {
          $targeted_entities = $entity_storage->loadMultipleRevisions(array_unique(array_values($ids)));
        }
        else {
          $targeted_entities = $entity_storage->loadMultiple(array_unique($ids));
        }
        $access_checked_entities = array_map(function (EntityInterface $entity) {
          return $this->entityAccessChecker->getAccessCheckedResourceObject($entity);
        }, $targeted_entities);
        $targeted_collection = new IncludedData(array_filter($access_checked_entities, function (ResourceIdentifierInterface $resource_object) {
          return !$resource_object->getResourceType()->isInternal();
        }));
        $includes = static::resolveIncludeTree($children, $targeted_collection, IncludedData::merge($includes, $targeted_collection));
      }
    }
    return $includes;
  }

}
