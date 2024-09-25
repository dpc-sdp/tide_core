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
    /** @var \PNX\NestedSet\NestedSetInterface $storage */
    $storage = $this->getStorage();

    $cache = new CacheableMetadata();
    $cache->addCacheableDependency($entity);

    /** @var \PNX\NestedSet\Node[] $children */
    $children = $storage->findChildren($this->nestedSetNodeKeyFactory->fromEntity($entity));
    $child_entities = $this->entityTreeNodeMapper->loadAndAccessCheckEntitysForTreeNodes('node', $children, $cache);

    foreach ($children as $weight => $nested_node) {
      if (!$child_entities->contains($nested_node)) {
        // Doesn't exist or is access hidden.
        continue;
      }
      /** @var \Drupal\Core\Entity\ContentEntityInterface $child_entity */
      $child_entity = $child_entities->offsetGet($nested_node);
      if (!$child_entity->isDefaultRevision()) {
        continue;
      }
      $child_id = $nested_node->getId();

      $this->list[] = $this->createItem($weight, ['target_id' => $child_id]);
    }
  }

}
