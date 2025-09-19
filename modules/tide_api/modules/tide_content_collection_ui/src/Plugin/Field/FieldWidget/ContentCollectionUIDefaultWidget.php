<?php

namespace Drupal\tide_content_collection_ui\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

/**
 * Plugin implementation of the 'tide_content_collection_ui_default' widget.
 */
#[FieldWidget(
  id: 'tide_content_collection_ui_default',
  label: new TranslatableMarkup('Tide Content Collection UI'),
  field_types: ['tide_content_collection_ui'],
)]
class ContentCollectionUIDefaultWidget extends WidgetBase {

  /**
   * Processes the provided JSON-encoded value and constructs a config array.
   *
   * This is used to 'seed' the application with any necessary data that isn't available in the saved JSON blob.
   * For example, page IDs are saved in the blob but not the page titles as these may change.
   */
  private function getConfig($value = null): array {
    $config = [];
    $value = json_decode($value ?? '{}', TRUE);

    if ($value && is_array($value['manualItems']) && count($value['manualItems']) > 0) {
      $id_map = [];
      $node_ids = array_map('intval', $value['manualItems']);

      $nodes = Node::loadMultiple($node_ids);

      foreach ($nodes as $node) {
        if ($node instanceof NodeInterface) {
          $id_map[$node->id()] = $node->getTitle();
        }
      }

      $config['contentMap'] = $id_map;
    }

    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    // Get the index of the parent field.
    $index = $element['#field_parents'][1] ?? 0;

    // Setup any needed Drupal supplied data.
    $config = $this->getConfig($items[$delta]->value);

    // Add the application container.
    $element['tide_content_collection_ui'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'id' => 'content-collection-app-' . $index,
        'class' => ['content-collection-app'],
        'data-type' => $form["#entity_type"] ?? 'default',
        'data-config' => json_encode($config),
        'data-index' => "{$index}",
      ],
    ];

    // Add a hidden input to store the JSON data.
    $element['value'] = [
      '#type' => 'hidden',
      '#default_value' => $items[$delta]->value ?? '{}',
      '#attributes' => [
        'id' => 'content-collection-value-' . $index,
        'class' => ['content-collection-value'],
      ],
      '#element_validate' => [
        [static::class, 'validateJSON'],
      ],
      '#required' => TRUE
    ];

    // Attach the application.
    //$element['#attached']['library'][] = 'tide_content_collection_ui/tide_content_collection_ui';

    return $element;
  }

  /**
   * Element validator for the hidden JSON value.
   *
   * @param array $element
   *   The form element render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array $form
   *   The complete form render array.
   */
  public static function validateJSON(array &$element, FormStateInterface $form_state, array &$form): void {
    $value = $form_state->getValue($element['#parents']);
    $data = json_decode($value, TRUE);

    // Make sure we have a source selected.
    if (!$data || !is_array($data) || !isset($data['source'])) {
      $form_state->setError($element, new TranslatableMarkup('Please select a content source.'));
      return;
    }

    // Make sure we have a content type selected when using the 'auto' source.
    if ($data['source'] === 'auto' && (!isset($data['contentType']) || !$data['contentType'])) {
      $form_state->setError($element, new TranslatableMarkup('Please select a content type.'));
    }

    // Make sure we have at least one manual item selected.
    if (
      $data['source'] === 'manual' &&
      (
        !isset($data['manualItems']) ||
        !is_array($data['manualItems']) ||
        count($data['manualItems']) === 0 ||
        (count($data['manualItems']) === 1 && !$data['manualItems'][0])
      )
    ) {
        $form_state->setError($element, new TranslatableMarkup('Please add at least one content item.'));
    }
  }

}
