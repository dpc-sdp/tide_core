<?php

declare(strict_types=1);

namespace Drupal\tide_data_pipeline_json_endpoint\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\File\FileSystemInterface;
use Drupal\data_pipelines\Form\DatasetBatchOperations;
use Drupal\tide_data_pipeline_json_endpoint\Plugin\DatasetSource\JsonEndpointSource;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Handles authenticated JSON payload pushes to dataset endpoints.
 *
 * POST /api/datasets/{machine_name}/push
 *   Saves the payload and reprocesses the dataset synchronously.
 *
 * POST /api/datasets/{machine_name}/push?save_only=1
 *   Saves the payload only; reprocessing must be triggered manually.
 */
class DatasetPushController extends ControllerBase {

  public function __construct(private readonly FileSystemInterface $fileSystem) {}

  public static function create(ContainerInterface $container): static {
    return new static($container->get('file_system'));
  }

  public function push(Request $request, string $machine_name): JsonResponse {
    if (!str_contains($request->headers->get('Content-Type', ''), 'application/json')) {
      return new JsonResponse(['error' => 'Content-Type must be application/json.'], 415);
    }

    $datasets = $this->entityTypeManager()->getStorage('data_pipelines')
      ->loadByProperties(['machine_name' => $machine_name, 'source' => 'json_endpoint']);

    if (empty($datasets)) {
      return new JsonResponse(['error' => 'Dataset not found.'], 404);
    }

    /** @var \Drupal\data_pipelines\Entity\DatasetInterface $dataset */
    $dataset = reset($datasets);

    if (!$dataset->isPublished()) {
      return new JsonResponse(['error' => 'Dataset is not published.'], 422);
    }

    $body = $request->getContent();
    try {
      json_decode($body, flags: JSON_THROW_ON_ERROR);
    }
    catch (\JsonException $e) {
      return new JsonResponse(['error' => 'Invalid JSON: ' . $e->getMessage()], 400);
    }

    $directory_uri = sprintf('%s://%s', JsonEndpointSource::STORAGE_SCHEME, JsonEndpointSource::STORAGE_DIR);
    $this->fileSystem->prepareDirectory($directory_uri, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
    $this->fileSystem->saveData($body, JsonEndpointSource::buildStorageUri($machine_name), FileSystemInterface::EXISTS_REPLACE);

    if ($request->query->getBoolean('save_only')) {
      return new JsonResponse(['status' => 'saved', 'machine_name' => $machine_name]);
    }

    $dataset_id = (int) $dataset->id();

    $context = ['sandbox' => [], 'finished' => 0, 'results' => [], 'message' => ''];
    do {
      DatasetBatchOperations::operationQueueItem($dataset_id, $context);
    } while ($context['finished'] < 1);

    $context = ['sandbox' => [], 'finished' => 0, 'results' => [], 'message' => ''];
    do {
      DatasetBatchOperations::operationProcess($dataset_id, $context);
    } while ($context['finished'] < 1);

    return new JsonResponse(['status' => 'processed', 'machine_name' => $machine_name]);
  }

}
