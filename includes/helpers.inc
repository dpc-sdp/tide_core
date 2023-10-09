<?php

/**
 * @file
 * Helper functions.
 */

use Drupal\Core\Config\FileStorage;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Site\Settings;
use Drupal\paragraphs\Entity\ParagraphsType;

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

  if (!$prioritise_sync) {
    // CONFIG_SYNC has lower priority.
    array_push($locations, Settings::get('config_sync_directory'));
  }
  else {
    // CONFIG_SYNC has top priority.
    array_unshift($locations, Settings::get('config_sync_directory'));
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
 * Retrieve content of a config file in config/sync directory.
 *
 * @param string $config_name
 *   The config name.
 * @param string|null $key
 *   The key to return. If empty, the whole config is returned.
 *
 * @return array|mixed|null
 *   The config, or the value of the key in the config.
 */
function _tide_retrieve_config_sync(string $config_name, string $key = NULL) {
  try {
    $config_file = Settings::get('config_sync_directory') . DIRECTORY_SEPARATOR . $config_name . '.yml';
    if (file_exists($config_file)) {
      $config = Yaml::decode(file_get_contents($config_file));
      return ($key && array_key_exists($key, $config)) ? $config[$key] : $config;
    }
  }
  catch (Exception $exception) {
    watchdog_exception('tide_core', $exception);
  }
  return NULL;
}

/**
 * Retrieve UUID of a config file in config/sync directory.
 *
 * @param string $config_name
 *   The config name.
 *
 * @return string|null
 *   The UUID.
 */
function _tide_retrieve_config_uuid(string $config_name) : ?string {
  $uuid = _tide_retrieve_config_sync($config_name, 'uuid');
  return is_string($uuid) ? $uuid : NULL;
}

/**
 * Set the icon for a paragraph type.
 *
 * @param string|\Drupal\paragraphs\Entity\ParagraphsType $paragraph_type
 *   The paragraph type id, or a loaded ParagraphsType entity.
 * @param string $icon_file
 *   The path to the icon file.
 *
 * @throws \Drupal\Core\Entity\EntityStorageException
 */
function _tide_set_paragraph_type_icon($paragraph_type, string $icon_file) {
  if (!($paragraph_type instanceof ParagraphsType)) {
    $paragraph_type = ParagraphsType::load((string) $paragraph_type);
  }
  if ($paragraph_type instanceof ParagraphsType) {
    if (@file_exists($icon_file)) {
      $icon_data = base64_encode(file_get_contents($icon_file));

      /** @var \Symfony\Component\Mime\MimeTypeGuesserInterface $mime_type_guesser */
      $mime_guesser = \Drupal::service('file.mime_type.guesser');
      $icon_mime = $mime_guesser->guessMimeType($icon_file);
      if (!$icon_mime) {
        $icon_mime = 'image/png';
      }

      $icon_uuid = _tide_retrieve_config_sync($paragraph_type->getConfigDependencyName(), 'icon_uuid');
      if (!$icon_uuid) {
        $icon_uuid = \Drupal::service('uuid')->generate();
      }

      $paragraph_type->set('icon_uuid', $icon_uuid);
      $paragraph_type->set('icon_default', 'data:' . $icon_mime . ';base64,' . $icon_data);
      $paragraph_type->getIconFile();
    }

    $paragraph_type->save();
  }
}

/**
 * Extracts config id from a given string.
 *
 * @param string $target
 *   The start of the string.
 * @param string $config
 *   The full length of config id.
 * @param bool $include
 *   Whether to include $target.
 *
 * @example
 * @code
 * $config = 'field.field.paragraph.compact_card_collection.field_paragraph_title
 * $field_id = _tide_extract_id_for_storage_usage('paragraph',$config,true);
 * returns ['paragraph.compact_card_collection.field_paragraph_title'];
 * $field_id = _tide_extract_id_for_storage_usage('paragraph',$config,false);
 * returns ['compact_card_collection.field_paragraph_title'];
 * @endcode
 */
function _tide_extract_id_for_storage_usage($target, $config, $include) {
  $pattern = '/(' . $target . '\.[^\s]+)/';
  $matches = [];
  preg_match($pattern, $config, $match);
  if (!empty($match[1])) {
    if ($include) {
      $matches[] = $match[1];
    }
    else {
      $matches[] = str_replace($target . '.', '', $match[1]);
    }
  }
  return $matches;
}
