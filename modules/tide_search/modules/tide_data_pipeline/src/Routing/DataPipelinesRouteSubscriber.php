<?php

namespace Drupal\tide_data_pipeline\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;
use Drupal\jsonapi\Routing\Routes;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Adds an inaccessible individual route for routeless Data Pipeline resources.
 *
 * JSON:API links an access denied error to the individual route of the entity
 * without checking that the resource type is locatable. Without this route, a
 * related or include request for a dataset the user cannot view fails with a
 * 500 error instead of reporting the access denied error.
 *
 * @see \Drupal\jsonapi\Normalizer\EntityAccessDeniedHttpExceptionNormalizer
 * @see \Drupal\tide_data_pipeline\ResourceType\DataPipelinesResourceTypeRepository
 */
class DataPipelinesRouteSubscriber extends RouteSubscriberBase {

  /**
   * Constructs a DataPipelinesRouteSubscriber object.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface $resourceTypeRepository
   *   The resource type repository.
   * @param string $jsonApiBasePath
   *   The JSON:API base path.
   */
  public function __construct(
    protected ResourceTypeRepositoryInterface $resourceTypeRepository,
    protected string $jsonApiBasePath,
  ) {}

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach ($this->resourceTypeRepository->all() as $resource_type) {
      if ($resource_type->getEntityTypeId() !== 'data_pipelines' || $resource_type->isInternal() || $resource_type->isLocatable()) {
        continue;
      }

      $route = new Route(sprintf('%s%s/{entity}', $this->jsonApiBasePath, $resource_type->getPath()));
      $route->setMethods(['GET']);
      $route->setDefault(Routes::JSON_API_ROUTE_FLAG_KEY, TRUE);
      $route->setRequirement('_access', 'FALSE');
      $route->setRequirement('_format', 'api_json');
      $collection->add(Routes::getRouteName($resource_type, 'individual'), $route);
    }
  }

}
