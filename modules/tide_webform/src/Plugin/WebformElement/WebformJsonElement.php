<?php

namespace Drupal\tide_webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElementBase;

/**
 * Provides a 'webform_json_element' element.
 *
 * @WebformElement(
 * id = "webform_json_element",
 * label = @Translation("Json data"),
 * description = @Translation("A custom element for passing unstructured JSON configuration to the API. Ideal for Data Driven Component integrations."),
 * category = @Translation("Custom"),
 * )
 */
class WebformJsonElement extends WebformElementBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return ['json_payload' => ''] + parent::getDefaultProperties();
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['custom_data'] = [
      '#type' => 'details',
      '#title' => $this->t('Custom JSON Data'),
      '#open' => TRUE,
    ];
    $form['custom_data']['json_payload'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'json',
      '#title' => $this->t('JSON Configuration'),
      '#required' => TRUE,
      '#default_value' => $this->getElementProperty($form_state->getValues(), 'json_payload'),
      '#element_validate' => [[get_class($this), 'validateJson']],
    ];
    return $form;
  }

  /**
   * Form element validation handler for the JSON payload.
   *
   * @param array $element
   *   The render element being validated.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function validateJson(array &$element, FormStateInterface $form_state) {
    $value = $element['#value'];
    if (!empty($value) && is_null(json_decode($value))) {
      $form_state->setError(
        $element,
        t('Invalid JSON: @error', [
          '@error' => json_last_error_msg(),
        ])
      );
    }
  }

}
