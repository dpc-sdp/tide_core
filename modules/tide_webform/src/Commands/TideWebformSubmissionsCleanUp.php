<?php

namespace Drupal\tide_webform\Commands;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drush\Commands\DrushCommands;
use Drush\Drush;
use Respect\Validation\Validator as v;

/**
 * Drush command.
 */
class TideWebformSubmissionsCleanUp extends DrushCommands {

  /**
   * Clean up webform submissions based on the specified conditions.
   *
   * @param string $webform_id
   *   The webform ID.
   * @param string|int $date_param
   *   The date string or number of days.
   * @param string $end_date
   *   Optional end date for date range.
   *
   * @command tide_webform:cleanup_submissions
   * @aliases tsc
   * @usage tide_webform:cleanup_submissions tide_webform_content_rating 07-May-2024
   *   Clean up submissions for 'tide_webform_content_rating' on '07-May-2024'
   * @usage tide_webform:cleanup_submissions tide_webform_content_rating 07-May-2024 01-May-2024
   *   Clean up submissions between '01-May-2024' and '07-May-2024'
   * @usage tide_webform:cleanup_submissions tide_webform_content_rating 30
   *   Clean up submissions older than 30 days
   */
  public function cleanupSubmissions($webform_id, $date_param = NULL, $end_date = NULL) {
    $webform = Webform::load($webform_id);
    if (!$webform) {
      Drush::output()->writeln("Webform with ID $webform_id does not exist.");
      return;
    }

    // Check if date parameter is provided.
    if (is_null($date_param)) {
      Drush::output()->writeln("Missing date parameter. Please provide a date, date range, or number of days.");
      return;
    }

    // Case 1: A numeric value representing days ago.
    if (is_numeric($date_param)) {
      $days = (int) $date_param;
      $cutoff_date = new DrupalDateTime();
      $cutoff_date->modify("-$days days");
      $cutoff_date->setTime(0, 0, 0);

      $query = \Drupal::entityQuery('webform_submission')
        ->condition('webform_id', $webform_id)
        ->condition('created', $cutoff_date->getTimestamp(), '<')
        ->accessCheck(FALSE)
        ->range(0, 100);
    }
    // Case 2: Two dates provided for a range.
    elseif (!is_null($end_date)) {
      // Validate date formats - use 'd-M-Y' format for DD-MMM-YYYY (e.g.,
      // 07-May-2024)
      if (!$this->isValidDate($date_param) || !$this->isValidDate($end_date)) {
        Drush::output()->writeln("Invalid date format. Please provide dates in the format 'DD-MMM-YYYY' (e.g., '07-May-2024').");
        return;
      }

      try {
        $date1 = new DrupalDateTime($date_param);
        $date2 = new DrupalDateTime($end_date);

        // Determine which date is earlier.
        if ($date1 < $date2) {
          $date_start = $date1;
          $date_end = $date2;
        }
        else {
          $date_start = $date2;
          $date_end = $date1;
        }

        $date_start->setTime(0, 0, 0);
        $date_end->setTime(23, 59, 59);

        $query = \Drupal::entityQuery('webform_submission')
          ->condition('webform_id', $webform_id)
          ->condition('created', $date_start->getTimestamp(), '>=')
          ->condition('created', $date_end->getTimestamp(), '<=')
          ->accessCheck(FALSE)
          ->range(0, 100);
      }
      catch (\Exception $e) {
        Drush::output()->writeln("Error processing dates: " . $e->getMessage());
        return;
      }
    }
    // Case 3: Single date provided.
    else {
      // Validate date format - use 'd-M-Y' format for DD-MMM-YYYY
      // (e.g., 07-May-2024)
      if (!$this->isValidDate($date_param)) {
        Drush::output()->writeln("Invalid date format. Please provide a date in the format 'DD-MMM-YYYY' (e.g., '07-May-2024').");
        return;
      }

      try {
        $date = new DrupalDateTime($date_param);
        $date_start = clone $date;
        $date_start->setTime(0, 0, 0);
        $date_end = clone $date;
        $date_end->setTime(23, 59, 59);
        error_log($date_start->getTimestamp());
        error_log($date_end->getTimestamp());

        $query = \Drupal::entityQuery('webform_submission')
          ->condition('webform_id', $webform_id)
          ->condition('created', $date_start->getTimestamp(), '>=')
          ->condition('created', $date_end->getTimestamp(), '<=')
          ->accessCheck(FALSE)
          ->range(0, 100);
      }
      catch (\Exception $e) {
        Drush::output()->writeln("Error processing date: " . $e->getMessage());
        return;
      }
    }

    // Execute the query and process results.
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
        [$sid],
      ];
    }

    batch_set($batch);
    drush_backend_batch_process();
  }

  /**
   * Validates a date string in multiple acceptable formats.
   *
   * @param string $date
   *   The date string to validate.
   *
   * @return bool
   *   TRUE if the date is valid, FALSE otherwise.
   */
  private function isValidDate($date) {
    // Try with common formats that might be used.
    // 07-May-2024.
    return v::date('d-M-Y')->validate($date) ||
    // 7-May-2024
           v::date('j-M-Y')->validate($date) ||
    // 07-May-2024 (full month name)
           v::date('d-F-Y')->validate($date) ||
    // 7-May-2024 (full month name)
           v::date('j-F-Y')->validate($date) ||
    // 2024-05-07 (ISO format)
           v::date('Y-m-d')->validate($date);
  }

  /**
   * Batch operation callback for deleting a submission.
   */
  public static function deleteSubmission($sid, &$context) {
    $submission = WebformSubmission::load($sid);
    if ($submission) {
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
