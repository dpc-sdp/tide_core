<?php

declare(strict_types=1);

namespace Drupal\tide_data_pipeline_json_endpoint\Access;

use Drupal\Core\Access\AccessCheckInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\key\KeyRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Validates requests to the JSON endpoint push route via an API key header.
 *
 * The expected key is read from the key.key.data_pipeline_push_api_key config
 * entity, which is sourced from the DATA_PIPELINE_PUSH_API_KEY environment
 * variable via the key module.
 *
 * Clients must supply the key in the X-Api-Key request header.
 */
class ApiKeyAccessCheck implements AccessCheckInterface {

  const HEADER_NAME = 'X-Api-Key';
  const KEY_ID = 'data_pipeline_push_api_key';

  public function __construct(private readonly KeyRepositoryInterface $keyRepository) {}

  /**
   * {@inheritdoc}
   */
  public function applies(Route $route): bool {
    return $route->hasRequirement('_data_pipeline_api_key');
  }

  /**
   * Allows the request if the X-Api-Key header matches the configured key.
   */
  public function access(Route $route, Request $request): AccessResultInterface {
    $provided = $request->headers->get(static::HEADER_NAME, '');
    if (empty($provided)) {
      return AccessResult::forbidden('Missing ' . static::HEADER_NAME . ' header.')->setCacheMaxAge(0);
    }

    $key = $this->keyRepository->getKey(static::KEY_ID);
    $expected = $key ? $key->getKeyValue() : NULL;

    if (empty($expected)) {
      return AccessResult::forbidden('API key not configured.')->setCacheMaxAge(0);
    }

    // Use hash_equals to prevent timing attacks.
    if (!hash_equals($expected, $provided)) {
      return AccessResult::forbidden('Invalid API key.')->setCacheMaxAge(0);
    }

    return AccessResult::allowed()->setCacheMaxAge(0);
  }

}
