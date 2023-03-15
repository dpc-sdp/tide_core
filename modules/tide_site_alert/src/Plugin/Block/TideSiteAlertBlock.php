<?php

namespace Drupal\tide_site_alert\Plugin\Block;

use Drupal\site_alert\Plugin\Block\SiteAlertBlock;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheBackendInterface;

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

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    // The block should be invalidated whenever any site alert changes.
    $list_cache_tags = $this->entityTypeManager->getDefinition('site_alert')->getListCacheTags();
    return Cache::mergeTags(parent::getCacheTags(), $list_cache_tags);
  }

}
