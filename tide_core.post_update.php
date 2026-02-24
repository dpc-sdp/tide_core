<?php

/**
 * @file
 * Post update functions for tide_core.
 */

use Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface;

/**
 * Fix the problems of fields in status page.
 */
function tide_core_post_update_fix_status_page() {
  $entity_definition_update_manager = \Drupal::entityDefinitionUpdateManager();
  /** @var \Drupal\tide_core\TideEntityUpdateHelper $helper */
  $helper = Drupal::service('tide_core.entity_update_helper');
  foreach ($entity_definition_update_manager->getChangeList() as $entity_type_id => $change_list) {
    if (!empty($change_list['field_storage_definitions'])) {
      foreach ($change_list['field_storage_definitions'] as $field_name => $change) {
        switch ($change) {
          case EntityDefinitionUpdateManagerInterface::DEFINITION_CREATED:
            $detail = $helper->getFieldStorageDefinitions($entity_type_id);
            $helper->installFieldTable($detail[$field_name]);
            break;

          case EntityDefinitionUpdateManagerInterface::DEFINITION_UPDATED:
            $helper->updateFieldTable($entity_type_id, $field_name);
            break;

          case EntityDefinitionUpdateManagerInterface::DEFINITION_DELETED:
            $original_storage_definitions = $helper->getOriginalStorageDefinitions($entity_type_id);
            $entity_definition_update_manager->uninstallFieldStorageDefinition($original_storage_definitions[$field_name]);
            break;
        }
      }
    }
  }
}

/**
 * Fixes mismatched entity and/or field definitions" error.
 */
function tide_core_post_update_fixes_mismatched_entity_01() {
  $entity_type_manager = \Drupal::entityTypeManager();
  $entity_type_manager->clearCachedDefinitions();
  $entity_type_ids = [];
  $change_summary = \Drupal::service('entity.definition_update_manager')->getChangeSummary();
  if (!empty($change_summary)) {
    foreach ($change_summary as $entity_type_id => $change_list) {
      $entity_type = $entity_type_manager->getDefinition($entity_type_id);
      \Drupal::entityDefinitionUpdateManager()->installEntityType($entity_type);
      $entity_type_ids[] = $entity_type_id;
    }
    Drush::output()->writeln('Installed/Updated the entity type(s): ' . implode(', ', $entity_type_ids));
  }
}
