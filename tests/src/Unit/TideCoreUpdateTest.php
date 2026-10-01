<?php

declare(strict_types=1);

namespace Drupal\Tests\tide_core\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\filter\FilterFormatInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests tide_core update hooks.
 */
#[CoversFunction('tide_core_update_11005')]
#[Group('tide_core')]
final class TideCoreUpdateTest extends UnitTestCase {

  /**
   * The expected filter configuration.
   */
  private const FILTER_CONFIGURATION = [
    'id' => 'tide_ckeditor_iframe_permissions',
    'provider' => 'tide_core',
    'status' => TRUE,
    'weight' => 101,
    'settings' => [],
  ];

  /**
   * Tests that the filter is enabled on existing supported formats.
   */
  public function testUpdate11005(): void {
    if (!function_exists('tide_core_update_11005')) {
      require_once dirname(__DIR__, 3) . '/tide_core.install';
    }

    $rich_text = $this->createMock(FilterFormatInterface::class);
    $rich_text->expects($this->once())
      ->method('setFilterConfig')
      ->with('tide_ckeditor_iframe_permissions', self::FILTER_CONFIGURATION)
      ->willReturnSelf();
    $rich_text->expects($this->once())->method('save');

    $summary_text = $this->createMock(FilterFormatInterface::class);
    $summary_text->expects($this->once())
      ->method('setFilterConfig')
      ->with('tide_ckeditor_iframe_permissions', self::FILTER_CONFIGURATION)
      ->willReturnSelf();
    $summary_text->expects($this->once())->method('save');

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->exactly(3))
      ->method('load')
      ->willReturnMap([
        ['rich_text', $rich_text],
        ['admin_text', NULL],
        ['summary_text', $summary_text],
      ]);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->expects($this->once())
      ->method('getStorage')
      ->with('filter_format')
      ->willReturn($storage);

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $entity_type_manager);
    \Drupal::setContainer($container);

    tide_core_update_11005();
  }

}
