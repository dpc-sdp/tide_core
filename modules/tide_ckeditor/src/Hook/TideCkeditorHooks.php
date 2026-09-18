<?php

namespace Drupal\tide_ckeditor\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for the tide ckeditor module.
 */
class TideCkeditorHooks {

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme($existing, $type, $theme, $path) {
    return [
      'tide_iframe' => [
        'variables' => [
          'title' => NULL,
          'url' => NULL,
          'width' => NULL,
          'height' => NULL,
        ],
        'template' => 'embedded-content/tide-iframe',
      ],
    ];
  }

}
