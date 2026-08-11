<?php

namespace Drupal\tide_media_file_overwrite\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Hook implementations for the tide media file overwrite module.
 */
class TideMediaFileOverwriteHooks {

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match) {
    switch ($route_name) {
      // Main module help for the tide_media_file_overwrite module.
      case 'help.page.tide_media_file_overwrite':
        $output = '';
        $output .= '<h3>' . t('About') . '</h3>';
        $output .= '<p>' . t('Gives the author option to a overwrite file, and keeping the same filename when upload. This is an override of the default Drupal behaviour where a file is uploaded as a new file with a number appended to the file name, eg my-file-title_1.pdf. This module can be set to Override or not Override the file by default. Each file upload has an option to change the default.') . '</p>';
        return $output;

      default:
    }
  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme() {
    return [
      'tide_media_file_overwrite' => [
        'render element' => 'children',
      ],
    ];
  }

  /**
   * Implements hook_form_FORM_ID_alter().
   */
  #[Hook('form_media_form_alter')]
  public function formMediaFormAlter(&$form, FormStateInterface $form_state, $form_id) {
    // Only show overwrite option on edit,
    // and this media bundle is actually in the configured list.
    if ($form_state->getFormObject()->getOperation() == 'edit' && _tide_media_file_overwrite_get_field($form_id) != FALSE) {
      $upload_overwrite = \Drupal::config('tide_media_file_overwrite.settings')->get('needs_overwritten');

      $form['needs_overwritten'] = [
        '#type' => 'checkbox',
        '#title' => t('Overwrite upload file if the same file name exists?'),
        '#default_value' => $upload_overwrite,
        '#weight' => '0',
      ];

      foreach (array_keys($form['actions']) as $action) {
        if (isset($form['actions'][$action]['#type']) && $form['actions'][$action]['#type'] === 'submit') {
          $form['actions'][$action]['#submit'][] = 'tide_media_file_overwrite_form_submit';
        }
      }
    }
  }

}
