<?php

namespace Drupal\tide_oauth\Commands;

use Drupal\tide_oauth\EnvKeyGenerator;
use Drupal\Core\File\FileSystemInterface;
use Drupal\simple_oauth\Service\KeyGeneratorService;
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
   * File System.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Simple Oauth Key Generator Service.
   *
   * @var \Drupal\simple_oauth\Service\KeyGeneratorService
   */
  protected $keyGenerator;

  /**
   * TideOauthCommands constructor.
   *
   * @param \Drupal\tide_oauth\EnvKeyGenerator $env_key_generator
   *   Env Key Generator.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   File system.
   * @param \Drupal\simple_oauth\Service\KeyGeneratorService $key_generator
   *   Simple Oauth Key Generator Service.
   */
  public function __construct(EnvKeyGenerator $env_key_generator, FileSystemInterface $file_system, KeyGeneratorService $key_generator) {
    parent::__construct();
    $this->envKeyGenerator = $env_key_generator;
    $this->fileSystem = $file_system;
    $this->$keyGenerator = $key_generator;
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
    else {
      $this->$keyGenerator->generateKeys('private://');
      $this->fileSystem->move('private://private.key', EnvKeyGenerator::FILE_PRIVATE_KEY);
      $this->fileSystem->chmod(EnvKeyGenerator::FILE_PRIVATE_KEY, 0600);
      $this->fileSystem->move('private://public.key', EnvKeyGenerator::FILE_PUBLIC_KEY);
      $this->fileSystem->chmod(EnvKeyGenerator::FILE_PUBLIC_KEY, 0600);
      // Update Simple OAuth settings.
      $this->envKeyGenerator->setSimpleOauthKeySettings();
      $this->io()->success('OAuth keys have been created.');
    }
  }

}
