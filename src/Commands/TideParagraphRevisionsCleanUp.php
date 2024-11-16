<?php

namespace Drupal\tide_core\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drush\Commands\DrushCommands;
use Drupal\tide_core\Batch\BatchServiceInterface;

/**
 * Drush command.
 */
class TideParagraphRevisionsCleanUp extends DrushCommands {

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
   * The batch service.
   *
   * @var \Drupal\modulename\BatchServiceInterface
   */
  protected $batch;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   Logger service.
   * @param \Drupal\modulename\BatchServiceInterface $batch
   *   The batch service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, LoggerChannelFactoryInterface $loggerFactory, BatchServiceInterface $batch) {
    $this->entityTypeManager = $entityTypeManager;
    $this->loggerChannel = $loggerFactory->get('tide_core');
    $this->batch = $batch;
  }

  /**
   * Get node revisions per node.
   *
   * @return array
   *   A list of nodes with their corresponding node revisions.
   */
  public function getNodeRevisions(string $timeAgo) {
    $nodeRevisions = [];
    $storage = $this->entityTypeManager->getStorage('node');

    try {
      $query = $storage->getQuery()
      ->condition('status', '1')
      ->condition('created', $timeAgo, '<')
      ->range(0, 500)
      ->accessCheck(FALSE);
      $nids = $query->execute();

      if (!empty($nids)) {
        foreach($nids as $vid => $nid) {
          $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
          $revisionIds = $storage->revisionIds($node);
          $latestRevisionId = $storage->getLatestRevisionId($nid);
          $nodeRevisions[$nid] = [
            'revision_list' => $revisionIds,
            'current_revision' => $latestRevisionId,
          ];
        }
      }
    } catch (\Exception $e) {
      $this->output()->writeln($e);
      $this->loggerChannel->warning('Error found @e', ['@e' => $e]);
    }

    return $nodeRevisions;
  }

  /**
   * Delete paragraphs revisions.
   *
   * @command tide_core:paragraph-revisions-cleanup
   * @aliases dpr
   *
   * @usage tide_core:paragraph-revisions-cleanup --batch=150 --older='30 days'
   */
  public function deleteParagraphRevision(array $options = ['batch' => 10, 'older' => '30 days']) {
    $timeAgo = strtotime('-' . $options['older']);
    $data = $this->getNodeRevisions($timeAgo);
    if (!empty($data)) {
      foreach ($data as $key => $value) {
        foreach($value['revision_list'] as $krid => $rid) {
          if ($value['current_revision'] == $rid) {
            unset($data[$key]['revision_list'][$krid]);
          }
        }
        unset($data[$key]['current_revision']);
      }
    }

    $this->batch->create($options['batch'], $data);
  }
}
