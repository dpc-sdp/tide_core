<?php

namespace Drupal\tide_core\Controller;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Returns system information including tide and sdp versions.
 */
class SystemInfoController extends ControllerBase {

  /**
   * Cache backend interface.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * Constructs a SystemInfoController object.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend interface.
   */
  public function __construct(CacheBackendInterface $cache_backend) {
    $this->cacheBackend = $cache_backend;
  }

  /**
   * Dependency injection.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('cache.default')
    );
  }

  /**
   * Reads composer.json and returns tide and sdp versions.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The system information in JSON format.
   */
  public function getSystemInfo() {
    $cid = 'system_info:composer_versions';

    if ($cache = $this->cacheBackend->get($cid)) {
      return new JsonResponse($cache->data);
    }

    $composer_file_path = DRUPAL_ROOT . '/../composer.json';

    if (!file_exists($composer_file_path)) {
      return new JsonResponse(['error' => 'composer.json not found'], Response::HTTP_NOT_FOUND);
    }

    $composer_data = json_decode(file_get_contents($composer_file_path), TRUE);

    $sdp_version = $composer_data['extra']['sdp_version'] ?? 'unknown';
    $tide_version = $composer_data['require']['dpc-sdp/tide'] ?? 'unknown';

    $data = [
      'tideVersion' => $tide_version,
      'sdpVersion' => $sdp_version,
    ];

    $this->cacheBackend->set($cid, $data, time() + 3600);

    return new JsonResponse($data);
  }

}
