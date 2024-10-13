<?php

namespace Drupal\tide_core;

use Drupal\Component\Utility\NestedArray;
use Drupal\config_update\ConfigPreRevertEvent;
use Drupal\config_update\ConfigReverter;
use Drupal\config_update\ConfigRevertEvent;
use Drupal\config_update\ConfigRevertInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Sql\SqlEntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionListenerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides helper function for entity updates.
 *
 * @package Drupal\tide_core
 */
class TideEntityUpdateHelper extends ConfigReverter {

  const INSTALL_DIR = '/config/install';
  const OPTIONAL_DIR = '/config/optional';

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * The active config storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $activeConfigStorage;

  /**
   * The extension config storage for config/install config items.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $extensionConfigStorage;

  /**
   * The extension config storage for config/optional config items.
   *
   * @var \Drupal\Core\Config\ExtensionInstallStorage
   */
  protected $extensionOptionalConfigStorage;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $dispatcher;

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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Config\StorageInterface $active_config_storage
   *   The active config storage.
   * @param \Drupal\Core\Config\StorageInterface $extension_config_storage
   *   The extension config storage.
   * @param \Drupal\Core\Config\StorageInterface $extension_optional_config_storage
   *   The extension config storage for optional config items.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Entity\EntityFieldManager $entity_field_manager
   *   EntityFieldManager.
   * @param \Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface $entity_last_installed_schema_repository
   *   EntityLastInstalledSchemaRepositoryInterface.
   * @param \Drupal\Core\Field\FieldStorageDefinitionListenerInterface $definition_listener
   *   FieldStorageDefinitionListenerInterface.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager, StorageInterface $active_config_storage, StorageInterface $extension_config_storage, StorageInterface $extension_optional_config_storage, ConfigFactoryInterface $config_factory, EventDispatcherInterface $dispatcher, EntityFieldManager $entity_field_manager, EntityLastInstalledSchemaRepositoryInterface $entity_last_installed_schema_repository, FieldStorageDefinitionListenerInterface $definition_listener) {
    $this->entityManager = $entity_manager;
    $this->activeConfigStorage = $active_config_storage;
    $this->extensionConfigStorage = $extension_config_storage;
    $this->extensionOptionalConfigStorage = $extension_optional_config_storage;
    $this->configFactory = $config_factory;
    $this->dispatcher = $dispatcher;
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
    $all_ids = array_keys($this->entityManager->getDefinitions());
    if (!in_array($entity_type_id, $all_ids)) {
      return NULL;
    }
    return $this->entityManager->getStorage($entity_type_id);
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
    $all_ids = array_keys($this->entityManager->getDefinitions());
    if (!in_array($entity_type_id, $all_ids)) {
      return NULL;
    }
    $r_get_storage_schema = new \ReflectionMethod($storage, 'getStorageSchema');
    $r_get_storage_schema->setAccessible(TRUE);
    $storage_schema = $r_get_storage_schema->invoke($storage);
    $entity_type_definition = $this->entityManager
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
    $all_ids = array_keys($this->entityManager->getDefinitions());
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

  /**
   * {@inheritdoc}
   */
  public function updateFromOptional($type, $name) {
    // Read the config from the file. Note: Do not call getFromExtension() here
    // because we need $full_name below.
    $value = FALSE;
    $full_name = $this->getFullName($type, $name);
    if ($full_name) {
      $value = $this->extensionOptionalConfigStorage->read($full_name);
    }
    if (!$value) {
      return FALSE;
    }

    // Make sure the configuration exists currently in active storage.
    $active_value = $this->activeConfigStorage->read($full_name);
    if (!$active_value) {
      return FALSE;
    }

    // Trigger an event to modify the active configuration value.
    $event = new ConfigPreRevertEvent($type, $name, $value, $active_value);
    $this->dispatcher->dispatch($event, ConfigRevertInterface::PRE_REVERT);
    $value = $event->getValue();

    // Load the current config and replace the value, retaining the config
    // hash (which is part of the _core config key's value).
    if ($type === 'system.simple') {
      $config = $this->configFactory->getEditable($full_name);
      $core = $config->get('_core');
      $config
        ->setData($value)
        ->set('_core', $core)
        ->save();
    }
    else {
      $definition = $this->entityManager->getDefinition($type);
      $id_key = $definition->getKey('id');
      $id = $value[$id_key];
      $entity_storage = $this->entityManager->getStorage($type);
      $entity = $entity_storage->load($id);
      $core = $entity->get('_core');
      $entity = $entity_storage->updateFromStorageRecord($entity, $value);
      $entity->set('_core', $core);
      $entity->save();
    }

    // Trigger an event notifying of this change.
    $event = new ConfigRevertEvent($type, $name);
    $this->dispatcher->dispatch($event, ConfigRevertInterface::REVERT);

    return TRUE;
  }

  /**
   * Rewrites configuration entities based on a module config file.
   *
   * @param string $module_name
   *   The name of the module.
   * @param string $config_directory
   *   The directory where the config files are stored (e.g.,
   *   'TideEntityUpdateHelper::INSTALL_DIR' or
   *   'TideEntityUpdateHelper::OPTIONAL_DIR').
   * @param array $form_configs
   *   An array of configuration entity names to be processed.
   */
  public function configMergeDeep($module_name, $config_directory, array $form_configs) {
    // Construct the config file location path.
    \Drupal::moduleHandler()->loadInclude('tide_core', 'inc', 'includes/helpers');
    $config_location = [\Drupal::service('extension.list.module')->getPath($module_name) . $config_directory];

    foreach ($form_configs as $form_config) {
      // Read the config file from the specified location.
      $rewrite = _tide_read_config($form_config, $config_location, FALSE);

      // Get the current configuration entity.
      $config_entity = \Drupal::configFactory()->getEditable($form_config);
      $original_config = $config_entity->getRawData();

      // Merge the original config with the rewritten config.
      $rewritten_config = NestedArray::mergeDeep($original_config, $rewrite);

      // Ensure dependencies are unique and sorted.
      if (!empty($rewritten_config['dependencies'])) {
        $dependencies = $rewritten_config['dependencies'];
        foreach ($dependencies as $type => $items) {
          $uniqueItems = array_unique($items);
          sort($uniqueItems);
          $dependencies[$type] = $uniqueItems;
        }
        $rewritten_config['dependencies'] = $dependencies;
      }

      // Save the updated configuration.
      $config_entity->setData($rewritten_config);
      $config_entity->save();
    }
  }

}
