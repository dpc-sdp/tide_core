<?php

declare(strict_types=1);

namespace Drupal\Tests\tide_data_pipeline_json_endpoint\Kernel;

use DG\BypassFinals;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\data_pipelines\DatasetData;
use Drupal\data_pipelines\Entity\Dataset;
use Drupal\data_pipelines\Source\DatasetSourcePluginManager;
use Drupal\tide_data_pipeline_json_endpoint\Plugin\DatasetSource\JsonEndpointSource;

/**
 * Tests the JsonEndpointSource plugin's data extraction behaviour.
 *
 * @group tide_data_pipeline_json_endpoint
 *
 * @covers \Drupal\tide_data_pipeline_json_endpoint\Plugin\DatasetSource\JsonEndpointSource
 */
class JsonEndpointSourceKernelTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'options',
    'link',
    'file',
    'entity',
    'data_pipelines',
    'data_pipelines_test',
    'tide_data_pipeline_json_endpoint',
    'tide_data_pipeline_json_endpoint_test',
    'user',
    'system',
  ];

  protected string $privatePath;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->privatePath = $this->siteDirectory . '/private';
    mkdir($this->privatePath, 0777, TRUE);
    $this->setSetting('file_private_path', $this->privatePath);
    $this->installEntitySchema('user');
    $this->installEntitySchema('file');
    $this->installEntitySchema('data_pipelines');
    $this->installSchema('file', ['file_usage']);
    $this->setUpCurrentUser();
    BypassFinals::enable(FALSE);
  }

  /**
   * Returns the json_endpoint source plugin instance.
   */
  private function getPlugin(): JsonEndpointSource {
    $manager = \Drupal::service('plugin.manager.data_pipelines_source');
    assert($manager instanceof DatasetSourcePluginManager);
    $plugin = $manager->createInstance('json_endpoint');
    assert($plugin instanceof JsonEndpointSource);
    return $plugin;
  }

  /**
   * Creates an unsaved Dataset entity with the json_endpoint source.
   */
  private function createDataset(string $machine_name, array $extra = []): Dataset {
    $dataset = Dataset::create([
      'name' => $machine_name,
      'machine_name' => $machine_name,
      'source' => 'json_endpoint',
      'pipeline' => 'test_json_endpoint_pipeline',
    ] + $extra);
    assert($dataset instanceof Dataset);
    return $dataset;
  }

  /**
   * Writes a JSON payload to the private filesystem for a given machine name.
   */
  private function writePayload(string $machine_name, mixed $data): void {
    $storage_dir = $this->privatePath . '/' . JsonEndpointSource::STORAGE_DIR;
    if (!is_dir($storage_dir)) {
      mkdir($storage_dir, 0777, TRUE);
    }
    file_put_contents($storage_dir . '/' . $machine_name . '.json', json_encode($data));
  }

  /**
   * Tests that an array-of-objects payload yields one DatasetData per element.
   */
  public function testExtractArrayOfObjectsPayload(): void {
    $machine_name = 'test_array_objects';
    $this->writePayload($machine_name, [
      ['name' => 'Station A', 'suburb' => 'Carlton'],
      ['name' => 'Station B', 'suburb' => 'Fitzroy'],
    ]);

    $result = iterator_to_array($this->getPlugin()->extractDataFromDataSet($this->createDataset($machine_name)));

    $this->assertEquals([
      new DatasetData(['name' => 'Station A', 'suburb' => 'Carlton']),
      new DatasetData(['name' => 'Station B', 'suburb' => 'Fitzroy']),
    ], $result);
  }

  /**
   * Tests that a root JSON object yields a single DatasetData.
   */
  public function testExtractObjectPayload(): void {
    $machine_name = 'test_object';
    $this->writePayload($machine_name, ['key1' => 'value1', 'key2' => 'value2']);

    $result = iterator_to_array($this->getPlugin()->extractDataFromDataSet($this->createDataset($machine_name)));

    $this->assertEquals([
      new DatasetData(['key1' => 'value1', 'key2' => 'value2']),
    ], $result);
  }

  /**
   * Tests that an array of scalar values wraps each value in a DatasetData.
   */
  public function testExtractArrayOfScalarsPayload(): void {
    $machine_name = 'test_scalars';
    $this->writePayload($machine_name, ['alpha', 'beta', 'gamma']);

    $result = iterator_to_array($this->getPlugin()->extractDataFromDataSet($this->createDataset($machine_name)));

    $this->assertEquals([
      new DatasetData(['alpha']),
      new DatasetData(['beta']),
      new DatasetData(['gamma']),
    ], $result);
  }

  /**
   * Tests that the json_endpoint_path_to_data field applies a JSONPath filter.
   */
  public function testExtractWithJsonPath(): void {
    $machine_name = 'test_jsonpath';
    $this->writePayload($machine_name, [
      'meta' => ['total' => 2],
      'records' => [
        ['id' => 1, 'title' => 'First'],
        ['id' => 2, 'title' => 'Second'],
      ],
    ]);

    $dataset = $this->createDataset($machine_name, ['json_endpoint_path_to_data' => '$.records']);
    $result = iterator_to_array($this->getPlugin()->extractDataFromDataSet($dataset));

    $this->assertEquals([
      new DatasetData(['id' => 1, 'title' => 'First']),
      new DatasetData(['id' => 2, 'title' => 'Second']),
    ], $result);
  }

  /**
   * Tests that extraction returns empty when no payload file exists.
   */
  public function testExtractReturnsEmptyWhenNoFileExists(): void {
    $result = iterator_to_array(
      $this->getPlugin()->extractDataFromDataSet($this->createDataset('nonexistent_dataset'))
    );

    $this->assertEmpty($result);
  }

  /**
   * Tests that extraction returns empty when the stored file contains invalid JSON.
   */
  public function testExtractReturnsEmptyOnInvalidJson(): void {
    $machine_name = 'test_invalid_json';
    $storage_dir = $this->privatePath . '/' . JsonEndpointSource::STORAGE_DIR;
    mkdir($storage_dir, 0777, TRUE);
    file_put_contents($storage_dir . '/' . $machine_name . '.json', '{not: valid json{{{');

    $result = iterator_to_array($this->getPlugin()->extractDataFromDataSet($this->createDataset($machine_name)));

    $this->assertEmpty($result);
  }

  /**
   * Tests that a single-record payload is processed as one DatasetData.
   */
  public function testExtractSingleRecordPayload(): void {
    $machine_name = 'test_single';
    $this->writePayload($machine_name, [['station' => 'CBD', 'phone' => '9999-9999']]);

    $result = iterator_to_array($this->getPlugin()->extractDataFromDataSet($this->createDataset($machine_name)));

    $this->assertCount(1, $result);
    $this->assertEquals(new DatasetData(['station' => 'CBD', 'phone' => '9999-9999']), $result[0]);
  }

}
