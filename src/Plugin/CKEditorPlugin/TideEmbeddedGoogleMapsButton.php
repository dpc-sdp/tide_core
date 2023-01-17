<?php

namespace Drupal\tide_core\Plugin\CKEditorPlugin;

/**
 * @file
 * Embedded Google Maps CKEditor button.
 */

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "wenzgmap" plugin.
 *
 * NOTE: The plugin ID ('id' key) corresponds to the CKEditor plugin name.
 * It is the first argument of the CKEDITOR.plugins.add() function in the
 * plugin.js file.
 *
 * @CKEditorPlugin(
 *   id = "wenzgmap",
 *   label = @Translation("Embedded Google Maps")
 * )
 */
class TideEmbeddedGoogleMapsButton extends CKEditorPluginBase {

  /**
   * {@inheritdoc}
   *
   * NOTE: The keys of the returned array corresponds to the CKEditor button
   * names. They are the first argument of the editor.ui.addButton() or
   * editor.ui.addRichCombo() functions in the plugin.js file.
   */
  public function getButtons() {
    // Make sure that the path to the image matches the file structure of
    // the CKEditor plugin you are implementing.
    return [
      'wenzgmap' => [
        'label' => t('Embedded Google Maps'),
        'image' => \Drupal::service('extension.list.module')->getPath('tide_core') . '/js/plugins/wenzgmap/icons/wenzgmap.png',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    // Make sure that the path to the plugin.js matches the file structure of
    // the CKEditor plugin you are implementing.
    return \Drupal::service('extension.list.module')->getPath('tide_core') . '/js/plugins/wenzgmap/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [];
  }

}
