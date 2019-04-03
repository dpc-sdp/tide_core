<?php

/**
 * @file
 * Drush commands for Tide Core.
 */

/**
 * Implements hook_drush_command().
 */
function tide_core_drush_command() {
  $items = [];

  $items['tide-core-scheduled-updates-cron'] = [
    'description' => 'Execute scheduled_updates cron.',
    'examples' => [
      'drush tide-core-scheduled-updates-cron' => dt('Run scheduled updates cron.'),
    ],
    'arguments' => [],
    'aliases' => ['tide-su-cron'],
  ];

  return $items;
}

/**
 * Execute scheduled_updates cron.
 */
function drush_tide_core_scheduled_updates_cron() {

  try {
    \Drupal::moduleHandler()->invoke('scheduled_updates', 'cron');
  }
  catch (ConsoleException $exception) {
    drush_set_error($exception->getMessage());
  }
}
