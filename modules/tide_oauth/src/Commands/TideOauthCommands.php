<?php

namespace Drupal\tide_oauth\Commands;

use Drupal\tide_oauth\EnvKeyGenerator;
use Drush\Commands\DrushCommands;

/**
 * Class TideOauthCommands defines the drush commands.
 *
 * @package Drupal\tide_oauth\Commands
 */
class TideOauthCommands extends DrushCommands {

  /**
   * Env Key Generator.
   *
   * @var \Drupal\tide_oauth\EnvKeyGenerator
   */
  protected $envKeyGenerator;

  /**
   * TideOauthCommands constructor.
   *
   * @param \Drupal\tide_oauth\EnvKeyGenerator $env_key_generator
   *   Env Key Generator.
   */
  public function __construct(EnvKeyGenerator $env_key_generator) {
    parent::__construct();
    $this->envKeyGenerator = $env_key_generator;
  }

  /**
   * Generate OAuth keys from Environment variables.
   *
   * @usage drush tide-oauth:keygen
   *   Generate OAuth keys from Environment variables.
   *
   * @command tide-oauth:keygen
   * @validate-module-enabled tide_oauth
   * @aliases tokgn,tide-oauth-keygen
   */
  public function generateKeys() {
    if ($this->envKeyGenerator->generateEnvKeys()) {
      // Update Simple OAuth settings.
      $this->envKeyGenerator->setSimpleOauthKeySettings();
      $this->io()->success('OAuth keys have been created.');
    }
    else {
      $this->io()->error('Could not generate OAuth keys.');
    }
  }

}
