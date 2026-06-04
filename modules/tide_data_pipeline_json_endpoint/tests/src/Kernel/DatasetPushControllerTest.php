<?php

declare(strict_types=1);

namespace Drupal\Tests\tide_data_pipeline_json_endpoint\Kernel;

use DG\BypassFinals;
use Drupal\data_pipelines\Entity\Dataset;
use Drupal\data_pipelines\Entity\Destination;
use Drupal\data_pipelines\Form\DatasetBatchOperations;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\tide_data_pipeline_json_endpoint\Controller\DatasetPushController;
use Drupal\tide_data_pipeline_json_endpoint\Plugin\DatasetSource\JsonEndpointSource;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the DatasetPushController business logic.
 *
 * These tests call the controller method directly, bypassing the routing layer.
 * OAuth bearer token authentication and the _permission access check are
 * enforced by the routing system and should be covered by functional tests.
 *
 * @group tide_data_pipeline_json_endpoint
 *
 * @covers \Drupal\tide_data_pipeline_json_endpoint\Controller\DatasetPushController
 */
class DatasetPushControllerTest extends KernelTestBase {

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

  /**
   * Absolute path to the private filesystem used during tests.
   */
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
   * Returns an instantiated controller with the test container.
   */
  private function controller(): DatasetPushController {
    return DatasetPushController::create($this->container);
  }

  /**
   * Builds a POST request with JSON content type.
   */
  private function jsonRequest(mixed $body): Request {
    return Request::create(
      '/',
      'POST',
      content: is_string($body) ? $body : json_encode($body),
      server: ['CONTENT_TYPE' => 'application/json'],
    );
  }

  /**
   * Creates and saves a published json_endpoint dataset with a state dest.
   */
  private function createPublishedDataset(string $machine_name): Dataset {
    $destination = Destination::create([
      'id' => $machine_name . '_dest',
      'label' => 'Test destination',
      'destination' => 'state',
      'destinationSettings' => ['state_key' => 'test_push_result'],
    ]);
    $destination->save();

    $dataset = Dataset::create([
      'name' => $machine_name,
      'machine_name' => $machine_name,
      'source' => 'json_endpoint',
      'pipeline' => 'test_json_endpoint_pipeline',
      'published' => TRUE,
      'destinations' => [$destination],
    ]);
    $dataset->save();
    assert($dataset instanceof Dataset);
    return $dataset;
  }

  /**
   * Tests that a valid push returns 200 and processes the dataset.
   */
  public function testPushProcessesDatasetAndReturns200(): void {
    $machine_name = 'push_success';
    $this->createPublishedDataset($machine_name);
    $payload = [['suburb' => 'Carlton'], ['suburb' => 'Fitzroy']];

    $response = $this->controller()->push($this->jsonRequest($payload), $machine_name);

    $this->assertSame(200, $response->getStatusCode());
    $body = json_decode($response->getContent(), TRUE);
    $this->assertSame('processed', $body['status']);
    $this->assertSame($machine_name, $body['machine_name']);
  }

  /**
   * Tests that ?save_only=1 saves the file but skips reprocessing.
   */
  public function testSaveOnlyReturnsSavedStatusWithoutProcessing(): void {
    $machine_name = 'push_save_only';
    $this->createPublishedDataset($machine_name);
    $payload = [['id' => 1]];

    $request = $this->jsonRequest($payload);
    $request->query->set('save_only', '1');
    $response = $this->controller()->push($request, $machine_name);

    $this->assertSame(200, $response->getStatusCode());
    $body = json_decode($response->getContent(), TRUE);
    $this->assertSame('saved', $body['status']);

    // Confirm the file was written.
    $file_path = $this->privatePath . '/' . JsonEndpointSource::STORAGE_DIR . '/' . $machine_name . '.json';
    $this->assertFileExists($file_path);
    $this->assertSame($payload, json_decode(file_get_contents($file_path), TRUE));

    // Confirm no processing occurred (state destination was never written to).
    $this->assertNull(\Drupal::state()->get('test_push_result'));
  }

