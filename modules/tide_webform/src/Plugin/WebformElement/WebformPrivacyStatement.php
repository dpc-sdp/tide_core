<?php

namespace Drupal\tide_webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Element\WebformHtmlEditor;
use Drupal\webform\Plugin\WebformElement\Checkbox;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'privacy_statement' element.
 *
 * @WebformElement(
 *   id = "webform_privacy_statement",
 *   default_key = "privacy_statement",
 *   label = @Translation("Privacy statement"),
 *   description = @Translation("Provides a privacy statement element."),
 *   category = @Translation("Advanced elements"),
 * )
 */
class WebformPrivacyStatement extends Checkbox {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    $default = $this->configFactory->get('tide_webform.defaults')->get('privacy_statement');

    $properties = [
      'title' => $default['agreement'],
      'privacy_statement_heading' => $default['heading'],
      'privacy_statement_content' => $default['content'],
      'required' => TRUE,
    ] + parent::getDefaultProperties();
    unset(
      $properties['icheck'],
      $properties['field_prefix'],
      $properties['field_suffix'],
      $properties['description'],
      $properties['description_display'],
      $properties['title_display']
    );
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslatableProperties() {
    return array_merge(parent::getTranslatableProperties(), [
      'privacy_statement_heading',
      'privacy_statement_content',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function initialize(array &$element) {
    // Set default #title.
    if (!isset($element['#title'])) {
      $element['#title'] = $this->getDefaultProperty('title');
    }
    if (!isset($element['#privacy_statement_heading'])) {
      $element['#privacy_statement_heading'] = $this->getDefaultProperty('privacy_statement_heading');
    }

    parent::initialize($element);
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepare($element, $webform_submission);

    if (isset($element['#privacy_statement_content'])) {
      $element['#privacy_statement_content'] = WebformHtmlEditor::checkMarkup($element['#privacy_statement_content']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preview() {
    return [
      '#type' => $this->getTypeName(),
      '#title' => $this->t('I have read and agree to the privacy statement.'),
      '#required' => TRUE,
      '#privacy_statement_heading' => '<em>' . $this->t('Privacy statement') . '</em>',
      '#privacy_statement_content' => '<em>' . $this->t('This is the privacy statement.') . '</em>',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['privacy_statement'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Privacy statement'),
    ];
    $form['privacy_statement']['privacy_statement_heading'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Heading'),
    ];
    $form['privacy_statement']['privacy_statement_content'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Content'),
      '#required' => TRUE,
    ];
    return $form;
  }

}
