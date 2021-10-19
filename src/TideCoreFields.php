<?php

namespace Drupal\tide_core;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\field\Entity\FieldConfig;

/**
 * Defines the tide_core fields.
 *
 * @package Drupal\tide_core.
 */
abstract class TideCoreFields {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Create pre-defined site field for entity, optionally with form display.
   *
   * @param string $field_name
   *   Machine name of the field to create. Must be one of the pre-defined
   *   fields in this class.
   * @param string $entity_type_id
   *   Entity type to create the field for.
   * @param string $bundle
   *   Entity bundle to create the field for.
   * @param bool $create_form_display
   *   Flag to create form display. Defaults to TRUE.
   *
   * @return array
   *   Create field configuration.
   */
  public function provisionField($field_name, $entity_type_id, $bundle, $create_form_display = TRUE) {
    $field_config = $this->getFieldConfig(static::normaliseFieldName($field_name, $entity_type_id, $bundle), $field_name);
    $this->createField($entity_type_id, $bundle, $field_config);
    if ($create_form_display) {
      $display_components = $this->getFormDisplayComponents(static::normaliseFieldName($field_name, $entity_type_id, $bundle), $field_name);
      $this->createFormDisplay($entity_type_id, $bundle, $field_config['field_name'], $display_components);
    }

    return $field_config;
  }

  /**
   * Helper to create a field.
   */
  protected function createField($entity_type_id, $bundle, $config) {
    $field_config = $this->loadFieldConfig($entity_type_id, $bundle, $config['field_name']);
    if (empty($field_config)) {
      $field_config = FieldConfig::create($config + [
        'entity_type' => $entity_type_id,
        'bundle' => $bundle,
      ]);
      $field_config->save();
    }
  }

  /**
   * Helper to load field config by field name.
   *
   * @return \Drupal\field\Entity\FieldConfig
   *   Field config.
   */
  protected function loadFieldConfig($entity_type_id, $bundle, $field_name) {
    return $this->entityTypeManager->getStorage('field_config')->load($entity_type_id . '.' . $bundle . '.' . $field_name);
  }

  /**
   * Helper to create form display.
   */
  protected function createFormDisplay($entity_type_id, $bundle, $field_name, $components, $mode = 'default') {
    /** @var \Drupal\Core\Entity\EntityDisplayBase $form_display */
    $form_display = $this->loadFormDisplay($entity_type_id, $bundle, $mode);

    if (!$form_display) {
      /** @var \Drupal\Core\Entity\Entity\EntityFormDisplay $storage */
      $storage = $this->entityTypeManager->getStorage('entity_form_display');
      $form_display = $storage->create([
        'targetEntityType' => $entity_type_id,
        'bundle' => $bundle,
        'mode' => $mode,
        'status' => TRUE,
      ]);
    }

    $form_display->setComponent($field_name, $components)->save();
  }

  /**
   * Helper to load form display config.
   *
   * @return \Drupal\Core\Entity\Entity\EntityFormDisplay
   *   Entity form display.
   */
  protected function loadFormDisplay($entity_type_id, $bundle, $mode = 'default') {
    return $this->entityTypeManager->getStorage('entity_form_display')->load($entity_type_id . '.' . $bundle . '.' . $mode);
  }

  /**
   * Get field config by name.
   *
   * This supports granular per-field config as well as generic config.
   *
   * @return \Drupal\field\Entity\FieldConfig
   *   Field config.
   */
  protected function getFieldConfig($field_name, $field_name_generic) {
    try {
      // Get config for field as is.
      $config = $this->callOwnGetter($field_name, 'Config');
    }
    catch (\RuntimeException $exception) {
      // If field config was not found, use generic config.
      $field_name_generic = static::normaliseFieldName($field_name_generic);
      $config = $this->callOwnGetter($field_name_generic, 'Config');
    }
    $config['field_name'] = $field_name;

    return $config;
  }

  /**
   * Get form display components by name.
   */
  protected function getFormDisplayComponents($field_name, $field_name_generic) {
    try {
      // Get config for field as is.
      $config = $this->callOwnGetter($field_name, 'FormDisplayComponents');
    }
    catch (\RuntimeException $exception) {
      // If field config was not found, use generic config.
      $field_name_generic = static::normaliseFieldName($field_name_generic);
      $config = $this->callOwnGetter($field_name_generic, 'FormDisplayComponents');
    }
    $config['field_name'] = $field_name;

    return $config;
  }

  /**
   * Helper to convert field name to machine name.
   */
  public static function normaliseFieldName($field_name, $entity_type_id = '', $bundle = '') {
    return $field_name;
  }

  /**
   * Helper to call a getter method within this class.
   *
   * @param string $prefix
   *   Method prefix. Snake case converted to upper camel case.
   * @param string $suffix
   *   Method suffix as upper camel case.
   *
   * @return mixed
   *   Method value.
   *
   * @throws \RuntimeException
   *   In case if method does not exist.
   */
  protected function callOwnGetter($prefix, $suffix) {
    $method = 'get' . implode('', array_map('\Drupal\Component\Utility\Unicode::ucfirst', explode('_', $prefix))) . $suffix;
    if (!method_exists($this, $method)) {
      throw new \RuntimeException(sprintf('Invalid method %s requested', $method));
    }

    return $this->{$method}();
  }

}
