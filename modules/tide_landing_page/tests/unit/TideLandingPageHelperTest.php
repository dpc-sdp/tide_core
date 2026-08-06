<?php

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\test_helpers\TestHelpers;
use Drupal\Tests\UnitTestCase;
use Drupal\tide_landing_page\Helper\TideLandingPageHelper;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the TideLandingPageHelper class.
 */
#[Group('tide_landing_page')]
class TideLandingPageHelperTest extends UnitTestCase {

  /**
   * Tests the localDateAndTimeFormatter method.
   */
  public function testLocalDateAndTimeFormatter() {
    TestHelpers::service('language_manager');
    $date_config = $this->createMock(ImmutableConfig::class);
    $date_config->method('get')
      ->with('timezone.default')
      ->willReturn('Australia/Melbourne');
    $config_factory = $this->createMock(ConfigFactoryInterface::class);
    $config_factory->method('get')
      ->with('system.date')
      ->willReturn($date_config);
    TestHelpers::getContainer()->set('config.factory', $config_factory);
    // Instantiate the TideLandingPageHelper class with the mocked objects.
    $helper = new TideLandingPageHelper();
    // Call the method to be tested.
    $result = $helper->localDateAndTimeFormatter('2023-09-29T13:59:00');
    // Assert that the result matches the expected output.
    $this->assertEquals('2023-09-29T23:59:00+10:00', $result);
  }

}
