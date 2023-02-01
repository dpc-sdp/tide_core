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
        $dateTimeImmutable = new \DateTimeImmutable();
        $build[$key]['#alert']['start'] = $dateTimeImmutable->modify($site_alert->getStartTime())
          ->format('d/m/Y H:i:s');
        $build[$key]['#alert']['end'] = $dateTimeImmutable->modify($site_alert->getEndTime())
          ->format('d/m/Y H:i:s');
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
