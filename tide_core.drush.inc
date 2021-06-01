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
 * Disabiling some warning rule check.
 * phpcs:disable Drupal.Commenting.Deprecated.DeprecatedVersionFormat
 * phpcs:disable Drupal.Commenting.Deprecated.DeprecatedWrongSeeUrlFormat
 * phpcs:disable Drupal.Semantics.FunctionTriggerError.TriggerErrorVersion
 * phpcs:disable Drupal.Semantics.FunctionTriggerError.TriggerErrorVersion
 * phpcs:disable Drupal.Semantics.FunctionTriggerError.TriggerErrorSeeUrlFormat
 */

/**
 * Dispatches scheduled media releases.
 *
 * @deprecated in tide_core:2.0.5 and is removed from tide_core:2.0.6.
 *    With a move to Drush 9+ the functionality provided by this function is now
 *    accessible with the Drush command provided by Scheduled Transitions
 *    module.
 *
 * @see Drupal\scheduled_transitions\Commands\ScheduledTransitionsCommands
 */
function drush_tide_core_scheduled_transitions_queue_jobs() {
  @trigger_error('drush_tide_core_scheduled_transitions_queue_jobs() is deprecated in tide_core:2.0.5 and is removed from tide_core:2.0.6. Instead use scheduled-transitions:queue-jobs. See https://github.com/dpc-sdp/tide_core/pull/220', E_USER_DEPRECATED);
  $jobs = \Drupal::service('scheduled_transitions.jobs');
  $jobs->jobCreator();
  drush_print(dt('Scheduled transitions queued.'));
}

/* phpcs:enable */
