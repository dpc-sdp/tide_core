<?php

namespace Drupal\tide_site_alert\Plugin\Block;

use Drupal\Component\Utility\NestedArray;
use Drupal\site_alert\Entity\SiteAlert;
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
    foreach ($build as $key => $item) {
      if (!is_numeric($key)) {
        continue;
      }
      $value = NestedArray::getValue($build, ['#cache', 'tags', $key]);
      if ($value && strpos($value, 'site_alert:') !== FALSE) {
        preg_match('#[0-9]+$#', $value, $match);
        $site_alert = SiteAlert::load(reset($match));
        $build[$key]['#alert']['start'] = date('d/m/Y H:i:s', strtotime($site_alert->getStartTime()));
        $build[$key]['#alert']['end'] = date('d/m/Y H:i:s', strtotime($site_alert->getEndTime()));
      }
    }
    foreach ($build['#attached']['library'] as $id => $value) {
      if ($value == 'site_alert/drupal.site_alert') {
        unset($build['#attached']['library'][$id]);
      }
    }
    $build['#attached']['library'][] = 'tide_site_alert/drupal.tide_site_alert';
    return $build;
  }

}
