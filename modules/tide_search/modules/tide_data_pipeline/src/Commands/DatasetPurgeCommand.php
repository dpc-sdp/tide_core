<?php

namespace Drupal\tide_data_pipelines\Commands;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drush\Commands\DrushCommands;
use Elasticsearch\ClientBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 */
class DatasetPurgeCommand extends DrushCommands {

  /**
   * The configuration service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a DatasetPurgeCommand object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration service.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * Dataset Purge Indices.
   *
   * @param string $indices
   *   The name of indices on Elastic cloud to purge.
   * @param string $options
   *   The confirmation flag to run the command.
   *
   * @command dataset-purge
   * @aliases dpi
   * @usage dataset-purge ise
   * @option force-cleanup Automatically confirm the purge action.
   */
  public function datasetPurgeIndices($indices, $options = ['force-cleanup' => FALSE]) {
    // If the --yes (or -y) option is passed, skip the confirmation.
    if ($options['force-cleanup']) {
      // Proceed directly to purge the index.
      $this->purgeIndex($indices);
    }
    else {
      // Ask for confirmation before proceeding.
      $confirm = $this->confirm(
        'Are you sure you want to purge the index? This action cannot be undone.',
        FALSE
      );

      if ($confirm) {
        $this->purgeIndex($indices);
      }
      else {
        $this->output()->writeln('Index purge aborted.');
      }
    }
  }

  /**
   * Helper function to purge the given index.
   */
  private function purgeIndex($indices) {
    // Fetch active elastic config.
    $config = $this->configFactory->get('data_pipelines.dataset_destination.sdp_elasticsearch');
    $config_data = $config->get('destinationSettings');
    // Get the current 'url', 'username', and 'password' values.
    $current_url = $config_data['url'] ?? NULL;
    $username = $config_data['username'] ?? 'user';
    $password = $config_data['password'] ?? 'pass';
    try {
      if ($current_url && $username && $password) {
        $parsed_url = parse_url($current_url);
        $constructed_url = 'http://' . $username . ':' . $password . '@' . $parsed_url['host'];
        $hosts = [$constructed_url];
      }

      // Creating the Elasticsearch client.
      $client = ClientBuilder::create()
        ->setHosts($hosts)
        ->setSSLVerification(FALSE)
        ->build();

      // Purge the given index from Elasticsearch.
      $index_name = $config_data['prefix'] . $indices ?? '';
      $client->indices()->delete(['index' => $index_name]);
      $this->output()->writeln("Purged the provided index from Elasticsearch.");

    }
    catch (\Exception $e) {
      \Drupal::logger('dataset_purge')->error('Error creating Elasticsearch client: ' . $e->getMessage());
      throw new \Exception('Failed to connect to Elasticsearch: ' . $e->getMessage());
    }
  }

}
