<?php

/**
 * @file
 * Tide OAuth Drush commands.
 */

use Drupal\tide_oauth\EnvKeyGenerator;

/**
 * Implements hook_drush_command().
 */
function tide_oauth_drush_command() {
  $commands['tide-oauth-env-keygen'] = [
    'description' => 'Generate OAuth keys from environment variables',
    'drupal dependencies' => ['tide_oauth'],
    'aliases' => ['toenvkg', 'tide-oauth:env-keygen'],
  ];
  return $commands;
}

/**
 * Callback for tide-oauth:env-keygen command.
 */
function drush_tide_oauth_env_keygen() {
  /** @var \Drupal\tide_oauth\EnvKeyGenerator $env_key_generator */
  $env_key_generator = \Drupal::service('tide_oauth.env_key_generator');
  /** @var \Drupal\Core\File\FileSystemInterface $fs */
  $fs = \Drupal::service('file_system');
  // Generate the OAuth encryption keys from Environment variables.
  if ($env_key_generator->hasEnvKeys()) {
    if ($env_key_generator->generateEnvKeys()) {
      // Update Simple OAuth settings.
      $env_key_generator->setSimpleOauthKeySettings();
    }
    else {
      drush_set_error('Could not generate OAuth keys from TIDE_OAUTH_*_KEY environment variables.');
    }
  }
  else {
    /** @var \Drupal\simple_oauth\Service\KeyGeneratorService $key_generator */
    $key_generator = \Drupal::service('simple_oauth.key.generator');
    $key_generator->generateKeys('private://');
    $fs->move('private://private.key', EnvKeyGenerator::FILE_PRIVATE_KEY);
    $fs->chmod(EnvKeyGenerator::FILE_PRIVATE_KEY, 0600);
    $fs->move('private://public.key', EnvKeyGenerator::FILE_PUBLIC_KEY);
    $fs->chmod(EnvKeyGenerator::FILE_PUBLIC_KEY, 0600);
    // Update Simple OAuth settings.
    $env_key_generator->setSimpleOauthKeySettings();
  }
}
