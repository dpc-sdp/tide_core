<?php

namespace Drupal\tide_webform;

use Drupal\user\Entity\Role;

/**
 * Helper class for install/update functions.
 */
class TideOperation {

  /**
   * Ensure that unsupported elements are hidden.
   */
  public static function installWebformConfig() {
    if (\Drupal::moduleHandler()->moduleExists('webform')) {
      $webform_elements = [
        'checkboxes',
        'color',
        'container',
        'datelist',
        'datetime',
        'details',
        'entity_autocomplete',
        'fieldset',
        'item',
        'language_select',
        'managed_file',
        'range',
        'search',
        'tableselect',
        'text_format',
        'value',
        'view',
        'webform_address',
        'webform_audio_file',
        'webform_autocomplete',
        'webform_checkboxes_other',
        'webform_codemirror',
        'webform_computed_token',
        'webform_computed_twig',
        'webform_contact',
        'webform_custom_composite',
        'webform_document_file',
        'webform_element',
        'webform_email_confirm',
        'webform_email_multiple',
        'webform_entity_checkboxes',
        'webform_entity_radios',
        'webform_entity_select',
        'webform_flexbox',
        'webform_image_file',
        'webform_likert',
        'webform_link',
        'webform_location_places',
        'webform_mapping',
        'webform_message',
        'webform_more',
        'webform_name',
        'webform_radios_other',
        'webform_rating',
        'webform_same',
        'webform_scale',
        'webform_section',
        'webform_select_other',
        'webform_signature',
        'webform_table',
        'webform_table_row',
        'webform_table_sort',
        'webform_tableselect_sort',
        'webform_telephone',
        'webform_term_checkboxes',
        'webform_terms_of_service',
        'webform_time',
        'webform_variant',
        'webform_video_file',
        'webform_wizard_page',
      ];
      $config_factory = \Drupal::configFactory();
      $config = $config_factory->getEditable('webform.settings');
      $excluded_elements_value = $config->get('element.excluded_elements');
      foreach ($webform_elements as $webform_element) {
        $excluded_elements_value[$webform_element] = $webform_element;
      }
      $config->set('element.excluded_elements', $excluded_elements_value);
      $config->save();
    }
  }

  /**
   * Assign permission to access text editor in webforms.
   */
  public static function accessTextEditorInWebform() {
    $role_ids = ['editor', 'approver', 'site_admin', 'contributor'];
    foreach ($role_ids as $role_id) {
      $role = Role::load($role_id);
      if ($role) {
        $role->grantPermission('use text format webform_default')->save();
      }
    }
  }

}
