<?php

namespace Drupal\tide_inactive_users_management\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\user\Entity\User;

/**
 * A queue work on CRON run.
 *
 * @QueueWorker(
 *   id = "tide_inactive_users_management_queue",
 *   title = @Translation("Inactive users records"),
 *   cron = {"time" = 60}
 * )
 */
class InactiveRecordsQueue extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $user = User::load($data->uid);
    if ($user) {
      if (!\Drupal::keyValue('tide_inactive_users_management')
        ->get($user->id())) {
        tide_inactive_users_management_sending_email($user);
        $current_time = time();
        $block_time = strtotime('+1 month', $current_time);
        \Drupal::keyValue('tide_inactive_users_management')
          ->set($user->id(), $block_time);
      }
    }
  }

}
