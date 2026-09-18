<?php

namespace Drupal\Tests\tide_data_pipeline\Unit\EventSubscriber;

use Drupal\jsonapi\ResourceType\ResourceTypeBuildEvent;
use Drupal\Tests\UnitTestCase;
use Drupal\tide_data_pipeline\EventSubscriber\JsonApiResourceTypeSubscriber;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the Data Pipeline JSON:API resource type subscriber.
 */
#[CoversClass(JsonApiResourceTypeSubscriber::class)]
#[Group('tide_data_pipeline')]
class JsonApiResourceTypeSubscriberTest extends UnitTestCase {

  /**
   * Tests invalid Data Pipeline resource types are disabled.
   *
   * @param string $resource_type_name
   *   The JSON:API resource type name.
   * @param bool $should_be_disabled
   *   Whether the resource type should be disabled.
   */
  #[DataProvider('resourceTypeProvider')]
  public function testDisableInvalidResourceTypes(string $resource_type_name, bool $should_be_disabled): void {
    $event = $this->createMock(ResourceTypeBuildEvent::class);
    $event->expects($this->once())
      ->method('getResourceTypeName')
      ->willReturn($resource_type_name);
    $event->expects($should_be_disabled ? $this->once() : $this->never())
      ->method('disableResourceType');

    (new JsonApiResourceTypeSubscriber())->disableInvalidResourceTypes($event);
  }

  /**
   * Provides JSON:API resource type names.
   *
   * @return array<string, array{string, bool}>
   *   Resource type names and their expected disabled state.
   */
  public static function resourceTypeProvider(): array {
    return [
      'JSON file source' => ['data_pipelines--json:file', TRUE],
      'CSV private file source' => ['data_pipelines--csv:private_file', TRUE],
      'valid Data Pipeline type' => ['data_pipelines--custom_source', FALSE],
      'unrelated resource type' => ['node--landing_page', FALSE],
    ];
  }

}
