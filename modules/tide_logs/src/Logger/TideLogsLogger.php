<?php

namespace Drupal\tide_logs\Logger;

use Monolog\Logger;
use GuzzleHttp\Client;
use Drupal\lagoon_logs\LagoonLogsLogProcessor;
use Drupal\lagoon_logs\Logger\LagoonLogsLogger;
use Drupal\Core\Logger\LogMessageParserInterface;
use Drupal\tide_logs\Monolog\Handler\SumoLogicHandler;

class TideLogsLogger extends LagoonLogsLogger {

  const TIDE_LOGS_MONOLOG_CHANNEL_NAME = 'TideLogs';

  protected $sumoLogicCollectorCode;
  protected $sumoLogicCategory;
  protected Client $httpClient;
  protected $showDebug;

  public function __construct(
    $sumologic_collector_code,
    $sumologic_category,
    $logFullIdentifier,
    LogMessageParserInterface $parser,
    Client $http_client,
    $debug = FALSE
  ) {
    $this->sumoLogicCollectorCode = $sumologic_collector_code;
    $this->sumoLogicCategory = $sumologic_category;
    $this->logFullIdentifier = $logFullIdentifier;
    $this->parser = $parser;
    $this->httpClient = $http_client;
    $this->showDebug = $debug;
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = []) {
    if ($this->showDebug) {
      \Drupal::messenger()->addMessage(t(
        'Code: @code; Cat: @cat',
        [
          '@code' => $this->sumoLogicCollectorCode,
          '@cat' => $this->sumoLogicCategory,
        ]
      ));
    }

    if (empty($this->logFullIdentifier) || empty($this->sumoLogicCollectorCode)) {
      return;
    }

    global $base_url;

    $logger = new Logger(
      !empty($context['channel']) ? $context['channel'] : self::TIDE_LOGS_MONOLOG_CHANNEL_NAME
    );

    $sumoLogicHandler = new SumoLogicHandler(
      $this->sumoLogicCollectorCode,
      $this->sumoLogicCategory,
      $this->logFullIdentifier
    );
    $sumoLogicHandler->setClient($this->httpClient);

    $logger->pushHandler($sumoLogicHandler);

    $message_placeholders = $this->parser->parseMessagePlaceholders(
      $message,
      $context
    );
    $message = strip_tags(
      empty($message_placeholders) ? $message : strtr(
        $message,
        $message_placeholders
      )
    );

    $processorData = $this->transformDataForProcessor(
      $level,
      $message,
      $context,
      $base_url
    );

    $logger->pushProcessor(new LagoonLogsLogProcessor($processorData));

    try {
      $logger->log($this->mapRFCtoMonologLevels($level), $message);
    } catch (\Exception $exception) {
      if ($this->showDebug) {
        \Drupal::messenger()->addMessage(t(
          'Error logging to SumoLogic: @error',
          ['@error' => $exception->getMessage()]
        ));
      }
    }
  }

}
