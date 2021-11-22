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

    $form['request_id'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('ID of the Customer Request Type field in JIRA'),
    );

    $form['request_id']['customer_request_type_id'] = array (
      '#type' => 'textfield',
      '#title' => $this->t('Customer Request Type ID'),
    );

    $form['no_account_email'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Email to use when user does not have an active JIRA account.'),
    );

    $form['no_account_email']['no_account_email_value'] = array (
      '#type' => 'textfield',
      '#title' => $this->t('Email'),
    );

    return $form;

  }

}
