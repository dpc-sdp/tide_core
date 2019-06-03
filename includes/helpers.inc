<?php

/**
 * @file
 * Helper functions.
 */

use Drupal\config\StorageReplaceDataWrapper;
use Drupal\Core\Config\ConfigImporter;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\StorageComparer;

/**
 * Helper to read configuration from provided locations.
 *
 * CONFIG_SYNC_DIRECTORY is prepended to the beginning of the locations list
 * to perform lookup in active configuration.
 *
 * @param string $id
 *   Configuration id.
 * @param array $locations
 *   Array of paths to lookup configuration files.
 * @param bool $prioritise_sync
 *   Whether to prioritise the same config in CONFIG_SYNC.
 *
 * @return mixed
 *   Configuration value.
 *
 * @throws \Exception
 *   If configuration file was not found in any specified location.
 */
function _tide_read_config($id, array $locations = [], $prioritise_sync = TRUE) {
  static $storages;

  global $config_directories;

  if (!$prioritise_sync) {
    // CONFIG_SYNC has lower priority.
    array_push($locations, $config_directories[CONFIG_SYNC_DIRECTORY]);
  }
  else {
    // CONFIG_SYNC has top priority.
    array_unshift($locations, $config_directories[CONFIG_SYNC_DIRECTORY]);
  }

  foreach ($locations as $path) {
    if (file_exists($path . DIRECTORY_SEPARATOR . $id . '.yml')) {
      $storages[$path] = new FileStorage($path);
      break;
    }
  }

  if (!isset($storages[$path])) {
    throw new Exception('Configuration does not exist in any provided locations');
  }

  return $storages[$path]->read($id);
}

/**
 * Helper to ensure that specified configuration is present.
 *
 * Used in install and update hooks to automatically install required
 * configuration from active configuration or, if does not exist, from provided
 * locations (usually, 'config/install' in module's directory).
 *
 * Helpful to avoid cases when configuration import may remove and add back
 * entities, because install/update hook does not use the same config items
 * as already exported.
 *
 * @param string $id
 *   Configuration id.
 * @param array $locations
 *   Array of paths to lookup configuration files.
 */
function _tide_ensure_config($id, array $locations = []) {
  $config_data = _tide_read_config($id, $locations);
  \Drupal::service('config.storage')->write($id, $config_data);
}

/**
 * Helper to reload default configuration provided by a module.
 *
 * @param string $module
 *   Module name.
 */
function _tide_reload_default_config($module) {
  /** @var \Drupal\Core\Config\ConfigInstaller $config_installer */
  $config_installer = \Drupal::service('config.installer');
  $config_installer->installDefaultConfig('module', $module);
}

/**
 * Helper to import a single config item (in a quick and dirty way).
 *
 * @param string $config_name
 *   Config name.
 * @param array $locations
 *   Array of Locations.
 * @param bool $prioritise_sync
 *   Whether to prioritise the same config in CONFIG_SYNC.
 *
 * @throws \Exception
 */
function _tide_import_single_config($config_name, array $locations = [], $prioritise_sync = TRUE) {
  $config_data = _tide_read_config($config_name, $locations, $prioritise_sync);

  $config_storage = \Drupal::service('config.storage');
  $event_dispatcher = \Drupal::service('event_dispatcher');
  $config_manager = \Drupal::service('config.manager');
  $lock_persistent = \Drupal::service('lock.persistent');
  $config_typed = \Drupal::service('config.typed');
  $module_handler = \Drupal::service('module_handler');
  $module_installer = \Drupal::service('module_installer');
  $theme_handler = \Drupal::service('theme_handler');
  $string_translation = \Drupal::service('string_translation');

  \Drupal::config($config_name);

  $source_storage = new StorageReplaceDataWrapper($config_storage);
  $source_storage->replaceData($config_name, $config_data);

  $storage_comparer = new StorageComparer(
    $source_storage,
    $config_storage,
    $config_manager
  );

  $storage_comparer->createChangelist();

  $config_importer = new ConfigImporter(
    $storage_comparer,
    $event_dispatcher,
    $config_manager,
    $lock_persistent,
    $config_typed,
    $module_handler,
    $module_installer,
    $theme_handler,
    $string_translation
  );

  try {
    $config_importer->import();
    \Drupal::cache('config')->delete($config_name);
  }
  catch (Exception $exception) {
    foreach ($config_importer->getErrors() as $error) {
      \Drupal::logger('tide')->error($error);
      \Drupal::messenger()->addError($error);
    }
    throw $exception;
  }
}
