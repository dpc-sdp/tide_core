<?php

namespace Drupal\tide_publication\Navigation;

use Drupal\Core\Cache\CacheableMetadata;

/**
 * Class Root.
 */
class Children extends Base {

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\entity_hierarchy\Form\HierarchyChildrenForm::form()
   */
  protected function computeValue() {
    if (!$this->validateEntityType()) {
      return;
    }

    $entity = $this->getEntity();
    $storage = $this->getStorage();

    $cache = new CacheableMetadata();
    $cache->addCacheableDependency($entity);

    foreach ($storage->findChildren($entity) as $record) {
      $child_entity = $record->getEntity();
      if (!$child_entity) {
        continue;
      }
      $access = $child_entity->access('view', NULL, TRUE);
      $cache->addCacheableDependency($access);
      if (!$access->isAllowed() || !$child_entity->isDefaultRevision()) {
        continue;
      }
      $cache->addCacheableDependency($child_entity);

      $this->list[] = $this->createItem($record->getWeight(), ['target_id' => $record->getId()]);
    }
  }

}
