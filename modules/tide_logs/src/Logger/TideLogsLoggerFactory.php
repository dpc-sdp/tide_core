<?php

namespace Drupal\tide_logs\Logger;

use GuzzleHttp\Client;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LogMessageParserInterface;

/**
 * Defines a logger factory for the SumoLogic channel.
 */
class TideLogsLoggerFactory {

  /**
   * Creates an instance of the SumoLogic logger.
   *
   * This method is called in tide_logs.services.yml to initialise the logger.
   *
   * @param ConfigFactoryInterface $config
   *   The config service.
   * @param LogMessageParserInterface $parser
   *   The log message parser service.
   * @param Client $http_client
   *   The http client service.
   *
   * @return TideLogsLogger
   *    The logger instance that was created.
   */
  public static function create(
    ConfigFactoryInterface $config,
    LogMessageParserInterface $parser,
    Client $http_client
  ) {
    return new TideLogsLogger(
      $parser,
      $http_client,
      $config->get('tide_logs.settings')
    );
  }

}
