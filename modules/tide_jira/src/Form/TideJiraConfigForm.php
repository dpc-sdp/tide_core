<?php

namespace Drupal\tide_jira\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class TideJiraConfigForm extends ConfigFormBase {

  public function getFormId() {
    return 'tide_jira_config_form';
  }

  public function getEditableConfigNames() {
    return [
      'tide_jira.settings'
    ];
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('tide_jira.settings');
    $form['request'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Service Request Field Configuration'),
    );

    $form['request']['customer_request_type_field_id'] = array (
      '#type' => 'textfield',
      '#default_value' => $config->get('customer_request_type_field_id'),
      '#title' => $this->t('Customer Request Type Field ID'),
    );

    $form['request']['customer_request_type_id'] = array (
      '#type' => 'textfield',
      '#default_value' => $config->get('customer_request_type_id'),
      '#title' => $this->t('Customer Request Type ID'),
    );

    $form['request']['issue_type'] = array (
      '#type' => 'textfield',
      '#default_value' => $config->get('issue_type'),
      '#title' => $this->t('Issue Type'),
    );

    $form['no_account_email'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Email to use when user does not have an active JIRA account.'),
    );

    $form['no_account_email']['no_account_email_value'] = array (
      '#type' => 'email',
      '#default_value' => $config->get('no_account_email'),
      '#title' => $this->t('Email'),
    );

    return $form;

  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

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
