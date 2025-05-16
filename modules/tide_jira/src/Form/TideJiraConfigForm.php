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

    $form['field_mappings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Jira field mappings'),
    ];

    $form['field_mappings']['content_type'] = [
      '#type' => 'textfield',
      '#default_value' => $config->get('content_type'),
      '#title' => $this->t('Content Type Field ID'),
    ];

    $form['field_mappings']['node_id'] = [
      '#type' => 'textfield',
      '#default_value' => $config->get('node_id'),
      '#title' => $this->t('Node ID Field ID'),
    ];

    $form['field_mappings']['site'] = [
      '#type' => 'textfield',
      '#default_value' => $config->get('site'),
      '#title' => $this->t('Site Field ID'),
    ];

    $form['field_mappings']['site_section'] = [
      '#type' => 'textfield',
      '#default_value' => $config->get('site_section'),
      '#title' => $this->t('Site Section Field ID'),
    ];

    $form['field_mappings']['page_department'] = [
      '#type' => 'textfield',
      '#default_value' => $config->get('page_department'),
      '#title' => $this->t('Page Department Field ID'),
    ];

    $form['field_mappings']['editor_department'] = [
      '#type' => 'textfield',
      '#default_value' => $config->get('editor_department'),
      '#title' => $this->t('Editor Department Field ID'),
    ];

    $form['field_mappings']['fallback_department'] = [
      '#type' => 'textfield',
      '#default_value' => $config->get('fallback_department'),
      '#title' => $this->t('Fallback department'),
      '#description' => $this->t('This is the department ID (taxonomy ID) that will be used if no other department is available.'),
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
    $config->set('content_type', $form_state->getValue('content_type'));
    $config->set('node_id', $form_state->getValue('node_id'));
    $config->set('site', $form_state->getValue('site'));
    $config->set('site_section', $form_state->getValue('site_section'));
    $config->set('page_department', $form_state->getValue('page_department'));
    $config->set('editor_department', $form_state->getValue('editor_department'));
    $config->set('fallback_department', $form_state->getValue('fallback_department'));
    $config->save();

  }

}
