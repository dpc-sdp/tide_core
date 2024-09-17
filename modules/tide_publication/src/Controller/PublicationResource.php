<?php

namespace Drupal\tide_publication\Controller;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Url;
use Drupal\entity_hierarchy\Storage\EntityTreeNodeMapperInterface;
use Drupal\entity_hierarchy\Storage\NestedSetNodeKeyFactory;
use Drupal\entity_hierarchy\Storage\NestedSetStorage;
use Drupal\entity_hierarchy\Storage\NestedSetStorageFactory;
use Drupal\jsonapi\Controller\EntityResource;
use Drupal\jsonapi\JsonApiResource\Link;
use Drupal\jsonapi\JsonApiResource\LinkCollection;
use Drupal\jsonapi\Routing\Routes;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class PublicationResource Controller.
 *
 * @package Drupal\tide_publication\Controller
 */
class PublicationResource extends EntityResource {
  use ContainerAwareTrait;

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
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The cache bin.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheData;

  /**
   * Injects dependencies.
   *
   * @param \Drupal\entity_hierarchy\Storage\NestedSetStorageFactory $nested_set_storage_factory
   *   NestedSetStorageFactory service.
   * @param \Drupal\entity_hierarchy\Storage\NestedSetNodeKeyFactory $nested_set_nodekey_factory
   *   NestedSetNodeKeyFactory service.
   * @param \Drupal\entity_hierarchy\Storage\EntityTreeNodeMapperInterface $entity_tree_node_mapper
   *   EntityTreeNodeMapperInterface service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_data
   *   Data cache bin.
   */
  public function setDependencies(NestedSetStorageFactory $nested_set_storage_factory, NestedSetNodeKeyFactory $nested_set_nodekey_factory, EntityTreeNodeMapperInterface $entity_tree_node_mapper, ModuleHandlerInterface $module_handler, CacheBackendInterface $cache_data) {
    $this->nestedSetStorageFactory = $nested_set_storage_factory;
    $this->nestedSetNodeKeyFactory = $nested_set_nodekey_factory;
    $this->entityTreeNodeMapper = $entity_tree_node_mapper;
    $this->moduleHandler = $module_handler;
    $this->cacheData = $cache_data;
  }

  /**
   * Get the hierarchy of a publication.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The loaded entity.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Drupal\jsonapi\ResourceResponse|\Symfony\Component\HttpFoundation\JsonResponse
   *   The response.
   */
  public function getHierarchy(EntityInterface $entity, Request $request) {
    if (!($entity instanceof ContentEntityInterface)) {
      throw new \Exception('The requested entity is not supported.');
    }

    $resource_object = $this->entityAccessChecker->getAccessCheckedResourceObject($entity);
    if ($entity->bundle() != 'publication') {
      $response = $this->buildWrappedResponse($resource_object, $request, $this->getIncludes($request, $resource_object));
      return $response;
    }

    if (!$this->nestedSetStorageFactory || !$this->nestedSetNodeKeyFactory || !$this->entityTreeNodeMapper) {
      throw new \Exception('The method setDependencies() must be called when instantiating a PublicationResource object.');
    }

    $resource_type = $this->resourceTypeRepository->get($entity->getEntityTypeId(), $entity->bundle());
    $route_name = Routes::getRouteName($resource_type, 'individual');
    $entity_link = Url::fromRoute($route_name, ['entity' => $entity->uuid()]);

    $response_data = [
      'type' => $resource_type->getTypeName(),
      'id' => $entity->uuid(),
      'links' => [
        'self' => [
          'href' => $entity_link->setAbsolute(TRUE)->toString(TRUE)->getGeneratedUrl(),
        ],
      ],
    ];

    $cache = new CacheableMetadata();
    $cache->addCacheableDependency($entity);

    $site = NULL;
    if ($this->moduleHandler->moduleExists('tide_site')) {
      /** @var \Drupal\tide_site\TideSiteHelper $site_helper */
      $site_helper = $this->container->get('tide_site.helper');
      $site_id = $request->get('site');
      if ($site_helper->isValidSite($site_id)) {
        $site = $site_helper->getSiteById($site_id);
      }
    }

    $publication_field_name = 'field_publication';
    /** @var \PNX\NestedSet\NestedSetInterface $storage */
    $storage = $this->nestedSetStorageFactory->get($publication_field_name, $entity->getEntityTypeId());
    $flatten_hierarchy = [];
    $hierarchy = $this->buildHierarchy($entity, $storage, $cache, NULL, $flatten_hierarchy, $site);
    $response_data['meta']['hierarchy'] = $hierarchy;

    // Cache the flatten hierarchy.
    if (!empty($flatten_hierarchy)) {
      $cid = 'tide_publication:hierarchy:' . $entity->uuid() . ':flatten';
      $this->cacheData->set($cid, $flatten_hierarchy, $cache->getCacheMaxAge(), $cache->getCacheTags());
    }

    $self_link = new Link(new CacheableMetadata(), self::getRequestLink($request), 'self');
    $links = (new LinkCollection([]))->withLink('self', $self_link);
    $serialized_links = $this->serializer->normalize($links, 'api_json');

    return new JsonResponse([
      'data' => $response_data,
      'links' => $serialized_links->getNormalization(),
    ], 200);
  }

  /**
   * Build the hierarchy.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param \Drupal\entity_hierarchy\Storage\NestedSetStorage $storage
   *   The storage.
   * @param \Drupal\Core\Cache\CacheableMetadata $cache
   *   The cache object.
   * @param int|null $weight
   *   The weight of the current entity.
   * @param array $flatten_hierarchy
   *   The flatten hierarchy to be built.
   * @param \Drupal\taxonomy\TermInterface|null $site
   *   The current site term.
   *
   * @return array
   *   The hierarchy.
   */
  protected function buildHierarchy(ContentEntityInterface $entity, NestedSetStorage $storage, CacheableMetadata $cache, $weight, array &$flatten_hierarchy, $site = NULL) {
    $resource_type = $this->resourceTypeRepository->get($entity->getEntityTypeId(), $entity->bundle());

    $hierarchy = [
      'id' => $entity->uuid(),
      'type' => $resource_type->getTypeName(),
      'entity_id' => $entity->id(),
      'title' => $entity->label(),
      'url' => $entity->toUrl('canonical')->toString(),
      'weight' => $weight,
    ];

    $flatten_hierarchy[] = [
      'entity_id' => $entity->id(),
      'revision_id' => $entity->getRevisionId(),
      'bundle' => $entity->bundle(),
      'uuid' => $entity->uuid(),
      'weight' => $weight,
    ];

    if ($site) {
      /** @var \Drupal\tide_site\TideSiteHelper $site_helper */
      $site_helper = $this->container->get('tide_site.helper');
      if (!$site_helper->isEntityBelongToSite($entity, $site->id())) {
        $hierarchy['url'] = $site_helper->getNodeUrlFromPrimarySite($entity);
      }
    }

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
      $hierarchy['children'][] = $this->buildHierarchy($child_entity, $storage, $cache, $child_weight, $flatten_hierarchy, $site);
    }

    return $hierarchy;
  }

}
