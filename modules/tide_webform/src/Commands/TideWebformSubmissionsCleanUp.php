<?php

namespace Drupal\tide_webform\Commands;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drush\Commands\DrushCommands;
use Drush\Drush;

/**
 * Drush command.
 */
class TideWebformSubmissionsCleanUp extends DrushCommands {

  /**
   * Clean up webform submissions based on the specified conditions.
   *
   * @param string $webform_id
   *   The webform ID.
   * @param string $date_string
   *   The date string.
   * @param string $webform_field
   *   The webform field to check.
   *
   * @command tide_webform:cleanup_submissions
   * @aliases twc
   * @usage tide_webform:cleanup_submissions tide_webform_content_rating 07-May-2024 was_this_page_helpful
   *   Clean up submissions for 'tide_webform_content_rating' on '07-May-2024'
   * where 'was_this_page_helpful' field is empty or does not exist.
   */
  public function cleanupSubmissions($webform_id, $date_string, $webform_field) {
    $webform = Webform::load($webform_id);
    if (!$webform) {
      Drush::output()->writeln("Webform with ID $webform_id does not exist.");
      return;
    }

    if (!$webform->getElement($webform_field) || !$webform->getElement($webform_field)['#required']) {
      Drush::output()->writeln("The field $webform_field is not a required field.");
      return;
    }

    $date_start = new DrupalDateTime($date_string);
    $date_start->setTime(0, 0, 0);
    $date_end = new DrupalDateTime($date_string);
    $date_end->setTime(23, 59, 59);

    $query = \Drupal::entityQuery('webform_submission')
      ->condition('webform_id', $webform_id)
      ->condition('created', $date_start->getTimestamp(), '>=')
      ->condition('created', $date_end->getTimestamp(), '<=')
      ->accessCheck(FALSE);
    $sids = $query->execute();

    if (empty($sids)) {
      Drush::output()->writeln("No submissions found for the specified criteria.");
      return;
    }

    $batch = [
      'title' => t('Deleting submissions...'),
      'operations' => [],
      'finished' => [get_class($this), 'cleanupSubmissionsFinished'],
    ];

    foreach ($sids as $sid) {
      $batch['operations'][] = [
        [get_class($this), 'deleteSubmission'],
        [$sid, $webform_field],
      ];
    }

    batch_set($batch);
    drush_backend_batch_process();
  }

  /**
   * Batch operation callback for deleting a submission.
   */
  public static function deleteSubmission($sid, $webform_field, &$context) {
    $submission = WebformSubmission::load($sid);
    if ($submission && ($submission->getElementData($webform_field) === '' || is_null($submission->getElementData($webform_field)))) {
      $submission->delete();
      $context['results']['deleted'][] = $sid;
    }
    else {
      $context['results']['skipped'][] = $sid;
    }
  }

  /**
   * Finished callback for the batch.
   */
  public static function cleanupSubmissionsFinished($success, $results, $operations) {
    if ($success) {
      Drush::output()->writeln('Finished processing.');
    }
    else {
      Drush::output()->writeln("Finished with errors.");
    }
  }

}
