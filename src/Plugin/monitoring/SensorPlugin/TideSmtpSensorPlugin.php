<?php

namespace Drupal\tide_core\Plugin\monitoring\SensorPlugin;

use Drupal\monitoring\Result\SensorResultInterface;
use Drupal\monitoring\SensorPlugin\SensorPluginBase;
use Drupal\monitoring\SensorPlugin\SensorPluginInterface;

/**
 * Monitors the SMTP connection.
 *
 * @SensorPlugin(
 *   id = "tide_smtp_sensor",
 *   addable = false,
 *   label = @Translation("Tide SMTP Sensor"),
 *   description = @Translation("Monitors connectivity to SMTP"),
 * )
 */
class TideSmtpSensorPlugin extends SensorPluginBase implements SensorPluginInterface {

  /**
   * Load the smtp connection tester.
   *
   * @return \Drupal\smtp\ConnectionTester\ConnectionTester
   *   The smtp connection service.
   */
  protected function getSmtpTesterService() {
    return \Drupal::service('smtp.connection_tester');
  }

  /**
   * Execute the sensor.
   *
   * @param \Drupal\monitoring\Result\SensorResultInterface $result
   *   An instance of SensorResult to set the test result on.
   */
  public function runSensor(SensorResultInterface $result) {
    try {
      $tester = $this->getSmtpTesterService();
      if ($tester->testConnection()) {
        $result->setStatus(SensorResultInterface::STATUS_OK);
        $result->setMessage('OK');
        return;
      }
      else {
        $result->setStatus(SensorResultInterface::STATUS_CRITICAL);
        $reqs = $tester->hookRequirements('runtime');
        $result->setMessage($reqs['smtp_connection']['value']);
      }
    }
    catch (\Exception $e) {
      $result->setStatus(SensorResultInterface::STATUS_CRITICAL);
      $result->setMessage('Exception encountered during SMTP test: ' . $e->getMessage());
    }
  }

}
