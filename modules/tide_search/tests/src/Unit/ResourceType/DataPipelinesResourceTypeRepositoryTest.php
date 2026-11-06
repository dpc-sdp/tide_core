<?php

namespace Drupal\Tests\tide_data_pipeline\Unit\ResourceType;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\tide_data_pipeline\ResourceType\DataPipelinesResourceTypeRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the Data Pipeline JSON:API resource type repository.
 */
#[CoversClass(DataPipelinesResourceTypeRepository::class)]
#[Group('tide_data_pipeline')]
class DataPipelinesResourceTypeRepositoryTest extends UnitTestCase {

  /**
   * Tests resource types with an invalid name have no routes.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The bundle.
   * @param bool $has_routes
   *   Whether the resource type should have routes.
   */
  #[DataProvider('resourceTypeProvider')]
  public function testResourceTypeRoutes(string $entity_type_id, string $bundle, bool $has_routes): void {
    $entity_type = $this->createMock(EntityTypeInterface::class);
    $entity_type->method('id')->willReturn($entity_type_id);

    foreach (['isLocatableResourceType', 'isMutableResourceType'] as $method) {
      $reflection = new \ReflectionMethod(DataPipelinesResourceTypeRepository::class, $method);
      $this->assertSame($has_routes, $reflection->invoke(NULL, $entity_type, $bundle));
    }
  }

  /**
   * Provides entity types and bundles.
   *
   * @return array<string, array{string, string, bool}>
   *   The entity type ID, bundle and expected result.
   */
  public static function resourceTypeProvider(): array {
    return [
      'CSV file source' => ['data_pipelines', 'csv:file', FALSE],
      'JSON file source' => ['data_pipelines', 'json:file', FALSE],
      'CSV private file source' => ['data_pipelines', 'csv:private_file', FALSE],
      'valid Data Pipeline type' => ['data_pipelines', 'custom_source', TRUE],
      'unrelated resource type' => ['node', 'landing_page', TRUE],
    ];
  }

}
