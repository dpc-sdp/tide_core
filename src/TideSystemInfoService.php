<?php

namespace Drupal\tide_core;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\monitoring\Entity\SensorConfig;
use Drupal\monitoring\SensorRunner;

/**
 * Service for retrieving system information.
 */
class TideSystemInfoService {

  /**
   * CacheBackendInterface.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * StateInterface.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * EntityTypeManagerInterface.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * EntityTypeBundleInfoInterface.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * EntityFieldManagerInterface.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * LoggerChannelFactoryInterface.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * FileSystemInterface.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * SensorRunner.
   *
   * @var \Drupal\monitoring\SensorRunner
   */
  protected $sensorRunner;

  /**
   * Constructs a new SystemInfoService.
   */
  public function __construct(
    CacheBackendInterface $cache_backend,
    StateInterface $state,
    EntityTypeManagerInterface $entity_type_manager,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    EntityFieldManagerInterface $entity_field_manager,
    LoggerChannelFactoryInterface $logger,
    FileSystemInterface $file_system,
    SensorRunner $sensor_runner
  ) {
    $this->cacheBackend = $cache_backend;
    $this->state = $state;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->entityFieldManager = $entity_field_manager;
    $this->logger = $logger;
    $this->fileSystem = $file_system;
    $this->sensorRunner = $sensor_runner;
  }

  /**
   * Gets system information including Tide and SDP versions.
   *
   * @return array
   *   An array containing Tide and SDP versions.
   */
  public function getSystemInfo() {
    $cid = 'tide_core:system_info:composer_versions';

    if ($cache = $this->cacheBackend->get($cid)) {
      return $cache->data;
    }

    $composer_file_path = $this->fileSystem->realpath(DRUPAL_ROOT . '/../composer.json');

    if (!file_exists($composer_file_path)) {
      $this->logger->get('tide_core')->error('composer.json not found.');
      return ['error' => 'composer.json not found'];
    }

    $composer_data = json_decode(file_get_contents($composer_file_path), TRUE);

    $data = [
      'tideVersion' => $composer_data['require']['dpc-sdp/tide'] ?? 'unknown',
      'sdpVersion' => $composer_data['extra']['sdp_version'] ?? 'unknown',
    ];

    $this->cacheBackend->set($cid, $data, $this->state->get('tide_core.cache_lifetime', 3600));

    return $data;
  }

  /**
   * Gets custom field information for specified entity types.
   *
   * @param array $requestedTypes
   *   Array of requested entity types or empty for all.
   *
   * @return array
   *   An array of custom field IDs grouped by entity type and bundle.
   */
  public function getCustomFields(array $requestedTypes = []) {
    $cid = 'tide_core:system_info:fields:' . md5(implode(',', $requestedTypes));

    if ($cache = $this->cacheBackend->get($cid)) {
      return $cache->data;
    }

    $result = [];
    $entityTypes = $this->entityTypeManager->getDefinitions();

    foreach ($entityTypes as $entityTypeId => $entityType) {
      if (!$entityType->entityClassImplements(ContentEntityInterface::class)) {
        continue;
      }

      if (!empty($requestedTypes) && !in_array($entityTypeId, $requestedTypes)) {
        continue;
      }

      $result[$entityTypeId] = [];
      $bundles = $this->entityTypeBundleInfo->getBundleInfo($entityTypeId);

      foreach ($bundles as $bundleId => $bundleInfo) {
        $result[$entityTypeId][$bundleId] = $this->getEntityCustomFields($entityTypeId, $bundleId);
      }
    }

    $this->cacheBackend->set($cid, $result, $this->state->get('tide_core.cache_lifetime', 3600));

    return $result;
  }

  /**
   * Gets custom fields for a specific entity type and bundle.
   *
   * @param string $entityTypeId
   *   The entity type ID.
   * @param string $bundleId
   *   The bundle ID.
   *
   * @return array
   *   Array of custom field names.
   */
  protected function getEntityCustomFields($entityTypeId, $bundleId) {
    $fieldDefinitions = $this->entityFieldManager->getFieldDefinitions($entityTypeId, $bundleId);
    return array_values(array_filter(array_keys($fieldDefinitions),
      function ($fieldName) use ($fieldDefinitions) {
        $fieldDefinition = $fieldDefinitions[$fieldName];
        return !$fieldDefinition->isComputed() && strpos($fieldName, 'field_') === 0;
      }));
  }

  /**
   * Gets all valid entity types.
   *
   * @return array
   *   Array of valid entity type IDs.
   */
  public function getValidEntityTypes() {
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

    $this->cacheBackend->set($cid, $validTypes, $this->state->get('tide_core.cache_lifetime', 86400));

    return $validTypes;
  }

  /**
   * Gets the version of a specified package from composer.lock.
   *
   * @param string $packageName
   *   The name of the package.
   *
   * @return array
   *   An array containing package name and version.
   */
  public function getPackageVersion($packageName) {
    if (empty($packageName)) {
      $result = $this->sensorRunner->runSensors([SensorConfig::load('tide_times')]);
      $value = $result[0]->getValue();
      $decodedValue = json_decode($value, TRUE);
      return $decodedValue;
    }

    if (strtolower($packageName) === 'php') {
      return [
        'package' => 'php',
        'version' => PHP_VERSION,
      ];
    }

    $cid = 'tide_core:system_info:package_version:' . md5($packageName);

    if ($cache = $this->cacheBackend->get($cid)) {
      return $cache->data;
    }

    $composer_lock_path = $this->fileSystem->realpath(DRUPAL_ROOT . '/../composer.lock');

    if (!file_exists($composer_lock_path)) {
      $this->logger->get('tide_core')->error('composer.lock not found');
      return ['error' => 'composer.lock not found'];
    }

    $composer_lock = json_decode(file_get_contents($composer_lock_path), TRUE);

    $version = $this->findPackageVersion($composer_lock, $packageName);

    if ($version === NULL) {
      return ['error' => 'Package not found'];
    }

    $data = [
      'package' => $packageName,
      'version' => $version,
    ];

    $this->cacheBackend->set($cid, $data);

    return $data;
  }

  /**
   * Finds the version of a package in the composer.lock.
   *
   * @param array $composer_lock
   *   The parsed composer.lock.
   * @param string $packageName
   *   The name of the package to find.
   *
   * @return string|null
   *   The version of the package, or null if not found.
   */
  protected function findPackageVersion(array $composer_lock, string $packageName) {
    foreach (['packages', 'packages-dev'] as $section) {
      foreach ($composer_lock[$section] as $package) {
        if ($package['name'] === $packageName) {
          return $package['version'];
        }
      }
    }
    return NULL;
  }

}
