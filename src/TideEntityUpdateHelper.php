<?php

namespace Drupal\tide_core;

use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Sql\SqlEntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionListenerInterface;

/**
 * Provides helper function for entity updates.
 *
 * @package Drupal\tide_core
 */
class TideEntityUpdateHelper {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * The entity last installed schema repository.
   *
   * @var \Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface
   */
  protected $lastInstalledSchema;

  /**
   * The field storage definition listener.
   *
   * @var \Drupal\Core\Field\FieldStorageDefinitionListenerInterface
   */
  protected $fieldStorageDefinitionListener;

  /**
   * TideEntityUpdateHelper constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   EntityTypeManagerInterface.
   * @param \Drupal\Core\Entity\EntityFieldManager $entity_field_manager
   *   EntityFieldManager.
   * @param \Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface $entity_last_installed_schema_repository
   *   EntityLastInstalledSchemaRepositoryInterface.
   * @param \Drupal\Core\Field\FieldStorageDefinitionListenerInterface $definition_listener
   *   FieldStorageDefinitionListenerInterface.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFieldManager $entity_field_manager, EntityLastInstalledSchemaRepositoryInterface $entity_last_installed_schema_repository, FieldStorageDefinitionListenerInterface $definition_listener) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->lastInstalledSchema = $entity_last_installed_schema_repository;
    $this->fieldStorageDefinitionListener = $definition_listener;
  }

  /**
   * Returns entity storage object.
   *
   * @param string $entity_type_id
   *   The entity type id, e.g. node.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   Returns an entity storage object.
   */
  public function getStorageObject(string $entity_type_id) {
    $all_ids = array_keys($this->entityTypeManager->getDefinitions());
    if (!in_array($entity_type_id, $all_ids)) {
      return NULL;
    }
    return $this->entityTypeManager->getStorage($entity_type_id);
  }

  /**
   * Returns entity schema data.
   *
   * This helper function returns the schema data which will be saved in the
   * key_value table.
   *
   * @param \Drupal\Core\Entity\Sql\SqlEntityStorageInterface $storage
   *   SqlEntityStorageInterface.
   * @param string $entity_type_id
   *   The entity type id, e.g. node.
   *
   * @return mixed|null
   *   Returns an array or null.
   */
  public function getEntitySchemaData(SqlEntityStorageInterface $storage, string $entity_type_id) {
    $all_ids = array_keys($this->entityTypeManager->getDefinitions());
    if (!in_array($entity_type_id, $all_ids)) {
      return NULL;
    }
    $r_get_storage_schema = new \ReflectionMethod($storage, 'getStorageSchema');
    $r_get_storage_schema->setAccessible(TRUE);
    $storage_schema = $r_get_storage_schema->invoke($storage);
    $entity_type_definition = $this->entityTypeManager
      ->getDefinition($entity_type_id);
    $r_getEntitySchema = new \ReflectionMethod($storage_schema, 'getEntitySchema');
    $r_getEntitySchema->setAccessible(TRUE);
    $entity_schema = $r_getEntitySchema->invokeArgs($storage_schema, [$entity_type_definition]);
    $r_getEntitySchemaData = new \ReflectionMethod($storage_schema, 'getEntitySchemaData');
    $r_getEntitySchemaData->setAccessible(TRUE);
    return $r_getEntitySchemaData->invokeArgs($storage_schema, [
      $entity_type_definition,
      $entity_schema,
    ]);
  }

