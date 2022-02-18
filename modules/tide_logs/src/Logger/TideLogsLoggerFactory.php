<?php

namespace Drupal\tide_logs\Logger;

use GuzzleHttp\Client;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LogMessageParserInterface;
use Drupal\lagoon_logs\Logger\LagoonLogsLoggerFactory;

class TideLogsLoggerFactory {
  const TIDE_LOGS_DEFAULT_SUMOLOGIC_CATEGORY = 'sdp/dev/tide';

  public static function create(
    ConfigFactoryInterface $config,
    LogMessageParserInterface $parser,
    Client $http_client
  ) {
    return new TideLogsLogger(
      self::getSumoLogicCollectorCode($config),
      self::getSumoLogicCategory($config),
      self::getLogIdentifier($config),
      $parser,
      $http_client,
      self::getDebug($config)
    );
  }

  public static function getLogIdentifier(ConfigFactoryInterface $config) {
    $enabled = $config->get('tide_logs.settings')->get('enable');
    return $enabled ?
      implode('-', [
        getenv('LAGOON_PROJECT') ?: LagoonLogsLoggerFactory::LAGOON_LOGS_DEFAULT_LAGOON_PROJECT,
        getenv('LAGOON_GIT_SAFE_BRANCH') ?: LagoonLogsLoggerFactory::LAGOON_LOGS_DEFAULT_SAFE_BRANCH,
      ]) :
      FALSE;
  }

  public static function getSumoLogicCollectorCode(ConfigFactoryInterface $config) {
    $enabled = $config->get('tide_logs.settings')->get('enable');
    if (!$enabled) {
      return FALSE;
    }
    // Allow collector code to be specified via environment.
    $config_code = $config->get('tide_logs.settings')->get('sumologic_collector_code');
    return $config_code ?: getenv('SUMOLOGIC_COLLECTOR_CODE');
  }

  public static function getSumoLogicCategory(ConfigFactoryInterface $config) {
    $enabled = $config->get('tide_logs.settings')->get('enable');
    if (!$enabled) {
      return FALSE;
    }
    // Allow category to be specified via environment, otherwise default.
    $category = $config->get('tide_logs.settings')->get('sumologic_category');
    if (!$category) {
      $category = getenv('SUMOLOGIC_CATEGORY');
    }
    return $category ?: static::TIDE_LOGS_DEFAULT_SUMOLOGIC_CATEGORY;
  }

  public static function getDebug(ConfigFactoryInterface $config)
  {
    $enabled = $config->get('tide_logs.settings')->get('enable');
    if (!$enabled) {
      return FALSE;
    }
    return $config->get('tide_logs.settings')->get('debug');
  }

}