  /**
   * Tests that a save_only push followed by manual reprocessing works.
   */
  public function testSaveOnlyThenManualReprocessProducesExpectedData(): void {
    $machine_name = 'push_then_reprocess';
    $dataset = $this->createPublishedDataset($machine_name);
    $payload = [['name' => 'Station A'], ['name' => 'Station B']];

    // Step 1: save only.
    $request = $this->jsonRequest($payload);
    $request->query->set('save_only', '1');
    $this->controller()->push($request, $machine_name);

    // Step 2: trigger reprocessing via the batch operations directly.
    $dataset_id = (int) $dataset->id();
    $context = ['sandbox' => [], 'finished' => 0, 'results' => [], 'message' => ''];
    do {
      DatasetBatchOperations::operationQueueItem($dataset_id, $context);
    } while ($context['finished'] !== 1);

    $context = ['sandbox' => [], 'finished' => 0, 'results' => [], 'message' => ''];
    do {
      DatasetBatchOperations::operationProcess($dataset_id, $context);
    } while ($context['finished'] !== 1);

    $stored = \Drupal::state()->get('test_push_result');
    $this->assertNotNull($stored);
  }

  /**
   * Tests that a non-JSON Content-Type returns 415.
   */
  public function testPushReturns415ForNonJsonContentType(): void {
    $machine_name = 'push_415';
    $this->createPublishedDataset($machine_name);

    $request = Request::create('/', 'POST', content: 'some data', server: ['CONTENT_TYPE' => 'text/plain']);
    $response = $this->controller()->push($request, $machine_name);

    $this->assertSame(415, $response->getStatusCode());
    $body = json_decode($response->getContent(), TRUE);
    $this->assertArrayHasKey('error', $body);
  }

  /**
   * Tests that a malformed JSON body returns 400.
   */
  public function testPushReturns400ForInvalidJson(): void {
    $machine_name = 'push_400';
    $this->createPublishedDataset($machine_name);

    $response = $this->controller()->push(
      Request::create('/', 'POST', content: '{not: valid json', server: ['CONTENT_TYPE' => 'application/json']),
      $machine_name
    );

    $this->assertSame(400, $response->getStatusCode());
    $body = json_decode($response->getContent(), TRUE);
    $this->assertStringContainsString('Invalid JSON', $body['error']);
  }

  /**
   * Tests that pushing to an unknown machine name returns 404.
   */
  public function testPushReturns404ForUnknownDataset(): void {
    $response = $this->controller()->push(
      $this->jsonRequest(['data' => 'value']),
      'this_dataset_does_not_exist'
    );

    $this->assertSame(404, $response->getStatusCode());
    $body = json_decode($response->getContent(), TRUE);
    $this->assertArrayHasKey('error', $body);
  }

  /**
   * Tests that pushing to an unpublished dataset returns 422.
   */
  public function testPushReturns422ForUnpublishedDataset(): void {
    $machine_name = 'push_unpublished';
    $dataset = Dataset::create([
      'name' => $machine_name,
      'machine_name' => $machine_name,
      'source' => 'json_endpoint',
      'pipeline' => 'test_json_endpoint_pipeline',
      'published' => FALSE,
    ]);
    $dataset->save();

    $response = $this->controller()->push($this->jsonRequest(['data' => 'value']), $machine_name);

    $this->assertSame(422, $response->getStatusCode());
    $body = json_decode($response->getContent(), TRUE);
    $this->assertArrayHasKey('error', $body);
  }

  /**
   * Tests that pushing to a non-json_endpoint source dataset returns 404.
   *
   * A dataset with a different source type must not be reachable via this
   * endpoint even if the machine name matches.
   */
  public function testPushReturns404ForDatasetWithDifferentSource(): void {
    $name = mb_strtolower($this->randomMachineName());
    $dataset = Dataset::create([
      'name' => $name,
      'machine_name' => $name,
      'source' => 'csv:text',
      'pipeline' => 'test_pipeline_1',
      'csv_text' => "a,b\n1,2",
    ]);
    $dataset->save();

    $response = $this->controller()->push($this->jsonRequest(['data' => 'value']), $name);

    $this->assertSame(404, $response->getStatusCode());
  }

  /**
   * Tests that a successful push overwrites any previously stored payload.
   */
  public function testPushOverwritesPreviousPayload(): void {
    $machine_name = 'push_overwrite';
    $this->createPublishedDataset($machine_name);
    $file_path = $this->privatePath . '/' . JsonEndpointSource::STORAGE_DIR . '/' . $machine_name . '.json';

    $request1 = $this->jsonRequest([['v' => 'first']]);
    $request1->query->set('save_only', '1');
    $this->controller()->push($request1, $machine_name);
    $this->assertSame([['v' => 'first']], json_decode(file_get_contents($file_path), TRUE));

    $request2 = $this->jsonRequest([['v' => 'second']]);
    $request2->query->set('save_only', '1');
    $this->controller()->push($request2, $machine_name);
    $this->assertSame([['v' => 'second']], json_decode(file_get_contents($file_path), TRUE));
  }

}
