<?php

namespace Drupal\tide_core\Plugin\monitoring\SensorPlugin;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\monitoring\Entity\SensorConfig;
use Drupal\monitoring\Result\SensorResultInterface;
use Drupal\monitoring\SensorPlugin\SensorPluginBase;
use Drupal\monitoring\SensorPlugin\SensorPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Monitors the SMTP connection.
 *
 * @SensorPlugin(
 *   id = "tide_smtp",
 *   label = @Translation("Tide SMTP Sensor"),
 *   description = @Translation("Monitors connectivity to SMTP"),
 * )
 */
class TideSmtpSensorPlugin extends SensorPluginBase implements SensorPluginInterface {
  
  /**
   * The module handler.
   *
   * @var Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;
  
  public function __construct(SensorConfig $sensor_config, $plugin_id, $plugin_definition, ModuleHandlerInterface $module_handler) {
    parent::__construct($sensor_config, $plugin_id, $plugin_definition);
    $this->moduleHandler = $module_handler;
  }
  
  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, SensorConfig $sensor_config, $plugin_id, $plugin_definition) {
    return new static(
      $sensor_config,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler')
    );
  }
  
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
      } else {
        $result->setStatus(SensorResultInterface::STATUS_CRITICAL);
        $result->setMessage($tester->value);
      }
    }
    catch (\Exception $e) {
      $result->setStatus(SensorResultInterface::STATUS_CRITICAL);
      $result->setMessage('Exception encountered during SMTP test: ' . $e->getMessage());
    }
  }
  
}
