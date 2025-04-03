<?php

namespace Drupal\tide_landing_page\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'corner_graphic_computed' field type.
 *
 * @FieldType(
 *   id = "corner_graphic_computed",
 *   label = @Translation("corner_graphic_computed"),
 *   description = @Translation("corner_graphic_computed"),
 *   no_ui = TRUE,
 *   list_class = "\Drupal\Core\Field\FieldItemList",
 * )
 */
class ComputedCornerGraphicFieldType extends FieldItemBase {

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
    $properties['site_section_id'] = DataDefinition::create('string')
      ->setLabel(t('Site'))
      ->setRequired(FALSE);
    $properties['top_corner_graphic'] = DataDefinition::create('string')
      ->setLabel(t('Top Corner Graphic'))
      ->setRequired(FALSE);
    $properties['bottom_corner_graphic'] = DataDefinition::create('string')
      ->setLabel(t('Bottom Corner Graphic'))
      ->setRequired(FALSE);
    return $properties;
  }

}
