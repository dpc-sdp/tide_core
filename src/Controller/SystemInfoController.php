<?php

namespace Drupal\tide_core\Controller;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\tide_core\TideSystemInfoService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Returns system information including tide and sdp versions.
 */
class SystemInfoController extends ControllerBase {

  /**
   * The system info service.
   *
   * @var \Drupal\tide_core\TideSystemInfoService
   */
  protected $systemInfoService;

  /**
   * Constructs a SystemInfoController object.
   *
   * @param \Drupal\tide_core\TideSystemInfoService $system_info_service
   *   The system info service.
   */
  public function __construct(TideSystemInfoService $system_info_service) {
    $this->systemInfoService = $system_info_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tide_core.system_info_service')
    );
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
    $requestedTypes = $types === 'all' ? [] : explode(',', $types);

    $validTypes = $this->systemInfoService->getValidEntityTypes();
    $invalidTypes = array_diff($requestedTypes, $validTypes);

    if (!empty($invalidTypes)) {
      return new CacheableJsonResponse([
        'error' => 'Invalid entity type(s): ' . implode(', ', $invalidTypes),
        'valid_types' => $validTypes,
      ], Response::HTTP_BAD_REQUEST);
    }

    $data = $this->systemInfoService->getCustomFields($requestedTypes);
    return new CacheableJsonResponse($data);
  }

  /**
   * Returns the version of a specified package from composer.lock.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The package version in JSON format.
   */
  public function getPackageVersion(Request $request) {
    $packageName = $request->query->get('q');
    $data = $this->systemInfoService->getPackageVersion($packageName);
    $response = new CacheableJsonResponse($data);
    $response->addCacheableDependency((new CacheableMetadata())->addCacheContexts(['url.query_args:q']));
    return $response;
  }

}
