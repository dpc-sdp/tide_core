<?php

/**
 * @file
 * Post-update functions for Tide Core.
 */

use Drupal\Core\Config\FileStorage;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldPurger;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Utility\UpdateException;
use Drupal\editor\EditorInterface;
use Drupal\filter\FilterFormatInterface;

/**
 * Enables CKEditor IFrame and synchronizes the Rich Text configuration.
 */
function tide_core_post_update_enable_ckeditor_iframe_and_sync_rich_text(array &$sandbox = []): TranslatableMarkup {
  if (!\Drupal::moduleHandler()->moduleExists('ckeditor_iframe')) {
    \Drupal::service('module_installer')->install(['ckeditor_iframe'], TRUE);
  }

  $module_path = \Drupal::service('extension.list.module')->getPath('tide_core');
  $source = new FileStorage($module_path . '/config/install');
  $filter_config = $source->read('filter.format.rich_text');
  $editor_config = $source->read('editor.editor.rich_text');

  if (!is_array($filter_config) || !is_array($editor_config)) {
    throw new RuntimeException('Tide Core Rich Text configuration could not be read.');
  }

  $updated = _tide_core_post_update_sync_rich_text(
    \Drupal::entityTypeManager(),
    $filter_config,
    $editor_config,
  );

  return new TranslatableMarkup(
    'Enabled required CKEditor modules and synchronized existing Rich Text configuration: @configuration.',
    ['@configuration' => $updated ? implode(', ', $updated) : 'none'],
  );
}

/**
 * Synchronizes the existing Rich Text format and editor for this update only.
 *
 * This helper and the merge functions below intentionally live in the
 * post-update file. They should be removed with the completed post-update.
 *
 * @return string[]
 *   Names of configuration entities that were updated.
 */
function _tide_core_post_update_sync_rich_text(EntityTypeManagerInterface $entity_type_manager, array $filter_target, array $editor_target): array {
  $updated = [];
  $filter_storage = $entity_type_manager->getStorage('filter_format');
  $format = $filter_storage->load('rich_text');
  if (!$format instanceof FilterFormatInterface) {
    // Do not recreate a format that a site has deliberately removed, and do
    // not update an orphan editor without its associated format.
    return $updated;
  }

  $current = $format->toArray();
  $configuration = _tide_core_post_update_merge_rich_text_filter($current, $filter_target);
  if (($current['filters'] ?? []) !== $configuration['filters']) {
    $format->set('filters', $configuration['filters']);
    $format->save();
    $updated[] = 'filter.format.rich_text';
  }

  $editor_storage = $entity_type_manager->getStorage('editor');
  $editor = $editor_storage->load('rich_text');
  if (!$editor instanceof EditorInterface) {
    return $updated;
  }

  $current = $editor->toArray();
  $configuration = _tide_core_post_update_merge_rich_text_editor($current, $editor_target);
  $settings_changed = ($current['settings'] ?? []) !== $configuration['settings'];
  $upload_changed = ($current['image_upload'] ?? []) !== $configuration['image_upload'];
  if ($settings_changed || $upload_changed) {
    $editor->setSettings($configuration['settings']);
    $editor->setImageUploadSettings($configuration['image_upload']);
    $editor->save();
    $updated[] = 'editor.editor.rich_text';
  }

  return $updated;
}

/**
 * Synchronizes Tide-managed filters while retaining site-specific filters.
 */
function _tide_core_post_update_merge_rich_text_filter(array $current, array $target): array {
  $current_filters = is_array($current['filters'] ?? NULL) ? $current['filters'] : [];
  $target_filters = is_array($target['filters'] ?? NULL) ? $target['filters'] : [];

  foreach ($target_filters as $plugin_id => $configuration) {
    $existing = is_array($current_filters[$plugin_id] ?? NULL)
      ? $current_filters[$plugin_id]
      : [];
    $current_filters[$plugin_id] = _tide_core_post_update_merge_ckeditor_values($existing, $configuration);
  }

  $current['filters'] = $current_filters;
  return $current;
}

/**
 * Synchronizes Tide-managed editor settings while retaining site additions.
 */
