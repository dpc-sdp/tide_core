<?php

namespace Drupal\tide_core\Plugin\Field\FieldWidget;

use Drupal\paragraphs\Plugin\Field\FieldWidget\ParagraphsWidget;
use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'entity_reference paragraphs' widget.
 *
 * We hide add / remove buttons when translating to avoid accidental loss of
 * data because these actions effect all languages.
 *
 * @FieldWidget(
 *   id = "preset_paragraphs",
 *   label = @Translation("Preset Paragraphs"),
 *   description = @Translation("A preset paragraphs inline form widget."),
 *   field_types = {
 *     "entity_reference_revisions"
 *   }
 * )
 */
class PresetParagraphsWidget extends ParagraphsWidget {

  /**
   * Indicates whether the current widget instance is in translation.
   *
   * @var bool
   */
  protected $isTranslating;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'preset_number' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['preset_number'] = [
      '#type' => 'number',
      '#title' => 'Default number of paragraphs',
      '#description' => t('Set the default number of paragraphs.'),
      '#min' => 1,
      '#default_value' => $this->getSetting('preset_number'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $summary[] = t('Number of paragraphs: @number', ['@number' => $this->getSetting('preset_number')]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();
    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    $this->fieldParents = $form['#parents'];
    $field_state = static::getWidgetState($this->fieldParents, $field_name, $form_state);

    $max = $field_state['items_count'];
    $entity_type_manager = \Drupal::entityTypeManager();

    // Consider adding a default paragraph for new host entities.
    if ($max == 0 && $items->getEntity()->isNew()) {
      $default_type = $this->getDefaultParagraphTypeMachineName();

      // Checking if default_type is not none and if is allowed.
      if ($default_type) {
        // Place the default paragraph.
        $target_type = $this->getFieldSetting('target_type');
        $paragraphs_entity = $entity_type_manager->getStorage($target_type)->create([
          'type' => $default_type,
        ]);
        $field_state['selected_bundle'] = $default_type;
        $display = EntityFormDisplay::collectRenderDisplay($paragraphs_entity, $this->getSetting('form_display_mode'));
        $field_state['paragraphs'][0] = [
          'entity' => $paragraphs_entity,
          'display' => $display,
          'mode' => 'edit',
          'original_delta' => 1,
        ];
        $max = $this->getSetting('preset_number');
        $field_state['items_count'] = $max;
      }
    }

    $this->realItemCount = $max;
    $is_multiple = $this->fieldDefinition->getFieldStorageDefinition()->isMultiple();

    $field_title = $this->fieldDefinition->getLabel();
    $description = FieldFilteredMarkup::create(\Drupal::token()->replace($this->fieldDefinition->getDescription()));

    $elements = [];
    $tabs = '';
    $this->fieldIdPrefix = implode('-', array_merge($this->fieldParents, [$field_name]));
    $this->fieldWrapperId = Html::getUniqueId($this->fieldIdPrefix . '-add-more-wrapper');

    // If the parent entity is paragraph add the nested class if not then add
    // the perspective tabs.
    $field_prefix = strtr($this->fieldIdPrefix, '_', '-');
    if (count($this->fieldParents) == 0) {
      if ($items->getEntity()->getEntityTypeId() != 'paragraph') {
        $tabs = '<ul class="paragraphs-tabs tabs primary clearfix"><li id="content" class="tabs__tab"><a href="#' . $field_prefix . '-values">Content</a></li><li id="behavior" class="tabs__tab"><a href="#' . $field_prefix . '-values">Behavior</a></li></ul>';
      }
    }
    if (count($this->fieldParents) > 0) {
      if ($items->getEntity()->getEntityTypeId() === 'paragraph') {
        $form['#attributes']['class'][] = 'paragraphs-nested';
      }
    }
    $elements['#prefix'] = '<div class="is-horizontal paragraphs-tabs-wrapper" id="' . $this->fieldWrapperId . '">' . $tabs;
    $elements['#suffix'] = '</div>';

    $field_state['ajax_wrapper_id'] = $this->fieldWrapperId;
    // Persist the widget state so formElement() can access it.
    static::setWidgetState($this->fieldParents, $field_name, $form_state, $field_state);

    $header_actions = $this->buildHeaderActions($field_state, $form_state);
    if ($header_actions) {
      $elements['header_actions'] = $header_actions;
      // Add a weight element so we guaranty that header actions will stay in
      // first row. We will use this later in
      // paragraphs_preprocess_field_multiple_value_form().
      $elements['header_actions']['_weight'] = [
        '#type' => 'weight',
        '#default_value' => -100,
      ];
    }

    if (!empty($field_state['dragdrop'])) {
      $elements['#attached']['library'][] = 'paragraphs/paragraphs-dragdrop';
      $elements['dragdrop'] = $this->buildNestedParagraphsFoDragDrop($form_state, NULL, []);
      return $elements;
    }

    if ($max > 0) {
      for ($delta = 0; $delta < $max; $delta++) {

        // Add a new empty item if it doesn't exist yet at this delta.
        if (!isset($items[$delta])) {
          $items->appendItem();
        }

        // For multiple fields, title and description are handled by the
        // wrapping table.
        $element = [
          '#title' => $is_multiple ? '' : $field_title,
          '#description' => $is_multiple ? '' : $description,
        ];
        $element = $this->formSingleElement($items, $delta, $element, $form, $form_state);

        if ($element) {
          // Input field for the delta (drag-n-drop reordering).
          if ($is_multiple) {
            // We name the element '_weight' to avoid clashing with elements
            // defined by widget.
            $element['_weight'] = [
              '#type' => 'weight',
              '#title' => $this->t('Weight for row @number', ['@number' => $delta + 1]),
              '#title_display' => 'invisible',
              // Note: this 'delta' is the FAPI 'weight' element's property.
              '#delta' => $max,
              '#default_value' => $items[$delta]->_weight ?: $delta,
              '#weight' => 100,
            ];
          }

          // Access for the top element is set to FALSE only when the paragraph
          // was removed. A paragraphs that a user can not edit has access on
          // lower level.
          if (isset($element['#access']) && !$element['#access']) {
            $this->realItemCount--;
          }
          else {
            $elements[$delta] = $element;
          }
        }
      }
    }

    $field_state = static::getWidgetState($this->fieldParents, $field_name, $form_state);
    $field_state['real_item_count'] = $this->realItemCount;
    $field_state['add_mode'] = $this->getSetting('add_mode');
    static::setWidgetState($this->fieldParents, $field_name, $form_state, $field_state);

    $elements += [
      '#element_validate' => [[$this, 'multipleElementValidate']],
      '#required' => $this->fieldDefinition->isRequired(),
      '#field_name' => $field_name,
      '#cardinality' => $cardinality,
      '#max_delta' => $max - 1,
    ];

    if ($this->realItemCount > 0) {
      $elements += [
        '#theme' => 'field_multiple_value_form',
        '#cardinality_multiple' => $is_multiple,
        '#title' => $field_title,
        '#description' => $description,
      ];

    }
    else {
      $classes = $this->fieldDefinition->isRequired() ? ['form-required'] : [];
      $elements += [
        '#type' => 'container',
        '#theme_wrappers' => ['container'],
        '#cardinality_multiple' => TRUE,
        'title' => [
          '#type' => 'html_tag',
          '#tag' => 'strong',
          '#value' => $field_title,
          '#attributes' => ['class' => $classes],
        ],
        'text' => [
          '#type' => 'container',
          'value' => [
            '#markup' => $this->t('No @title added yet.', ['@title' => $this->getSetting('title')]),
            '#prefix' => '<em>',
            '#suffix' => '</em>',
          ],
        ],
      ];

      if ($description) {
        $elements['description'] = [
          '#type' => 'container',
          'value' => ['#markup' => $description],
          '#attributes' => ['class' => ['description']],
        ];
      }
    }

    $host = $items->getEntity();
    $this->initIsTranslating($form_state, $host);

    if (($this->realItemCount < $cardinality || $cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) && !$form_state->isProgrammed() && !$this->isTranslating) {
      $elements['add_more'] = $this->buildAddActions();
    }

    $elements['#attached']['library'][] = 'paragraphs/drupal.paragraphs.widget';

    return $elements;
  }

}
