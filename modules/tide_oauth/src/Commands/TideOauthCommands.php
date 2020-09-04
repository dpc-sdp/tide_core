<?php

namespace Drupal\tide_oauth\Commands;

use Drupal\tide_oauth\EnvKeyGenerator;
use Drush\Commands\DrushCommands;

/**
 * Class TideOauthCommands.
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
   * @usage drush tide-oauth:env-keygen
   *   Generate OAuth keys from Environment variables.
   *
   * @command tide-oauth:env-keygen
   * @validate-module-enabled tide_oauth
   * @aliases toenvkg,tide-oauth-env-keygen
   */
  public function generateKeys() {
    if ($this->envKeyGenerator->hasEnvKeys()) {
      if ($this->envKeyGenerator->generateEnvKeys()) {
        // Update Simple OAuth settings.
        $this->envKeyGenerator->setSimpleOauthKeySettings();
        $this->io()->success('OAuth keys have been created from TIDE_OAUTH_*_KEY environment variables.');
      }
      else {
        $this->io()->error('Could not generate OAuth keys from TIDE_OAUTH_*_KEY environment variables.');
      }
    }
  }

}
