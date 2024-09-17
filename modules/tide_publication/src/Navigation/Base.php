<?php

namespace Drupal\tide_publication\Navigation;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\entity_hierarchy\Storage\NestedSetStorage;

/**
 * Class Base for navigation.
 */
abstract class Base extends EntityReferenceFieldItemList {
  use ComputedItemListTrait;

  const PUBLICATION_FIELD_NAME = 'field_publication';

  /**
   * The NestedSetStorageFactory service.
   *
   * @var \Drupal\entity_hierarchy\Storage\NestedSetStorageFactory
   */
  protected $nestedSetStorageFactory;

  /**
   * The NestedSetNodeKeyFactory service.
   *
   * @var \Drupal\entity_hierarchy\Storage\NestedSetNodeKeyFactory
   */
  protected $nestedSetNodeKeyFactory;

  /**
   * The EntityTreeNodeMapperInterface service.
   *
   * @var \Drupal\entity_hierarchy\Storage\EntityTreeNodeMapperInterface
   */
  protected $entityTreeNodeMapper;

  /**
   * Returns the currently active global container.
   *
   * @return \Symfony\Component\DependencyInjection\ContainerInterface|null
   *   The container.
   *
   * @throws \Drupal\Core\DependencyInjection\ContainerNotInitializedException
   */
  public static function getContainer() {
    return \Drupal::getContainer();
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(DataDefinitionInterface $definition, $name = NULL, TypedDataInterface $parent = NULL) {
    parent::__construct($definition, $name, $parent);

    $container = static::getContainer();
    $this->nestedSetStorageFactory = $container->get('entity_hierarchy.nested_set_storage_factory');
    $this->nestedSetNodeKeyFactory = $container->get('entity_hierarchy.nested_set_node_factory');
    $this->entityTreeNodeMapper = $container->get('entity_hierarchy.entity_tree_node_mapper');
  }

  /**
   * Get Nested Set Storage.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   *
   * @return \Drupal\entity_hierarchy\Storage\NestedSetStorage
   *   The storage.
   */
  protected function getStorage(ContentEntityInterface $entity = NULL) {
    if (!$entity) {
      $entity = $this->getEntity();
    }
    return $this->nestedSetStorageFactory->get(static::PUBLICATION_FIELD_NAME, $entity->getEntityTypeId());
  }

  /**
   * Check if the entity is of Publication types.
   *
   * @param array $allowed_bundles
   *   The allow node bundles.
   *
   * @return bool
   *   TRUE if valid.
   */
  protected function validateEntityType(array $allowed_bundles = [
    'publication',
    'publication_page',
  ]) {
    $entity = $this->getEntity();
    return ($entity->getEntityTypeId() == 'node') && in_array($entity->bundle(), $allowed_bundles) && !$entity->isNew();
  }

  /**
   * Find the root publication entity.
   *
   * @param \Drupal\Core\Cache\CacheableMetadata $cache
   *   The cache metadata.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   *   The root entity.
   */
  protected function findRootEntity(CacheableMetadata $cache) {
    $entity = $this->getEntity();
    if ($entity->isNew()) {
      return NULL;
    }
    /** @var \PNX\NestedSet\NestedSetInterface $storage */
    $storage = $this->getStorage();
    /** @var \PNX\NestedSet\NodeKey $entity_nodekey */
    $entity_nodekey = $this->nestedSetNodeKeyFactory->fromEntity($entity);
    if ($entity_nodekey) {
      /** @var \PNX\NestedSet\Node $root_node */
      $root_node = $storage->findRoot($entity_nodekey);
      if ($root_node) {
        $root_entities = $this->entityTreeNodeMapper->loadAndAccessCheckEntitysForTreeNodes($entity->getEntityTypeId(), [$root_node], $cache);
        if ($root_entities->contains($root_node)) {
          /** @var \Drupal\Core\Entity\ContentEntityInterface $root_entity */
          $root_entity = $root_entities->offsetGet($root_node);
          if ($root_entity->isDefaultRevision()) {
            $cache->addCacheableDependency($root_entity);
            return $root_entity;
          }
        }
      }
    }

    return NULL;
  }

  /**
   * Get the flatten hierarchy of a publication.
   *
   * @param \Drupal\Core\Cache\CacheableMetadata $cache
   *   The cache metadata.
   *
   * @return array
   *   The flatten hierarchy.
   */
  protected function getFlattenHierarchy(CacheableMetadata $cache) {
    $hierarchy = [];
    $root_entity = $this->findRootEntity($cache);
    if ($root_entity) {
      /** @var \Drupal\Core\Cache\CacheBackendInterface $cache */
      $cache_bin = static::getContainer()->get('cache.data');
      $cid = 'tide_publication:hierarchy:' . $root_entity->uuid() . ':flatten';
      $cached_data = $cache_bin->get($cid);
      if ($cached_data) {
        return $cached_data->data;
      }
      $storage = $this->getStorage($root_entity);
      $this->buildFlattenHierarchyRecursive($root_entity, $storage, $cache, NULL, $hierarchy);
      if (!empty($hierarchy)) {
        $cache_bin->set($cid, $hierarchy, $cache->getCacheMaxAge(), $cache->getCacheTags());
      }
    }

    return $hierarchy;
  }

  /**
   * Build the flatten hierarchy of a publication.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param \Drupal\entity_hierarchy\Storage\NestedSetStorage $storage
   *   The storage.
   * @param \Drupal\Core\Cache\CacheableMetadata $cache
   *   The cache object.
   * @param int|null $weight
   *   The weight of the current entity.
   * @param array $hierarchy
   *   The flatten hierarchy.
   */
  private function buildFlattenHierarchyRecursive(ContentEntityInterface $entity, NestedSetStorage $storage, CacheableMetadata $cache, $weight, array &$hierarchy) {
    $hierarchy[] = [
      'entity_id' => $entity->id(),
      'revision_id' => $entity->getRevisionId(),
      'bundle' => $entity->bundle(),
      'uuid' => $entity->uuid(),
      'weight' => $weight,
    ];

    /** @var \PNX\NestedSet\NestedSetInterface $storage */
    /** @var \PNX\NestedSet\Node[] $children */
    $children = $storage->findChildren($this->nestedSetNodeKeyFactory->fromEntity($entity));
    $child_entities = $this->entityTreeNodeMapper->loadAndAccessCheckEntitysForTreeNodes('node', $children, $cache);
    foreach ($children as $child_weight => $nested_node) {
      if (!$child_entities->contains($nested_node)) {
        // Doesn't exist or is access hidden.
        continue;
      }
      /** @var \Drupal\Core\Entity\ContentEntityInterface $child_entity */
      $child_entity = $child_entities->offsetGet($nested_node);
      if (!$child_entity->isDefaultRevision()) {
        continue;
      }

      $cache->addCacheableDependency($child_entity);
      $this->buildFlattenHierarchyRecursive($child_entity, $storage, $cache, $child_weight, $hierarchy);
    }
  }

}
