<?php

namespace Drupal\tide_core\Controller;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Cache\CacheBackendInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\HttpFoundation\Response;
use Psr\Log\LoggerInterface;

/**
 * Returns system information including tide and sdp versions.
 */
class SystemInfoController extends ControllerBase {

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a SystemInfoController object.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend interface.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   */
  public function __construct(
    CacheBackendInterface $cache_backend,
    StateInterface $state,
    EntityTypeManagerInterface $entity_type_manager,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    EntityFieldManagerInterface $entity_field_manager,
    LoggerInterface $logger
  ) {
    $this->cacheBackend         = $cache_backend;
    $this->state                = $state;
    $this->entityTypeManager    = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->entityFieldManager   = $entity_field_manager;
    $this->logger               = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('cache.default'),
      $container->get('state'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity_field.manager'),
      $container->get('logger.factory')->get('tide_core')
    );
  }

  /**
   * Reads composer.json and returns tide and sdp versions.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The system information in JSON format.
   */
  public function getSystemInfo() {
    $cid = 'tide_core:system_info:composer_versions';

    if ($cache = $this->cacheBackend->get($cid)) {
      return new JsonResponse($cache->data);
    }

    $composer_file_path = DRUPAL_ROOT . '/../composer.json';

    if (!file_exists($composer_file_path)) {
      $this->logger->error('composer.json not found at @path',
        ['@path' => $composer_file_path]);
      return new JsonResponse(['error' => 'composer.json not found'],
        Response::HTTP_NOT_FOUND);
    }

    $composer_data = json_decode(file_get_contents($composer_file_path), TRUE);

    $data = [
      'tideVersion' => $composer_data['require']['dpc-sdp/tide'] ?? 'unknown',
      'sdpVersion'  => $composer_data['extra']['sdp_version'] ?? 'unknown',
    ];

    $this->cacheBackend->set($cid,
      $data,
      $this->state->get('tide_core.cache_lifetime', 3600));

    return new JsonResponse($data);
  }

  /**
   * Returns custom field information for specified entity types.
   *
   * @param string $types
   *   Comma-separated list of entity types or 'all'.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The field information in JSON format.
   */
  public function getFields($types = 'all') {
    try {
      $requestedTypes = $types === 'all' ? [] : explode(',', $types);

      $validTypes   = $this->getValidEntityTypes();
      $invalidTypes = array_diff($requestedTypes, $validTypes);

      if (!empty($invalidTypes)) {
        return new JsonResponse([
          'error'       => 'Invalid entity type(s): ' . implode(', ',
              $invalidTypes),
          'valid_types' => $validTypes,
        ], Response::HTTP_BAD_REQUEST);
      }

      $data = $this->getCustomFieldIds($requestedTypes);
      return new JsonResponse($data);
    }
    catch (\Exception $e) {
      $this->logger->error('Error fetching field information: @message',
        ['@message' => $e->getMessage()]);
      return new JsonResponse(['error' => 'An error occurred while fetching field information.'],
            Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  /**
   * Returns custom field IDs for specified entity types.
   *
   * @param array $requestedTypes
   *   Array of requested entity types.
   *
   * @return array
   *   Array of custom field IDs grouped by entity type and bundle.
   */
  private function getCustomFieldIds(array $requestedTypes = []) {
    $cid = 'tide_core:system_info:fields:' . md5(implode(',',
        $requestedTypes));

    if ($cache = $this->cacheBackend->get($cid)) {
      return $cache->data;
    }

    $result = [];

    $entityTypes = $this->entityTypeManager->getDefinitions();

    foreach ($entityTypes as $entityTypeId => $entityType) {
      if (!$entityType->entityClassImplements(ContentEntityInterface::class)) {
        continue;
      }

      if (!empty($requestedTypes)
          && !in_array($entityTypeId,
          $requestedTypes)
      ) {
        continue;
      }

      $result[$entityTypeId] = [];

      $bundles = $this->entityTypeBundleInfo->getBundleInfo($entityTypeId);
      foreach ($bundles as $bundleId => $bundleInfo) {
        $result[$entityTypeId][$bundleId]
          = $this->getEntityCustomFields($entityTypeId, $bundleId);
      }
    }

    $this->cacheBackend->set($cid,
      $result,
      $this->state->get('tide_core.cache_lifetime', 3600));

    return $result;
  }

  /**
   * Returns custom fields for a specific entity type and bundle.
   *
   * @param string $entityTypeId
   *   The entity type ID.
   * @param string $bundleId
   *   The bundle ID.
   *
   * @return array
   *   Array of custom field names.
   */
  private function getEntityCustomFields($entityTypeId, $bundleId) {
    $fieldDefinitions
      = $this->entityFieldManager->getFieldDefinitions($entityTypeId,
      $bundleId);
    return array_values(array_filter(array_keys($fieldDefinitions),
      function ($fieldName) use ($fieldDefinitions) {
        $fieldDefinition = $fieldDefinitions[$fieldName];
        return !$fieldDefinition->isComputed()
               && strpos($fieldName, 'field_') === 0;
      }));
  }

  /**
   * Returns all valid entity types.
   *
   * @return array
   *   Array of valid entity type IDs.
   */
  private function getValidEntityTypes() {
    $cid = 'tide_core:system_info:valid_entity_types';

    if ($cache = $this->cacheBackend->get($cid)) {
      return $cache->data;
    }

    $validTypes = array_keys(array_filter(
      $this->entityTypeManager->getDefinitions(),
      function ($entityType) {
        return $entityType->entityClassImplements(ContentEntityInterface::class);
      }
    ));

    $this->cacheBackend->set($cid,
      $validTypes,
      $this->state->get('tide_core.cache_lifetime', 86400));

    return $validTypes;
  }

}
