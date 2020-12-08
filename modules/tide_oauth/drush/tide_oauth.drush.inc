<?php

/**
 * @file
 * Tide OAuth Drush commands.
 */

/**
 * Implements hook_drush_command().
 */
function tide_oauth_drush_command() {
  $commands['tide-oauth-keygen'] = [
    'description' => 'Generate OAuth keys from environment variables',
    'drupal dependencies' => ['tide_oauth'],
    'aliases' => ['tokgn', 'tide-oauth:keygen'],
  ];
  return $commands;
}

/**
 * Callback for tide-oauth:keygen command.
 */
function drush_tide_oauth_keygen() {
  /** @var \Drupal\tide_oauth\EnvKeyGenerator $env_key_generator */
  $env_key_generator = \Drupal::service('tide_oauth.env_key_generator');
  // Generate the OAuth encryption keys from Environment variables.
  if ($env_key_generator->generateEnvKeys()) {
    // Update Simple OAuth settings.
    $env_key_generator->setSimpleOauthKeySettings();
  }
  else {
    drush_set_error('Could not generate OAuth keys.');
  }
}
