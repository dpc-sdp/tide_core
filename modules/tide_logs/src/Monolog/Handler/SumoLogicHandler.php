<?php declare(strict_types=1);

namespace Drupal\tide_logs\Monolog\Handler;

use Monolog\Logger;
use GuzzleHttp\Client;
use Monolog\Formatter\JsonFormatter;
use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\AbstractProcessingHandler;

/**
 * Sends errors to SumoLogic.
 */
class SumoLogicHandler extends AbstractProcessingHandler {

  protected const HOST = 'collectors.au.sumologic.com';
  protected const ENDPOINT = 'receiver/v1/http';

  /**
   * Default SumoLogic category in case none has been provided.
   */
  public const DEFAULT_CATEGORY = 'sdp/dev/tide';

  /**
   * The GuzzleHttp Client.
   *
   * @var Client
   */
  protected Client $client;

  /**
   * The SumoLogic Collector Code to be used for pushing logs.
   *
   * If empty, the logs won't be pushed to SumoLogic since there won't even be
   * a valid URL, which is comprised of this code.
   *
   * @var string
   *
   * @see https://help.sumologic.com/03Send-Data/Sources/02Sources-for-Hosted-Collectors/HTTP-Source/Upload-Data-to-an-HTTP-Source#upload-log-data-with-a-post-request
   */
  protected $collectorCode;

  /**
   * The SumoLogic Category, to be used in the X-Sumo-Category header.
   *
   * @var string
   *
   * @see https://help.sumologic.com/03Send-Data/Sources/02Sources-for-Hosted-Collectors/HTTP-Source/Upload-Data-to-an-HTTP-Source#supported-http-headers
   */
  protected $category;

  /**
   * The host to set for each log record, to be used in X-Sumo-Host header.
   *
   * @var string
   */
  protected $host;

  /**
   * Constructs a SumoLogicHandler object.
   *
   * @param Client $http_client
   *   The http client service.
   * @param string $collector_code
   *   The collector code for constructing the url.
   * @param string $category
   *   The value for X-Sumo-Category header.
   * @param string $host
   *   The value for X-Sumo-Host header.
   */
  public function __construct(
    Client $client,
    string $collector_code,
    string $category,
    string $host = "",
    $level = Logger::DEBUG,
    bool $bubble = true
  ) {
    $this->client = $client;
    $this->collectorCode = $collector_code;
    $this->host = $host;
    $this->category = $category;
    parent::__construct($level, $bubble);
  }

  /**
   * {@inheritdoc}
   */
  protected function write(array $record): void {
    $url = sprintf("https://%s/%s/%s/", static::HOST, static::ENDPOINT, $this->collectorCode);

    $headers = ['X-Sumo-Category' => $this->category ?: static::DEFAULT_CATEGORY];
    if ($this->host) {
      $headers['X-Sumo-Host'] = $this->host;
    }

    $this->client->post($url, [
      'headers' => $headers,
      'json' => $record,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultFormatter(): FormatterInterface {
    return new JsonFormatter();
  }
}
