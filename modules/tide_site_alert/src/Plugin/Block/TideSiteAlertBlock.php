<?php

namespace Drupal\tide_site_alert\Plugin\Block;

use Drupal\site_alert\Plugin\Block\SiteAlertBlock;

/**
 * Implements TideSiteAlertBlock class.
 *
 * @Block(
 *   id = "tide_site_alert_block",
 *   admin_label = @Translation("Tide Site Alert"),
 * )
 */
class TideSiteAlertBlock extends SiteAlertBlock {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = parent::build();
    foreach ($build['#attached']['library'] as $id => $value) {
      if ($value == 'site_alert/drupal.site_alert') {
        unset($build['#attached']['library'][$id]);
      }
    }
    $build['#attached']['library'][] = 'tide_site_alert/drupal.tide_site_alert';
    return $build;
  }

}
