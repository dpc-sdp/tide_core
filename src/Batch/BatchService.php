<?php

namespace Drupal\tide_core\Batch;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Defines a process and finish method for a batch.
 */
class BatchService implements BatchServiceInterface {

  use StringTranslationTrait;

  /**
   * Entity type service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;


  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $loggerChannel;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger factory.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, LoggerChannelFactoryInterface $loggerFactory) {
    $this->entityTypeManager = $entityTypeManager;
    $this->loggerChannel = $loggerFactory->get('tide_core');
  }

  /**
   * {@inheritdoc}
   */
  public function create(int $batchSize = 100, array $data = []): void {
    if (empty($data)) {
      $this->loggerChannel->notice('There is no data to process.');
    }
    else {
      $batch = new BatchBuilder();
      $batch->setTitle($this->t('Running batch process.'))
      ->setFinishCallback([self::class, 'batchFinished'])
      ->setInitMessage('Commencing')
      ->setProgressMessage('Processing...')
      ->setErrorMessage('An error occurred during processing.');

      // Create chunks of all items.
      $chunks = array_chunk($data, $batchSize);

      // Process each chunk in the array.
      foreach ($chunks as $id => $chunk) {
        $args = [
          $id,
          $chunk,
        ];
        $batch->addOperation([BatchService::class, 'batchProcess'], $args);
      }
      batch_set($batch->toArray());

      $this->loggerChannel->notice('Batch created.');
      drush_backend_batch_process();

      // Finish.
      $this->loggerChannel->notice('Batch operations end.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function batchProcess(int $batchId, array $chunk, array &$context): void {
    if (!isset($context['sandbox']['progress'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['max'] = 1000;
    }
    if (!isset($context['results']['updated'])) {
      $context['results']['updated'] = 0;
      $context['results']['skipped'] = 0;
      $context['results']['failed'] = 0;
      $context['results']['progress'] = 0;
      $context['results']['process'] = 'Batch processing completed';
    }

    // Keep track of progress.
    $context['results']['progress'] += count($chunk);

    // Message above progress bar.
    $context['message'] = t('Processing batch #@batch_id, batch size @batch_size for total @count items.', [
      '@batch_id' => number_format($batchId),
      '@batch_size' => number_format(count($chunk)),
      '@count' => number_format($context['sandbox']['max']),
    ]);

    foreach ($chunk as $dataProcessed) {
      $result = self::cleanRevisions($dataProcessed['revision_list']);
      switch ($result) {
        case 1:
          $context['results']['updated']++;
          break;

        case 0:
          $context['results']['skipped']++;
          break;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function batchFinished(bool $success, array $results, array $operations, string $elapsed): void {
    // Grab the messenger service, this will be needed if the batch was a
    // success or a failure.
    $messenger = \Drupal::messenger();
    if ($success) {
      // The success variable was true, which indicates that the batch process
      // was successful (i.e. no errors occurred).
      // Show success message to the user.
      $messenger->addMessage(t('@process processed @count, skipped @skipped, updated @updated, failed @failed in @elapsed.', [
        '@process' => $results['process'],
        '@count' => $results['progress'],
        '@skipped' => $results['skipped'],
        '@updated' => $results['updated'],
        '@failed' => $results['failed'],
        '@elapsed' => $elapsed,
      ]));
      // Log the batch success.
      \Drupal::logger('batch_form_example')->info(
        '@process processed @count, skipped @skipped, updated @updated, failed @failed in @elapsed.',
        [
          '@process' => $results['process'],
          '@count' => $results['progress'],
          '@skipped' => $results['skipped'],
          '@updated' => $results['updated'],
          '@failed' => $results['failed'],
          '@elapsed' => $elapsed,
        ]
      );
    } else {
      // An error occurred. $operations contains the operations that remained
      // unprocessed. Pick the last operation and report on what happened.
      $error_operation = reset($operations);
      if ($error_operation) {
        $message = t('An error occurred while processing %error_operation with arguments: @arguments', [
          '%error_operation' => print_r($error_operation[0]),
          '@arguments' => print_r($error_operation[1], TRUE),
        ]);
        $messenger->addError($message);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function cleanRevisions(array $revisionIds): int {
    $storage = \Drupal::entityTypeManager()->getStorage('node');
    /** @var \Drupal\Core\Entity\RevisionableStorageInterface $storage */
    if (!empty($revisionIds)) {
      if (count($revisionIds)) {
        return 2;
      }

      foreach ($revisionIds as $revisionId) {
        $storage->deleteRevision($revisionId);
      }

      return 1;
    }
    else {
      return 0;
    }
  }
}
