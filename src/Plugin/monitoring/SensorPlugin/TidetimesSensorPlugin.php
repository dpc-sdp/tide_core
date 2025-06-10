<?php

namespace Drupal\tide_core\Plugin\monitoring\SensorPlugin;

use Drupal\monitoring\Result\SensorResultInterface;
use Drupal\monitoring\SensorPlugin\ExtendedInfoSensorPluginInterface;
use Drupal\monitoring\SensorPlugin\SensorPluginBase;

/**
 * Monitors the Section API connection.
 *
 * @SensorPlugin(
 *   id = "tide_times_sensor",
 *   addable = false,
 *   label = @Translation("Tide Times Sensor"),
 *   description = @Translation("Monitors tide modules."),
 * )
 */
class TidetimesSensorPlugin extends SensorPluginBase implements ExtendedInfoSensorPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function resultVerbose(SensorResultInterface $result) {
    return [
      '#type' => 'inline_template',
      '#template' => '
      <div class="system-info-collapsible">
        {% for section, data in sections %}
          <div class="collapsible-section">
            <h3">{{ section|replace({"_": " "})|title }}</h3>
            <div style="display: block;">
                <ul>
                  {% for key, value in data %}
                    <li><strong>{{ key }}:</strong> {{ value }}</li>
                  {% endfor %}
                </ul>
            </div>
          </div>
        {% endfor %}
      </div>
      
      <style>
        .system-info-collapsible h3 {
          cursor: pointer;
          background-color: #f0f0f0;
          padding: 10px;
          margin: 5px 0;
        }
        .system-info-collapsible h3:hover {
          background-color: #e0e0e0;
        }
        .system-info-collapsible ul {
          margin-left: 20px;
        }
      </style>
    ',
      '#context' => [
        'sections' => [
          'package_versions' => $this->getInfo()['package_versions'],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function runSensor(SensorResultInterface $sensor_result) {
    $sensor_result->setValue(json_encode($this->getInfo(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
  }

  /**
   * Returns package infomation for Verbose.
   */
  private function getInfo() {
    $sys_info = \Drupal::service('tide_core.system_info_service');
    $package_versions = [
      'drupal/core' => $sys_info->getPackageVersion('drupal/core'),
      'dpc-sdp/tide' => $sys_info->getPackageVersion('dpc-sdp/tide'),
      'dpc-sdp/tide_core' => $sys_info->getPackageVersion('dpc-sdp/tide_core'),
      'drush/drush' => $sys_info->getPackageVersion('drush/drush'),
      'php' => $sys_info->getPackageVersion('php'),
    ];
    $context = [
      'package_versions' => [],
    ];
    foreach ($package_versions as $package => $info) {
      $context['package_versions'][$package] = $info['version'] ?? 'Unknown';
    }
    return $context;
  }

}
