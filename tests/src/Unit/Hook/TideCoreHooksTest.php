<?php

namespace Drupal\Tests\tide_core\Unit\Hook;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\tide_core\Hook\TideCoreHooks;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Tide Core hook implementations.
 */
#[CoversMethod(TideCoreHooks::class, 'entityOperationAlter')]
#[Group('tide_core')]
class TideCoreHooksTest extends UnitTestCase {

  /**
   * Tests that the default View operation is removed.
   */
  public function testEntityOperationAlterRemovesView(): void {
    $operations = [
      'edit' => ['title' => 'Edit'],
      'view' => ['title' => 'View'],
    ];

    (new TideCoreHooks())->entityOperationAlter(
      $operations,
      $this->createMock(EntityInterface::class),
      new CacheableMetadata(),
    );

    $this->assertSame([
      'edit' => ['title' => 'Edit'],
    ], $operations);
  }

}
