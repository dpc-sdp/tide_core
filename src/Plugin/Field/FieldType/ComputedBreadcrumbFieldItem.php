<?php

namespace Drupal\tide_core\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'breadcrumb_computed' field type.
 *
 * @FieldType(
 *   id = "breadcrumb_computed",
 *   label = @Translation("Breadcrumbs (computed)"),
 *   description = @Translation("Computed breadcrumbs"),
 *   no_ui = TRUE,
 *   list_class = "\Drupal\Core\Field\FieldItemList",
 * )
 */
class ComputedBreadcrumbFieldItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['url'] = DataDefinition::create('string')
      ->setLabel(t('URL'))
      ->setRequired(TRUE);
    $properties['name'] = DataDefinition::create('string')
      ->setLabel(t('Name'))
      ->setRequired(TRUE);
    return $properties;
  }

}
