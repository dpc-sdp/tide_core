<?php

namespace Drupal\tide_site_preview\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for the tide site preview module.
 */
class TideSitePreviewHooks {

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme($existing, $type, $theme, $path) {
    return [
      'tide_site_preview_links' => [
        'variables' => [
          'node' => NULL,
          'preview_links' => [],
        ],
      ],
    ];
  }

}
