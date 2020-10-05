<?php

namespace Drupal\tide_core\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginInterface;
use Drupal\ckeditor\CKEditorPluginButtonsInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "Callout" plugin, with a CKEditor.
 *
 * @CKEditorPlugin(
 *   id = "TideCallout",
 *   label = @Translation("Callout template Plugin")
 * )
 */
class TideCallout extends PluginBase implements CKEditorPluginInterface, CKEditorPluginButtonsInterface {

  /**
   * {@inheritdoc}
   */
  public function getDependencies(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function isInternal() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    // Provide the JS plugin path.
    return drupal_get_path('module', 'tide_core') . '/js/plugins/tide_callout/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    $iconImage = drupal_get_path('module', 'tide_core') . '/js/plugins/tide_callout/images/icon.png';

    // Return the CKEditor plugin button details.
    return [
      'TideCallout' => [
        'label' => t('Callout template'),
        'image' => $iconImage,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [];
  }

}
