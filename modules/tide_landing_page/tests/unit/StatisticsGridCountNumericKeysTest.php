<?php

namespace Drupal\Tests\tide_landing_page\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\tide_landing_page\Validate\StatisticsGrid;

/**
 * Tests CountNumericKeys from StatisticsGrid class.
 *
 * @group tide_landing_page
 */
class StatisticsGridCountNumericKeysTest extends UnitTestCase {

  /**
   * Tests CountNumericKeys.
   */
  public function testCountNumericKeys() {
    $data = [
      '1' => 'value1',
      '2' => 'value2',
      'key' => 'value3',
      '3' => 'value4',
      'anotherKey' => 'value5',
    ];
    $expectedCount = 3;
    $actualCount = StatisticsGrid::countNumericKeys($data);
    $this->assertEquals($expectedCount, $actualCount, "Failed asserting that the count of numeric keys is correct.");
  }

}
