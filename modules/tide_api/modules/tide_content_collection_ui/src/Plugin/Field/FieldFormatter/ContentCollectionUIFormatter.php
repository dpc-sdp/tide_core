<?php

namespace Drupal\tide_content_collection_ui\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Plugin implementation of the 'tide_content_collection_ui_formatter' formatter.
 */
#[FieldFormatter(
  id: 'tide_content_collection_ui_formatter',
  label: new TranslatableMarkup('Tide Content Collection UI'),
  field_types: ['tide_content_collection_ui'],
)]
class ContentCollectionUIFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#type' => 'markup',
        '#markup' => $this->t('<svg xmlns="http://www.w3.org/2000/svg" width="68" height="77" fill="none"><path fill="#fff" d="M0 0h68v77H0z"/><path fill="#C4C4C4" fill-rule="evenodd" d="M48.232 11H16.768c-1.743 0-3.192 0-4.38.098-4.597.377-7.922 3.81-8.29 8.33C4 20.621 4 22.084 4 23.828v28.342c0 1.75 0 3.207.098 4.402.097 1.243.319 2.408.88 3.511a8.979 8.979 0 0 0 3.914 3.934c1.098.563 2.257.786 3.495.884 1.189.098 2.638.098 4.378.098h31.47c1.74 0 3.192 0 4.378-.098 1.238-.098 2.397-.32 3.495-.884.554-.308.967-.82 1.153-1.428a2.465 2.465 0 0 0-.16-1.833 2.432 2.432 0 0 0-3.209-1.111c-.295.15-.742.288-1.68.363-.96.079-2.215.082-4.078.082H16.866c-1.863 0-3.117 0-4.082-.082-.934-.075-1.38-.213-1.674-.363a4.081 4.081 0 0 1-1.781-1.79c-.15-.295-.287-.743-.362-1.686-.078-.965-.081-2.225-.081-4.097V25.727h47.183c.026 1.364.04 2.727.045 4.091 0 .651.258 1.275.716 1.736a2.437 2.437 0 0 0 3.455 0A2.46 2.46 0 0 0 61 29.818c0-.61-.003-1.22-.01-1.826-.023-2.193-.088-5.914-.296-8.568-.368-4.66-3.492-7.953-8.081-8.326C51.424 11 49.975 11 48.232 11Zm7.66 9.818c-.156-2.608-.905-4.601-3.68-4.827-.96-.079-2.215-.082-4.078-.082H16.866c-1.863 0-3.117 0-4.082.082-2.664.22-3.788 2.225-3.872 4.827h46.98Z" clip-rule="evenodd"/><path fill="#A2A2A2" d="M16.4 29c-.636 0-1.247.263-1.697.732A2.554 2.554 0 0 0 14 31.5c0 .663.253 1.299.703 1.768.45.469 1.06.732 1.697.732h19.2c.636 0 1.247-.263 1.697-.732.45-.47.703-1.105.703-1.768s-.253-1.299-.703-1.768A2.353 2.353 0 0 0 35.6 29H16.4Z"/><path fill="#A2A2A2" fill-rule="evenodd" d="M50.804 32A10.808 10.808 0 0 0 40.41 45.754a10.805 10.805 0 0 0 11.375 7.815c1.664-.151 3.27-.686 4.693-1.562l4.203 4.2a2.495 2.495 0 1 0 3.525-3.525l-4.2-4.203A10.805 10.805 0 0 0 50.804 32Zm-5.82 10.806a5.818 5.818 0 0 1 9.935-4.114 5.818 5.818 0 0 1-4.115 9.933 5.82 5.82 0 0 1-5.82-5.819Z" clip-rule="evenodd"/><path fill="#A2A2A2" d="M16.423 36a2.423 2.423 0 1 0 0 4.846h9.692a2.423 2.423 0 0 0 0-4.846h-9.692Zm0 9.692a2.423 2.423 0 1 0 0 4.846h16.154a2.423 2.423 0 1 0 0-4.846H16.423Zm0 6.462a2.423 2.423 0 1 0 0 4.846h9.692a2.423 2.423 0 0 0 0-4.846h-9.692Z"/></svg>'),
      ];
    }

    return $elements;
  }

}
