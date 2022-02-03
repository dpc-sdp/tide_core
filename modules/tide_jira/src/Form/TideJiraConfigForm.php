<?php

namespace Drupal\tide_jira\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Tide Jira Config Form.
 */
class TideJiraConfigForm extends ConfigFormBase {

  /**
   * Returns the config form ID.
   *
   * @return string
   *   The config form ID.
   */
  public function getFormId() {
    return 'tide_jira_config_form';
  }

  /**
   * Returns config ID to be updated.
   *
   * @return string[]
   *   The config ID.
   */
  public function getEditableConfigNames() {
    return [
      'tide_jira.settings',
    ];
  }

  /**
   * Build the config form.
   *
   * @param \array $form
   *   Config form definition.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   Config form state.
   *
   * @return array
   *   Config form render array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('tide_jira.settings');
    $form['request'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Service Request Field Configuration'),
    ];

    $form['request']['customer_request_type_field_id'] = [
      '#type' => 'textfield',
      '#default_value' => $config->get('customer_request_type_field_id'),
      '#title' => $this->t('Customer Request Type Field ID'),
    ];

    $form['request']['customer_request_type_id'] = [
      '#type' => 'textfield',
      '#default_value' => $config->get('customer_request_type_id'),
      '#title' => $this->t('Customer Request Type ID'),
    ];

    $form['request']['issue_type'] = [
      '#type' => 'textfield',
      '#default_value' => $config->get('issue_type'),
      '#title' => $this->t('Issue Type'),
    ];

    $form['no_account_email'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Email to use when user does not have an active JIRA account.'),
    ];

    $form['no_account_email']['no_account_email_value'] = [
      '#type' => 'email',
      '#default_value' => $config->get('no_account_email'),
      '#title' => $this->t('Email'),
    ];

    return $form;

  }

  /**
   * Handles form submissions and config update.
   *
   * @param \array $form
   *   The config form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   State of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->config('tide_jira.settings');
    $config->set('customer_request_type_field_id', $form_state->getValue('customer_request_type_field_id'));
    $config->set('customer_request_type_id', $form_state->getValue('customer_request_type_id'));
    $config->set('no_account_email', $form_state->getValue('no_account_email_value'));
    $config->set('issue_type', $form_state->getValue('issue_type'));
    $config->save();

  }

}
