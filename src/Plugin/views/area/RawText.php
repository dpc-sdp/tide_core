<?php

namespace Drupal\tide_core\Plugin\views\area;

use Drupal\views\Plugin\views\area\TextCustom;
use Drupal\views\Render\ViewsRenderPipelineMarkup;

/**
 * Provides a Raw Text views area plugins.
 *
 * @ingroup views_area_handlers
 *
 * @ViewsArea("text_raw")
 *
 * @package Drupal\tide_core\Plugin\views\area
 */
class RawText extends TextCustom {

  /**
   * {@inheritdoc}
   */
  public function renderTextarea($value) {
    if ($value) {
      return ViewsRenderPipelineMarkup::create($this->tokenizeValue($value));
    }
    return NULL;
  }

}
