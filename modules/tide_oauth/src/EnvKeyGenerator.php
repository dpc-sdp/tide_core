<?php

namespace Drupal\tide_oauth;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\simple_oauth\Service\Filesystem\FileSystemChecker;
use Drupal\simple_oauth\Service\KeyGeneratorService;

/**
 * Generates OAuth key pairs from files referenced by environment variables.
 *
 * The environment variables TIDE_OAUTH_PRIVATE_KEY and TIDE_OAUTH_PUBLIC_KEY
 * must contain the file system paths to the respective PEM key files.
 *
 * @package Drupal\tide_oauth
 */
class EnvKeyGenerator {

  public const ENV_PRIVATE_KEY = 'TIDE_OAUTH_PRIVATE_KEY';
  public const ENV_PUBLIC_KEY = 'TIDE_OAUTH_PUBLIC_KEY';
  public const FILE_PRIVATE_KEY = 'private://oauth.key';
  public const FILE_PUBLIC_KEY = 'private://oauth.pub';

  /**
   * File System.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * File System Checker.
   *
   * @var \Drupal\simple_oauth\Service\Filesystem\FileSystemChecker
   */
  protected $fileSystemChecker;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Simple Oauth Key Generator Service.
   *
   * @var \Drupal\simple_oauth\Service\KeyGeneratorService
   */
  protected $keyGenerator;

  /**
   * Path to the private key file, sourced from the environment variable.
   *
   * @var string
   */
  protected $privateKeyPath;

  /**
   * Path to the public key file, sourced from the environment variable.
   *
   * @var string
   */
  protected $publicKeyPath;

  /**
   * EnvKeyGenerator constructor.
   *
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   File system.
   * @param \Drupal\simple_oauth\Service\Filesystem\FileSystemChecker $fs_checker
   *   File system checker.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   * @param \Drupal\simple_oauth\Service\KeyGeneratorService $key_generator
   *   Simple Oauth Key Generator Service.
   */
  public function __construct(FileSystemInterface $file_system, FileSystemChecker $fs_checker, ConfigFactoryInterface $config_factory, KeyGeneratorService $key_generator) {
    $this->fileSystem = $file_system;
    $this->fileSystemChecker = $fs_checker;
    $this->configFactory = $config_factory;
    $this->keyGenerator = $key_generator;
    $this->privateKeyPath = getenv(static::ENV_PRIVATE_KEY) ?: NULL;
    $this->publicKeyPath = getenv(static::ENV_PUBLIC_KEY) ?: NULL;
  }

  /**
   * Check if the key file paths are set in environment variables and readable.
   *
   * @return bool
   *   TRUE if both environment variables point to readable key files.
   */
  public function hasEnvKeys() : bool {
    return !empty($this->privateKeyPath) && is_readable($this->privateKeyPath)
      && !empty($this->publicKeyPath) && is_readable($this->publicKeyPath);
  }

  /**
   * Generate OAuth key pairs from files referenced by environment variables.
   *
   * @return bool
   *   TRUE if the keys are generated.
   */
  public function generateEnvKeys() : bool {
    if (!$this->hasEnvKeys()) {
      return $this->generateOauthKeys();
    }

    $private = 'private://';
    $this->fileSystem->prepareDirectory($private, FileSystemInterface::CREATE_DIRECTORY);

    $key_files = [
      static::FILE_PRIVATE_KEY => file_get_contents($this->privateKeyPath),
      static::FILE_PUBLIC_KEY => file_get_contents($this->publicKeyPath),
    ];
    foreach ($key_files as $key_uri => $key_content) {
      if (@file_exists($key_uri)) {
        $this->fileSystem->unlink($key_uri);
      }
      $this->fileSystemChecker->write($key_uri, $key_content);
      $this->fileSystem->chmod($key_uri, 0600);
    }

    return TRUE;
  }

  /**
   * Update Simple OAuth settings with generated keys.
   *
   * @return bool
   *   TRUE if succeed.
   */
  public function setSimpleOauthKeySettings() : bool {
    $real_private_key = $this->fileSystem->realpath(static::FILE_PRIVATE_KEY);
    if (!$real_private_key || !@file_exists($real_private_key)) {
      return FALSE;
    }
    $real_public_key = $this->fileSystem->realpath(static::FILE_PUBLIC_KEY);
    if (!$real_public_key || !@file_exists($real_public_key)) {
      return FALSE;
    }

    $settings = $this->configFactory->getEditable('simple_oauth.settings');
    $has_changes = FALSE;
    if ($settings->get('private_key') !== $real_private_key) {
      $settings->set('private_key', $real_private_key);
      $has_changes = TRUE;
    }
    if ($settings->get('public_key') !== $real_public_key) {
      $settings->set('public_key', $real_public_key);
      $has_changes = TRUE;
    }
    if ($has_changes) {
      $settings->save();
    }

    return TRUE;
  }

  /**
   * Generate keys if Environment variables not set.
   *
   * @return bool
   *   TRUE if the keys are generated.
   */
  public function generateOauthKeys() : bool {
    if ($this->hasEnvKeys()) {
      return FALSE;
    }

    $private = 'private://';
    $this->fileSystem->prepareDirectory($private, FileSystemInterface::CREATE_DIRECTORY);

    $this->keyGenerator->generateKeys('private://');
    $this->fileSystem->move('private://private.key', static::FILE_PRIVATE_KEY);
    $this->fileSystem->chmod(static::FILE_PRIVATE_KEY, 0600);
    $this->fileSystem->move('private://public.key', static::FILE_PUBLIC_KEY);
    $this->fileSystem->chmod(static::FILE_PUBLIC_KEY, 0600);

    return TRUE;
  }

}
