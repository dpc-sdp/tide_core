<?php

namespace Drupal\tide_content_collection\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Hook implementations for the tide content collection module.
 */
class TideContentCollectionHooks {

  /**
   * Implements hook_ENTITY_TYPE_access().
   */
  #[Hook('paragraph_access')]
  public function paragraphAccess(ParagraphInterface $entity, $operation, AccountInterface $account) {
    $type = $entity->getType();
    if ($type === 'content_collection') {
      if (in_array($operation, ['update', 'delete']) && !$account->hasPermission('access content_collection paragraph')) {
        return AccessResult::forbidden()->cachePerPermissions();
      }
    }
    return AccessResult::neutral()->cachePerPermissions();
  }

  /**
   * Implements hook_ENTITY_TYPE_create_access().
   */
  #[Hook('paragraph_create_access')]
  public function paragraphCreateAccess(?AccountInterface $account = NULL, array $context = [], $entity_bundle = NULL) {
    $type = $entity_bundle;
    if (($type === 'content_collection' || $type === 'content_collection_enhanced') && !$account->hasPermission('access content_collection paragraph')) {
      return AccessResult::forbidden()->cachePerPermissions();
    }
    return AccessResult::neutral()->cachePerPermissions();
  }

}
