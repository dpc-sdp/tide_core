<?php

namespace Drupal\tide_media_file_overwrite\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class Tide Media File OverwriteSettingsForm.
 */
class TideMediaFileOverwriteSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'tide_media_file_overwrite.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tide_media_file_overwrite_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('tide_media_file_overwrite.settings');
    $form['needs_overwritten'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Overwrite upload file if the same file name exists?'),
      '#description' => $this->t('Gives the author option to overwrite file, and keeping the same filename when upload.'),
      '#default_value' => $config->get('needs_overwritten'),
    ];

    // Display the config value as json key value string,
    // read from config and convert it.
    $form['media_form_field_map'] = [
      '#type' => 'textarea',
      '#maxlength' => 255,
      '#size' => 100,
      '#title' => $this->t('Media form field map'),
      '#description' => $this->t('Define a JSON object mapping media bundles to their corresponding fields as key: value pairs. Use the machine names of the media bundle and corresponding field name i.e. {"document":"field_document"}.'),
      '#default_value' => json_encode($config->get('media_form_field_map')),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // User entered config is a json key value string,
    // here we convert it to array before saved.
    $this->config('tide_media_file_overwrite.settings')
      ->set('needs_overwritten', $form_state->getValue('needs_overwritten'))
      ->set('media_form_field_map', json_decode($form_state->getValue('media_form_field_map'), TRUE))
      ->save();
  }

}
