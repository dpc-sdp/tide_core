<?php

namespace Drupal\tide_jira\Plugin\monitoring\SensorPlugin;

use Drupal\Core\Config\ConfigFactory;
use Drupal\jira_rest\JiraRestWrapperService;
use Drupal\monitoring\Entity\SensorConfig;
use Drupal\monitoring\Result\SensorResultInterface;
use Drupal\monitoring\SensorPlugin\SensorPluginBase;
use Drupal\monitoring\SensorPlugin\SensorPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Monitors the Section API connection.
 *
 * @SensorPlugin(
 *   id = "tide_jira",
 *   label = @Translation("Tide Jira Sensor"),
 *   description = @Translation("Monitors connectivity to the Jira Service Desk API"),
 * )
 */
class TideJiraSensorPlugin extends SensorPluginBase implements SensorPluginInterface {

  /**
   * The config.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $config;

  /**
   * The connector.
   *
   * @var \Drupal\jira_rest\JiraRestWrapperService
   */
  protected $connector;

  /**
   * {@inheritdoc}
   */
  public function __construct(SensorConfig $sensor_config, $plugin_id, $plugin_definition, ConfigFactory $config_factory, JiraRestWrapperService $jira_rest) {
    parent::__construct($sensor_config, $plugin_id, $plugin_definition);
    $this->config = $config_factory->get('tide_jira.settings');
    $this->connector = $jira_rest;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, SensorConfig $sensor_config, $plugin_id, $plugin_definition) {
    return new static(
      $sensor_config,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('jira_rest_wrapper_service'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultConfiguration() {
    return [
      'caching_time' => 60 * 5,
      'value_type' => 'bool',
      'category' => 'Tide',
      'settings' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function runSensor(SensorResultInterface $sensor_result) {
    $default_email = $this->config->get('no_account_email');
    $account_id = '';
    try {
      $us = $this->connector->getUserService();
      $account_id = $us->findUsers(['query' => $default_email])[0];
    }
    catch (\Exception $e) {
      $sensor_result->setStatus(SensorResultInterface::STATUS_CRITICAL);
      $sensor_result->setMessage('Tide Jira endpoint is not correctly configured...');
    }

    if (empty($account_id)) {
      $sensor_result->setStatus(SensorResultInterface::STATUS_CRITICAL);
      $sensor_result->setMessage(sprintf('Failed to lookup default account %s', $default_email));
    }
    else {
      $sensor_result->setStatus(SensorResultInterface::STATUS_OK);
      $sensor_result->setMessage('Tide Jira OK!');
    }

  }

}
