<?php

namespace Drupal\tide_core\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the configuration form for setting the log retention days.
 */
class AuditTrailSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    // This returns the name of the config object.
    return ['tide_core.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    // The form ID for this form.
    return 'tide_core_log_retention_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Load the configuration data.
    $config = $this->config('tide_core.settings');

    // Add a text field to specify the number of days for log retention.
    $form['log_retention_days'] = [
      '#type' => 'number',
      '#title' => $this->t('Log retention days'),
      '#description' => $this->t('Enter the number of days after which logs should be deleted.'),
    // Default to 30 if not set.
      '#default_value' => $config->get('log_retention_days', 30),
      '#min' => 1,
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('tide_core.settings')
      ->set('log_retention_days', $form_state->getValue('log_retention_days'))
      ->save();

    $this->messenger()->addMessage($this->t('The log retention days have been updated.'));
    parent::submitForm($form, $form_state);
  }

}
