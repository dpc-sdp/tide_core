<?php

use Drupal\test_helpers\TestHelpers;
use Drupal\Tests\UnitTestCase;
use Drupal\tide_landing_page\Helper\TideLandingPageHelper;

/**
 * Tests the TideLandingPageHelper class.
 *
 * @group tide_landing_page
 */
class TideLandingPageHelperTest extends UnitTestCase {

  /**
   * Tests the localDateAndTimeFormatter method.
   */
  public function testLocalDateAndTimeFormatter() {
    TestHelpers::service('date.formatter')->stubSetFormat('medium', 'Medium', 'd.m.Y');
    TestHelpers::service('config.factory')->stubSetConfig('system.date', ['timezone.default' => 'Australia/Melbourne']);
    // Instantiate the TideLandingPageHelper class with the mocked objects.
    $helper = new TideLandingPageHelper();
    // Call the method to be tested.
    $result = $helper->localDateAndTimeFormatter('2023-09-29T13:59:00');
    // Assert that the result matches the expected output.
    $this->assertEquals('2023-09-29 23:59:00', $result);
  }

}
