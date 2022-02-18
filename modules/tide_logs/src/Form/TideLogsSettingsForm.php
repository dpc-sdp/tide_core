<?php

namespace Drupal\tide_logs\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\tide_logs\Logger\TideLogsLoggerFactory;

/**
 * Settings form for tide_logs.
 */
class TideLogsSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['tide_logs.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tide_logs_settings_form';
  }

  /**
   * Build the form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('tide_logs.settings');

    $form['enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable module'),
      '#description' => $this->t('Send logs to SumoLogic.'),
      '#default_value' => $config->get('enable'),
    ];

    $form['description'] = [
      '#prefix' => '<div class="ll-settings-description">',
      '#suffix' => '</div>',
      '#markup' => $this->t(
        '<p>Current settings for the Tide Logs module. The defaults are set in configuration, this page is meant primarily for troubleshooting.</p>' .
        '<ul>' .
          '<li><b>' . $this->t('SumoLogic category') . ':</b> ' . TideLogsLoggerFactory::getSumoLogicCategory($this->configFactory()) . '</li>' .
        '</ul>'
      ),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('tide_logs.settings')
      ->set('enable', $form_state->getValue('enable'))
      ->save();
  }

}
