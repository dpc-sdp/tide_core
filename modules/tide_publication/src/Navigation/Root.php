<?php

namespace Drupal\tide_publication\Navigation;

use Drupal\Core\Cache\CacheableMetadata;

/**
 * Class Root to find the root entity.
 */
class Root extends Base {

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    if (!$this->validateEntityType()) {
      return;
    }

    $root = $this->findRootEntity(new CacheableMetadata());
    if ($root) {
      $this->list[] = $this->createItem(0, ['target_id' => $root->id()]);
    }
  }

}
