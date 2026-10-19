<?php

declare(strict_types=1);

namespace Drupal\tide_data_pipeline_json_endpoint_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\State\StateInterface;
use Drupal\data_pipelines\Entity\DatasetInterface;

/**
 * Tests Drupal 11 object-oriented implementations of the dataset hook.
 */
class DatasetProcessedHooks {

  public function __construct(protected readonly StateInterface $state) {}

  /**
   * Records calls discovered through Drupal 11 hook attributes.
   */
  #[Hook('tide_data_pipeline_json_endpoint_dataset_processed')]
  public function datasetProcessed(DatasetInterface $dataset, string $machine_name): void {
    $previous = $this->state->get('tide_data_pipeline_json_endpoint_test.dataset_processed_oop', []);
    $this->state->set('tide_data_pipeline_json_endpoint_test.dataset_processed_oop', [
      'dataset_id' => $dataset->id(),
      'machine_name' => $machine_name,
      'invocations' => ($previous['invocations'] ?? 0) + 1,
    ]);
  }

}
