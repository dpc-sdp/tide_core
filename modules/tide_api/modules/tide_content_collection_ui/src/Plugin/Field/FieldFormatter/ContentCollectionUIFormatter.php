<?php

namespace Drupal\tide_content_collection_ui\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Implementation of the 'tide_content_collection_ui_formatter' formatter.
 */
#[FieldFormatter(
  id: 'tide_content_collection_ui_formatter',
  label: new TranslatableMarkup('Content Collection'),
  field_types: ['tide_content_collection_ui'],
)]
class ContentCollectionUIFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items): array {
    $elements = [];

    foreach ($items as $delta => $item) {
      $form = json_decode($item->value ?? '{}');

      $elements[$delta] = [
        '#type' => 'markup',
        '#markup' => $form ? ucfirst($form->displayType) : '',
      ];
    }

    return $elements;
  }

}
