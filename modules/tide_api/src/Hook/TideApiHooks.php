<?php

namespace Drupal\tide_api\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\jsonapi\Routing\Routes;

/**
 * Hook implementations for the tide api module.
 */
class TideApiHooks {

  /**
   * Implements hook_entity_access().
   */
  #[Hook('entity_access')]
  public function entityAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($entity->getEntityTypeId() == 'menu' || $entity->getEntityTypeId() == 'menu_link_content') {
      if ($operation == 'view') {
        if (Routes::isJsonApiRequest(\Drupal::request()->attributes->all())) {
          return AccessResult::allowed()->addCacheableDependency($entity);
        }
      }
    }
    return AccessResult::neutral();
  }

  /**
   * Implements hook_jsonapi_entity_filter_access().
   */
  #[Hook('jsonapi_entity_filter_access')]
  public function jsonapiEntityFilterAccess(EntityTypeInterface $entity_type, AccountInterface $account) {
    if (in_array($entity_type->id(), ['node', 'paragraph'])) {
      return [
        JSONAPI_FILTER_AMONG_PUBLISHED => AccessResult::allowedIfHasPermission($account, 'access content')->cachePerPermissions(),
      ];
    }
    elseif ($entity_type->id() == 'media') {
      return [
        JSONAPI_FILTER_AMONG_PUBLISHED => AccessResult::allowedIfHasPermission($account, 'view media')->cachePerPermissions(),
      ];
    }
    elseif (in_array($entity_type->id(), ['menu', 'menu_link_content'])) {
      return [
        JSONAPI_FILTER_AMONG_ALL => AccessResult::allowed()->cachePerPermissions(),
      ];
    }

    return [JSONAPI_FILTER_AMONG_ALL => AccessResult::neutral()];
  }

}
