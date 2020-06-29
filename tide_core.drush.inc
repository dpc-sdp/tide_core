<?php

/**
 * @file
 * Drush integration for the tide_core module.
 */

/**
 * Implements hook_drush_help().
 */
function tide_core_drush_help($section) {
  switch ($section) {
    case 'tide-core-scheduled-transitions-queue-jobs':
      return dt('Drush 8 wrapper for the Drush 9 Scheduled transitions queuing Drush command.');
  }
}

/**
 * Implements hook_drush_command().
 */
function tide_core_drush_command() {
  $commands['tide-core-scheduled-transitions-queue-jobs'] = [
    'description' => dt('Drush 8 wrapper for the Drush 9 Scheduled transitions queuing Drush command.'),
  ];

  return $commands;
}

/**
 * Dispatches scheduled media releases.
 */
function drush_tide_core_tide_core_scheduled_transitions_queue_jobs() {
  $jobs = \Drupal::service('scheduled_transitions.jobs');
  $jobs->jobCreator();
  drush_print(dt('Scheduled transitions queued.'));
}
