<?php

namespace Drupal\tide_publication\Navigation;

use Drupal\Core\Cache\CacheableMetadata;

/**
 * Class Next.
 */
class Previous extends Base {

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    if (!$this->validateEntityType()) {
      return;
    }

    $entity = $this->getEntity();

    $cache = new CacheableMetadata();
    $cache->addCacheableDependency($entity);

    $root_entity = $this->findRootEntity($cache);
    if ($root_entity) {
      $hierarchy = $this->getFlattenHierarchy($cache);
      // Search for the current entity in the hierarchy.
      foreach ($hierarchy as $delta => $node) {
        if ($node['entity_id'] == $entity->id()) {
          $prev = $delta - 1;
          if (isset($hierarchy[$prev])) {
            $this->list[] = $this->createItem(0, ['target_id' => $hierarchy[$prev]['entity_id']]);
            return;
          }
        }
      }
    }
  }

}