function _tide_core_post_update_merge_rich_text_editor(array $current, array $target): array {
  $current_settings = is_array($current['settings'] ?? NULL) ? $current['settings'] : [];
  $target_settings = is_array($target['settings'] ?? NULL) ? $target['settings'] : [];
  if (!is_array($current_settings['toolbar'] ?? NULL)) {
    $current_settings['toolbar'] = [];
  }

  $current_toolbar = $current_settings['toolbar']['items'] ?? [];
  $target_toolbar = $target_settings['toolbar']['items'] ?? [];
  $current_settings['toolbar']['items'] = _tide_core_post_update_merge_ckeditor_toolbar(
    is_array($current_toolbar) ? $current_toolbar : [],
    is_array($target_toolbar) ? $target_toolbar : [],
  );

  $current_plugins = is_array($current_settings['plugins'] ?? NULL)
    ? $current_settings['plugins']
    : [];
  $target_plugins = is_array($target_settings['plugins'] ?? NULL)
    ? $target_settings['plugins']
    : [];
  $plugins = [];
  foreach ($target_plugins as $plugin_id => $configuration) {
    $existing = is_array($current_plugins[$plugin_id] ?? NULL)
      ? $current_plugins[$plugin_id]
      : [];
    $plugins[$plugin_id] = _tide_core_post_update_merge_ckeditor_values($existing, $configuration);
  }

  if (isset($target_plugins['ckeditor5_sourceEditing']['allowed_tags'])) {
    $obsolete_tags = [
      '<iframe frameborder height scrolling src width title allowfullscreen>',
      '<ul>',
      '<ol type>',
      '<th align class>',
      '<td align class>',
    ];
    $target_tags = $target_plugins['ckeditor5_sourceEditing']['allowed_tags'];
    $current_tags = $current_plugins['ckeditor5_sourceEditing']['allowed_tags'] ?? [];
    $managed_tags = array_merge($target_tags, $obsolete_tags);
    $custom_tags = array_filter(
      is_array($current_tags) ? $current_tags : [],
      static fn (mixed $tag): bool => is_string($tag) && !in_array($tag, $managed_tags, TRUE),
    );
    $plugins['ckeditor5_sourceEditing']['allowed_tags'] = array_values(array_unique([
      ...$target_tags,
      ...$custom_tags,
    ]));
  }

  foreach ($current_plugins as $plugin_id => $configuration) {
    if (!array_key_exists($plugin_id, $plugins)) {
      $plugins[$plugin_id] = $configuration;
    }
  }
  $current_settings['plugins'] = $plugins;
  $current['settings'] = $current_settings;

  $current_upload = is_array($current['image_upload'] ?? NULL) ? $current['image_upload'] : [];
  $target_upload = is_array($target['image_upload'] ?? NULL) ? $target['image_upload'] : [];
  if ($current_upload === []) {
    $current_upload = $target_upload;
  }
  else {
    foreach ($target_upload as $key => $value) {
      if (!array_key_exists($key, $current_upload)) {
        $current_upload[$key] = $value;
      }
    }
    if (($current_upload['max_size'] ?? NULL) === '') {
      $current_upload['max_size'] = $target_upload['max_size'] ?? NULL;
    }
  }
  $current['image_upload'] = $current_upload;

  return $current;
}

/**
 * Replaces retired toolbar items without removing site-specific additions.
 */
function _tide_core_post_update_merge_ckeditor_toolbar(array $current, array $target): array {
  if ($current === []) {
    return $target;
  }

  $legacy_items = [
    'embeddedContent',
    'embeddedContent__default',
  ];
  $iframe_item = 'iframeEmbed';
  $items = [];
  $iframe_added = FALSE;
  foreach ($current as $item) {
    if (in_array($item, $legacy_items, TRUE)) {
      if (!$iframe_added) {
        $items[] = $iframe_item;
        $iframe_added = TRUE;
      }
      continue;
    }
    if ($item === $iframe_item) {
      if ($iframe_added) {
        continue;
      }
      $iframe_added = TRUE;
    }
    $items[] = $item;
  }

  if (!$iframe_added && in_array($iframe_item, $target, TRUE)) {
    $target_position = array_search($iframe_item, $target, TRUE);
    $inserted = FALSE;
    for ($position = $target_position - 1; $position >= 0; $position--) {
      $predecessor = array_search($target[$position], $items, TRUE);
      if ($predecessor !== FALSE) {
        array_splice($items, $predecessor + 1, 0, [$iframe_item]);
        $inserted = TRUE;
        break;
      }
    }
    if (!$inserted) {
      $items[] = $iframe_item;
    }
  }

  return $items;
}

