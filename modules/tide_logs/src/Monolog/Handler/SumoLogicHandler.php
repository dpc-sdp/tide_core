<?php declare(strict_types=1);

namespace Drupal\tide_logs\Monolog\Handler;

use Monolog\Logger;
use GuzzleHttp\Client;
use Monolog\Formatter\JsonFormatter;
use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\AbstractProcessingHandler;

class SumoLogicHandler extends AbstractProcessingHandler {

  protected const HOST = 'collectors.au.sumologic.com';
  protected const ENDPOINT = 'receiver/v1/http';

  /**
   * The GuzzleHttp Client.
   */
  protected Client $client;

  /** @var string */
  protected $collectorCode;

  /** @var string */
  protected $host;

  /** @var string */
  protected $category;

  /**
   * @param string $collector_code
   *   Collector code supplied by Sumo Logic.
   */
  public function __construct(string $collector_code, string $category, string $host = "", $level = Logger::DEBUG, bool $bubble = true)
  {
    $this->collectorCode = $collector_code;
    $this->host = $host;
    $this->category = $category;
    parent::__construct($level, $bubble);
  }

  /**
   * Sets the GuzzleHttp Client.
   */
  public function setClient(Client $client)
  {
    $this->client = $client;
  }

  protected function write(array $record): void
  {
    $url = sprintf("https://%s/%s/%s/", static::HOST, static::ENDPOINT, $this->collectorCode);

    $headers = ['X-Sumo-Category' => $this->category];
    if ($this->host) {
      $headers['X-Sumo-Host'] = $this->host;
    }

    $this->client->post($url, [
      'headers' => $headers,
      'json' => $record,
    ]);
  }

  protected function getDefaultFormatter(): FormatterInterface
  {
    return new JsonFormatter();
  }
}
