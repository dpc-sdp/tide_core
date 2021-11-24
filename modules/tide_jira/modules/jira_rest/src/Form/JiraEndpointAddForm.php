<?php

namespace Drupal\jira_rest\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Class KeyAddForm.
 *
 * @package Drupal\key\Form
 */
class JiraEndpointAddForm extends JiraEndpointFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // See if any other Endpoints exist.
    /** @var integer $endpoint_total */
    $endpoint_total = \Drupal::entityQuery('jira_endpoint')->count()->execute();

    $form['default'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Default'),
      '#maxlength' => 255,
      '#default_value' => $endpoint_total === 0 ? TRUE : FALSE,
      '#required' => TRUE,
      '#disabled' => TRUE,
    ];
    return parent::buildForm($form, $form_state);
  }

}