  /**
   * Returns field storage schema data.
   *
   * This helper function returns field storage schema data which will be saved
   * in the key_value table.
   *
   * @param \Drupal\Core\Entity\Sql\SqlEntityStorageInterface $storage
   *   SqlEntityStorageInterface.
   * @param \Drupal\Core\Field\BaseFieldDefinition $fieldDefinition
   *   BaseFieldDefinition.
   *
   * @return mixed|null
   *   Returns an array or null.
   */
  public function getFieldEntitySchemaData(SqlEntityStorageInterface $storage, BaseFieldDefinition $fieldDefinition) {
    $r_get_storage_schema = new \ReflectionMethod($storage, 'getStorageSchema');
    $r_get_storage_schema->setAccessible(TRUE);
    $storage_schema = $r_get_storage_schema->invoke($storage);
    $r_getSchemaFromStorageDefinition = new \ReflectionMethod($storage_schema, 'getSchemaFromStorageDefinition');
    $r_getSchemaFromStorageDefinition->setAccessible(TRUE);
    return $r_getSchemaFromStorageDefinition->invokeArgs($storage_schema, [$fieldDefinition]);
  }

  /**
   * Installs a field table.
   *
   * For some reasons, the table didn't get installed. This function will help
   * to install the table.
   *
   * @param \Drupal\Core\Field\BaseFieldDefinition $fieldDefinition
   *   BaseFieldDefinition.
   */
  public function installFieldTable(BaseFieldDefinition $fieldDefinition) {
    $this->fieldStorageDefinitionListener->onFieldStorageDefinitionCreate($fieldDefinition);
  }

  /**
   * Returns field storage definitions for an entity type.
   *
   * @param string $entity_type_id
   *   The entity type id, e.g. node.
   *
   * @return array
   *   Keyed by a field name.
   *   Returns an array.
   *
   * @example
   * ['nid'=> BaseFieldDefinition .. ]
   */
  public function getFieldStorageDefinitions(string $entity_type_id) {
    $all_ids = array_keys($this->entityTypeManager->getDefinitions());
    if (!in_array($entity_type_id, $all_ids)) {
      return NULL;
    }
    return $this->entityFieldManager->getFieldStorageDefinitions($entity_type_id);
  }

  /**
   * Gets the entity type's most recently installed field storage definitions.
   *
   * During the application lifetime, field storage definitions can change. For
   * example, updated code can be deployed. The getFieldStorageDefinitions()
   * method will always return the definitions as determined by the current
   * codebase. This method, however, returns what the definitions were when the
   * last time that one of the
   * \Drupal\Core\Field\FieldStorageDefinitionListenerInterface events was last
   * fired and completed successfully. In other words, the definitions that
   * the entity type's handlers have incorporated into the application state.
   * For example, if the entity type's storage handler is SQL-based, the
   * definitions for which database tables were created.
   *
   * Application management code can check if getFieldStorageDefinitions()
   * differs from getLastInstalledFieldStorageDefinitions() and decide whether
   * to:
   * - Invoke the appropriate
   *   \Drupal\Core\Field\FieldStorageDefinitionListenerInterface
   *   events so that handlers react to the new definitions.
   * - Raise a warning that the application state is incompatible with the
   *   codebase.
   * - Perform some other action.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return \Drupal\Core\Field\FieldStorageDefinitionInterface[]
   *   The array of installed field storage definitions for the entity type,
   *   keyed by field name.
   *
   * @see \Drupal\Core\Entity\EntityTypeListenerInterface
   */
  public function getOriginalStorageDefinitions(string $entity_type_id) {
    return $this->lastInstalledSchema->getLastInstalledFieldStorageDefinitions($entity_type_id);
  }

  /**
   * Performs a field storage definition update.
   */
  public function updateFieldTable(string $entity_type_id, string $field_name) {
    $storage_definitions = $this->entityFieldManager->getFieldStorageDefinitions($entity_type_id);
    $original_storage_definitions = $this->lastInstalledSchema->getLastInstalledFieldStorageDefinitions($entity_type_id);
    $storage_definition = $storage_definitions[$field_name] ?? NULL;
    $original_storage_definition = $original_storage_definitions[$field_name] ?? NULL;
    if ($storage_definition && $original_storage_definition) {
      $this->fieldStorageDefinitionListener->onFieldStorageDefinitionUpdate($storage_definition, $original_storage_definition);
    }
  }

}
