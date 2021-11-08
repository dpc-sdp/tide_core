<?php

namespace Drupal\jira_rest\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Class KeyEditForm.
 *
 * @package Drupal\key\Form
 */
class JiraEndpointEditForm extends JiraEndpointFormBase {

  /**
   * Keeps track of extra confirmation step on key edit.
   *
   * @var bool
   */
  protected $editConfirmed = FALSE;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form['#title'] = $this->t('Edit JIRA Endpoint %label', ['%label' => $this->entity->label()]);
    return parent::form($form, $form_state);
  }
}
