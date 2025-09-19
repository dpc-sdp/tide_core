<?php

namespace Drupal\tide_content_collection_ui\Plugin\Field\FieldType;

use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'tide_content_collection_ui' field type.
 */
#[FieldType(
  id: "tide_content_collection_ui",
  label: new TranslatableMarkup("Content Collection JSON"),
  description: new TranslatableMarkup("Field to store Content Collection UI config as JSON."),
  default_widget: "tide_content_collection_ui_default",
  default_formatter: "tide_content_collection_ui_formatter"
)]
class ContentCollectionUIItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition): array {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('JSON'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition): array {
    return [
      'columns' => [
        'value' => [
          'type' => 'text',
          'size' => 'big',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty(): bool {
    $value = $this->get('value')->getValue();

    return $value === NULL || $value === '';
  }

}
