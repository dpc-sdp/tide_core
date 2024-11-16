<?php

namespace Drupal\tide_core\Batch;

/**
 * Defines batch service interface.
 */
interface BatchServiceInterface {

  /**
   * Create batch.
   *
   * @param int $batchSize
   *   Batch size.
   * @param array $data
   *   The data to process.
   */
  public function create(int $batchSize, array $data): void;

  /**
   * Batch operation callback.
   *
   * @param int $batchId
   *   The batch ID.
   * @param array $batch
   *   Information about batch (items, size, total, ...).
   * @param array $context
   *   Batch context.
   */
  public static function batchProcess(int $batchId, array $batch, array &$context): void;

  /**
   * Handle batch completion.
   *
   * @param bool $success
   *   TRUE if all batch API tasks were completed successfully.
   * @param array $results
   *   An results array from the batch processing operations.
   * @param array $operations
   *   A list of the operations that had not been completed.
   * @param string $elapsed
   *   Batch.inc kindly provides the elapsed processing time in seconds.
   */
  public static function batchFinished(bool $success, array $results, array $operations, string $elapsed): void;

  /**
   * Delete node revisions.
   *
   * @param array $revisionIds
   *   The list of revision IDs.
   */
  public static function cleanRevisions(array $revisionIds): int;
}
