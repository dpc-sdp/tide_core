<?php

declare(strict_types=1);

namespace Drupal\Tests\tide_data_pipeline_json_endpoint\Unit\Plugin\DatasetSource;

use Drupal\Tests\UnitTestCase;
use Drupal\tide_data_pipeline_json_endpoint\Plugin\DatasetSource\JsonEndpointSource;

/**
 * @group tide_data_pipeline_json_endpoint
 *
 * @covers \Drupal\tide_data_pipeline_json_endpoint\Plugin\DatasetSource\JsonEndpointSource
 */
class JsonEndpointSourceTest extends UnitTestCase {

  /**
   * Tests that buildStorageUri returns a well-formed private:// URI.
   */
  public function testBuildStorageUriReturnsCorrectPrivatePath(): void {
    $this->assertSame(
      'private://data_pipelines_json_endpoint/my_dataset.json',
      JsonEndpointSource::buildStorageUri('my_dataset')
    );
  }

  /**
   * Tests buildStorageUri with various valid machine names.
   *
   * @dataProvider machineNameProvider
   */
  public function testBuildStorageUriHandlesVariousMachineNames(string $name, string $expected): void {
    $this->assertSame($expected, JsonEndpointSource::buildStorageUri($name));
  }

  public static function machineNameProvider(): array {
    return [
      'underscored name' => [
        'station_locator',
        'private://data_pipelines_json_endpoint/station_locator.json',
      ],
      'name with numbers' => [
        'dataset_2024',
        'private://data_pipelines_json_endpoint/dataset_2024.json',
      ],
      'single character' => [
        'a',
        'private://data_pipelines_json_endpoint/a.json',
      ],
    ];
  }

  /**
   * Tests that the storage scheme and directory constants have expected values.
   */
  public function testStorageConstants(): void {
    $this->assertSame('private', JsonEndpointSource::STORAGE_SCHEME);
    $this->assertSame('data_pipelines_json_endpoint', JsonEndpointSource::STORAGE_DIR);
  }

}
