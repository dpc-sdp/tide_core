<?php

namespace Drupal\Tests\tide_site\Unit;

use Drupal\tide_site\TideSiteMenuAutocreate;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests for TideSiteMenuAutocreate class.
 */
#[CoversClass(TideSiteMenuAutocreate::class)]
#[Group('tide')]
class TideSiteMenuAutocreateTest extends TideSiteTestBase {

  #[DataProvider('providerToMachineName')]
  public function testToMachineName($input, $delimiter, $expected) {
    $mock = self::createMock('Drupal\tide_site\TideSiteMenuAutocreate');
    $actual = $this->callProtectedMethod($mock, 'toMachineName', [$input, $delimiter]);
    $this->assertEquals($expected, $actual);
  }

  /**
   * Data provider to test toMachineName() method.
   */
  public static function providerToMachineName() {
    return [
      ['', '_', ''],
      ['abc', '_', 'abc'],
      ['123', '_', '123'],
      ['abc123', '_', 'abc123'],
      ['abc 123', '_', 'abc_123'],
      ['abc-123', '_', 'abc_123'],
      ['abc_123', '_', 'abc_123'],
      ['abc_123 def', '_', 'abc_123_def'],
      ['[abc] 123 def', '_', 'abc_123_def'],
      ['**[abc] 123 def  ', '_', 'abc_123_def'],
      ['**[abc] 123 def  ', '-', 'abc-123-def'],
    ];
  }

  #[DataProvider('providerMakeMenuLabel')]
  public function testMakeMenuLabel($menu_title, $parents, $expected) {
    $mock = self::prepareMock('Drupal\tide_site\TideSiteMenuAutocreate', [
      'loadTermParents' => $this->prepareMockTermParents($parents),
    ]);
    $actual = $this->callProtectedMethod($mock, 'makeMenuLabel', [$menu_title, NULL]);
    $this->assertEquals($expected, $actual);
  }

  /**
   * Data provider to test makeMenuLabel() method.
   */
  public static function providerMakeMenuLabel() {
    return [
      ['', [], ''],
      ['abc', [], 'abc'],
      ['abc', ['t1'], 'abc - t1'],
      ['abc', ['t1', 'p1'], 'abc - p1 - t1'],
      ['abc', ['t1', 'p1', 'p2'], 'abc - p2 - p1 - t1'],
    ];
  }

  #[DataProvider('providerMakeMenuName')]
  public function testMakeMenuName($menu_title, $parents, $expected) {
    $mock = self::prepareMock('Drupal\tide_site\TideSiteMenuAutocreate', [
      'loadTermParents' => $this->prepareMockTermParents($parents),
    ]);
    $actual = $this->callProtectedMethod($mock, 'makeMenuName', [$menu_title, NULL]);
    $this->assertEquals($expected, $actual);
  }

  /**
   * Data provider to test makeMenuName() method.
   *
   * Menu names are built from term ids (mocked sequentially from 1, ordered
   * from the term to the "oldest" parent) and truncated to 32 characters.
   */
  public static function providerMakeMenuName() {
    return [
      ['abc', [], 'site-abc'],
      ['abc', ['t1'], 'site-abc-1'],
      ['abc', ['t1', 'p1'], 'site-abc-2-1'],
      ['abc', ['t1', 'p1', 'p2'], 'site-abc-3-2-1'],
      ['a very long menu title indeed', ['t1', 'p1'], 'site-a-very-long-menu-title-inde'],
    ];
  }

}
