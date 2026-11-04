<?php

namespace Drupal\Tests\tide_data_pipeline\Unit\Routing;

use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\tide_data_pipeline\Routing\DataPipelinesRouteSubscriber;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Routing\RouteCollection;

/**
 * Tests the Data Pipeline route subscriber.
 */
#[CoversClass(DataPipelinesRouteSubscriber::class)]
#[Group('tide_data_pipeline')]
class DataPipelinesRouteSubscriberTest extends UnitTestCase {

  /**
   * Tests only routeless Data Pipeline resource types get a denied route.
   */
  public function testAlterRoutes(): void {
    $repository = $this->createMock(ResourceTypeRepositoryInterface::class);
    $repository->method('all')->willReturn([
      new ResourceType('data_pipelines', 'csv:file', NULL, FALSE, FALSE, FALSE, FALSE, []),
      new ResourceType('data_pipelines', 'custom_source', NULL, FALSE, TRUE, TRUE, FALSE, []),
      new ResourceType('data_pipelines', 'internal_source', NULL, TRUE, FALSE, FALSE, FALSE, []),
      new ResourceType('node', 'landing_page', NULL, FALSE, FALSE, FALSE, FALSE, []),
    ]);

    $collection = new RouteCollection();
    (new DataPipelinesRouteSubscriber($repository, '/api/v1'))->onAlterRoutes(new RouteBuildEvent($collection));

    $this->assertSame(['jsonapi.data_pipelines--csv:file.individual'], array_keys($collection->all()));
    $route = $collection->get('jsonapi.data_pipelines--csv:file.individual');
    $this->assertSame('/api/v1/data_pipelines/csv:file/{entity}', $route->getPath());
    $this->assertSame('FALSE', $route->getRequirement('_access'));
  }

}
