<?php

namespace Drupal\tide_cms_support\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Block\BlockPluginInterface;

/**
 * Hook implementations for the tide cms support module.
 */
class TideCmsSupportHooks {

  /**
   * Implements hook_toolbar_alter().
   *
   * Attach css library.
   */
  #[Hook('toolbar_alter')]
  public function toolbarAlter(&$items) {
    $items['administration']['#attached']['library'][] = 'tide_cms_support/toolbar.icons';
  }

  /**
   * Implements hook_block_view_BASE_BLOCK_ID_alter().
   */
  #[Hook('block_view_tide_help_block_alter')]
  public function blockViewTideHelpBlockAlter(array &$build, BlockPluginInterface $block) {
    // Assume that CMS users do not need or want to perform contextual actions
    // on the help block, so don't needlessly draw attention to it.
    unset($build['#contextual_links']);
  }

}
