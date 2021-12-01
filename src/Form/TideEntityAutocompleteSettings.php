<?php

namespace Drupal\tide_core\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;

/**
 * Configure Tide entity reference auto complete.
 */
class TideEntityAutocompleteSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['tide_entity_autocomplete.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tide-entity-autocomplete';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('tide_entity_autocomplete.settings');
    $form['tide_entity_autocomplete'] = [
      '#type' => 'details',
      '#title' => t('Auto complete settings'),
      '#open' => TRUE,
    ];

    $form['tide_entity_autocomplete']['limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Limit'),
      '#min' => 0,
      '#default_value' => $config->get('limit') ? $config->get('limit') : 100,
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->config('tide_entity_autocomplete.settings')
      ->set('limit', $form_state->getValue('limit'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
