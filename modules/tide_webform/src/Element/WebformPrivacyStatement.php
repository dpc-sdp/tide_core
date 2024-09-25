<?php

namespace Drupal\tide_webform\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Checkbox;

/**
 * Provides a webform Privacy Statement element.
 *
 * @FormElement("webform_privacy_statement")
 */
class WebformPrivacyStatement extends Checkbox {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#return_value' => TRUE,
      '#privacy_statement_heading' => '',
      '#privacy_statement_content' => '',
    ] + parent::getInfo();
  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderCheckbox($element) {
    $element = parent::preRenderCheckbox($element);
    $id = 'webform-privacy-statement-' . implode('_', $element['#parents']);

    if (empty($element['#title'])) {
      $element['#title'] = (string) t('I have read and understood the privacy statement.');
    }

    $element['#description_display'] = 'before';

    // Change description to render array.
    if (isset($element['#description'])) {
      $element['#description'] = ['description' => (is_array($element['#description'])) ? $element['#description'] : ['#markup' => $element['#description']]];
    }
    else {
      $element['#description'] = [];
    }

    // Add privacy_statement to #description.
    $element['#description']['privacy_statement'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => $id . '--description',
        'class' => ['webform-privacy-statement-details'],
      ],
    ];
    if (!empty($element['#privacy_statement_heading'])) {
      $element['#description']['privacy_statement']['heading'] = [
        '#type' => 'container',
        '#markup' => $element['#privacy_statement_heading'],
        '#attributes' => [
          'class' => ['webform-privacy-statement-details--heading'],
        ],
      ];
    }
    if (!empty($element['#privacy_statement_content'])) {
      $element['#description']['privacy_statement']['content'] = (is_array($element['#privacy_statement_content'])) ? $element['#privacy_statement_content'] : ['#markup' => $element['#privacy_statement_content']];
      $element['#description']['privacy_statement']['content'] += [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['webform-privacy-statement-details--content'],
        ],
      ];
    }

    // Change #type to checkbox so that element is rendered correctly.
    $element['#type'] = 'checkbox';
    $element['#wrapper_attributes']['class'][] = 'form-type-webform-privacy-statement';

    $element['#element_validate'][] = [
      get_called_class(),
      'validateWebformPrivacyStatement',
    ];

    return $element;
  }

  /**
   * Webform element validation handler for webform privacy statement element.
   */
  public static function validateWebformPrivacyStatement(&$element, FormStateInterface $form_state, &$complete_form) {
    $value = (bool) $form_state->getValue($element['#parents'], []);
    $element['#value'] = $value;
    $form_state->setValueForElement($element, $value);
  }

}
