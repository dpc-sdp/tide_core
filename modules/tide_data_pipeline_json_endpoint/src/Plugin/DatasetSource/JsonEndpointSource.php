<?php

declare(strict_types=1);

namespace Drupal\tide_data_pipeline_json_endpoint\Plugin\DatasetSource;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\data_pipelines\Attribute\DatasetSource;
use Drupal\data_pipelines\DatasetData;
use Drupal\data_pipelines\Entity\DatasetInterface;
use Drupal\data_pipelines\Source\DatasetSourceInterface;
use Drupal\data_pipelines\Traits\FieldValueTrait;
use Drupal\data_pipelines\Traits\JsonPathTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a JSON endpoint dataset source.
 *
 * Datasets using this source receive their data via an authenticated POST
 * request to /api/datasets/{machine_name}/push. The payload is saved to the
 * private filesystem and used as the source on each processing run.
 */
#[DatasetSource(
  id: 'json_endpoint',
  label: new TranslatableMarkup('JSON Endpoint'),
  description: new TranslatableMarkup('Receives JSON data pushed via an authenticated POST endpoint.'),
)]
class JsonEndpointSource extends PluginBase implements DatasetSourceInterface, ContainerFactoryPluginInterface {

  use FieldValueTrait;
  use JsonPathTrait;

  // DatasetSourceBase.__construct is final, so this plugin extends PluginBase
  // directly and implements DatasetSourceInterface without a source resource.

  const STORAGE_SCHEME = 'private';
  const STORAGE_DIR = 'data_pipelines_json_endpoint';

  public function __construct(
    array $configuration,
    string $plugin_id,
    array $plugin_definition,
    private readonly FileSystemInterface $fileSystem,
    private readonly LoggerChannelInterface $logger,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('file_system'),
      $container->get('logger.channel.data_pipelines'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions(): array {
    return [
      'json_endpoint_path_to_data' => BaseFieldDefinition::create('string')
        ->setLabel(new TranslatableMarkup('Path to data'))
        ->setDescription(new TranslatableMarkup("Optional <a href='@link' target='_blank'>JSON path</a> expression to select a sub-array from the received payload.", [
          '@link' => 'https://github.com/SoftCreatR/JSONPath',
        ]))
        ->setSetting('max_length', 255)
        ->setDisplayOptions('form', ['type' => 'string_textfield'])
        ->setPropertyConstraints('value', ['JsonPath' => []]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function extractDataFromDataSet(DatasetInterface $dataset): \Generator {
    $machine_name = $dataset->get('machine_name')->value;
    $file_uri = static::buildStorageUri($machine_name);
    $real_path = $this->fileSystem->realpath($file_uri);

    if (!$real_path || !file_exists($real_path)) {
      $this->logger->warning('No payload file found for dataset @name at @uri.', [
        '@name' => $machine_name,
        '@uri' => $file_uri,
      ]);
      return;
    }

    try {
      $json = json_decode(file_get_contents($real_path), TRUE, 512, JSON_THROW_ON_ERROR);

      $json_path = self::getFieldValue($dataset, 'json_endpoint_path_to_data');
      if (!empty($json_path)) {
        $json = $this->createJsonPath($json)->find($json_path)->getData();
      }

      if (is_array($json) && array_values($json) === $json) {
        foreach ($json as $record) {
          yield new DatasetData(is_array($record) ? $record : [$record]);
        }
      }
      else {
        yield new DatasetData($json);
      }
    }
    catch (\Exception $e) {
      $this->logger->critical('Failed to process JSON payload for dataset @name: @message', [
        '@name' => $machine_name,
        '@message' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Returns the private filesystem URI for a dataset's stored JSON payload.
   */
  public static function buildStorageUri(string $machine_name): string {
    return sprintf('%s://%s/%s.json', static::STORAGE_SCHEME, static::STORAGE_DIR, $machine_name);
  }

}
