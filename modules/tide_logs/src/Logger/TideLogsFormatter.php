<?php

namespace Drupal\tide_logs\Logger;

use Monolog\Formatter\JsonFormatter;

/**
 * Class TideLogsFormatter
 *
 * Add SumoLogic fields to the JSON formatter.
 *
 * @package Drupal\lagoon_logs\Logger
 */
class TideLogsFormatter extends JsonFormatter {

  /**
   * The host to be sent to SumoLogic.
   *
   * @var string
   */
  protected string $sourceHost;

  /**
   * The category to be sent to SumoLogic.
   *
   * @var string
   */
  protected string $sourceCategory;

  /**
   * Class constructor.
   */
  public function __construct($source_host, $source_category) {
    parent::__construct();
    $this->sourceHost = $source_host;
    $this->sourceCategory = $source_category;
  }

  /**
   * {@inheritDoc}
   */
  public function format(array $record): string {
    $record = json_decode(parent::format($record), TRUE);

    // Add the SumoLogic attributes.
    $record['source_host'] = $this->sourceHost;
    $record['source_category'] = $this->sourceCategory;

    return $this->toJson($record) . "\n";
  }

}