/**
 * Applies shipped values while preserving unknown associative keys.
 */
function _tide_core_post_update_merge_ckeditor_values(array $current, array $target): array {
  foreach ($target as $key => $value) {
    if (is_array($value) && $value !== []) {
      if (array_is_list($value)) {
        $current[$key] = $value;
      }
      else {
        $existing = is_array($current[$key] ?? NULL) ? $current[$key] : [];
        $current[$key] = _tide_core_post_update_merge_ckeditor_values($existing, $value);
      }
    }
    elseif (!is_array($value)) {
      $current[$key] = $value;
    }
    elseif (!array_key_exists($key, $current)) {
      $current[$key] = [];
    }
  }

  return $current;
}

/**
 * Refresh the OAuth2 token scopes storage after Simple OAuth's schema updates.
 */
function tide_core_post_update_refresh_oauth2_token_scopes(array &$sandbox = []): ?TranslatableMarkup {
  $sandbox['#finished'] = 1;
  if (!\Drupal::moduleHandler()->moduleExists('simple_oauth')) {
    return NULL;
  }

  // Post-updates run after all hook_update_N() implementations, including the
  // Simple OAuth 6 migration from role references to scope references.
  $fields = \Drupal::service('entity_field.manager')->getFieldStorageDefinitions('oauth2_token');
  $definition = $fields['scopes'] ?? NULL;
  $update_manager = \Drupal::entityDefinitionUpdateManager();
  $installed = $update_manager->getFieldStorageDefinition('scopes', 'oauth2_token');
  if (!$definition || !$installed || $definition->getType() !== 'oauth2_scope_reference' || $installed->getType() !== 'oauth2_scope_reference') {
    throw new UpdateException('The OAuth2 token scopes field must be migrated to Simple OAuth 6 before its storage definition can be refreshed.');
  }

  // The old and replacement base fields share a storage identifier. Purging
  // the old role-reference field later would erase the new SQL schema record.
  // Finish only that already-deleted field's purge before repairing the record.
  $deleted_fields = \Drupal::service('entity_field.deleted_fields_repository');
  $storage_id = $definition->getUniqueStorageIdentifier();
  $deleted = $deleted_fields->getFieldStorageDefinitions()[$storage_id] ?? NULL;
  if ($deleted) {
    if (!$deleted->isDeleted() || $deleted->getType() !== 'entity_reference' || $deleted->getSetting('target_type') !== 'user_role') {
      throw new UpdateException('Unexpected deleted OAuth2 token scopes storage. Inspect the field purge queue before retrying this update.');
    }
    if (\Drupal::hasService(FieldPurger::class)) {
      \Drupal::service(FieldPurger::class)->purgeBatch(50, $storage_id);
    }
    else {
      // Compatibility with Drupal versions before the FieldPurger service.
      \Drupal::moduleHandler()->loadInclude('field', 'inc', 'field.purge');
      field_purge_batch(50, $storage_id);
    }
    if (isset($deleted_fields->getFieldStorageDefinitions()[$storage_id])) {
      $sandbox['#finished'] = 0;
      return NULL;
    }
  }

  $storage = \Drupal::entityTypeManager()->getStorage('oauth2_token');
  if (!$storage->requiresFieldStorageSchemaChanges($definition, $installed)) {
    return NULL;
  }

  // Use Drupal's update API and the current definition so token data is kept.
  // The database update runner handles the subsequent cache rebuild.
  $update_manager->updateFieldStorageDefinition($definition);
  return new TranslatableMarkup('Updated the OAuth2 token scopes field storage definition.');
}
