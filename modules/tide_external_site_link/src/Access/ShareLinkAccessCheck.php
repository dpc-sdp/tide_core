<?php

namespace Drupal\tide_external_site_link\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\node\NodeInterface;

/**
 * Allows opting out of share link routes.
 */
class ShareLinkAccessCheck implements AccessInterface {

  /**
   * Block access to share links routes for external_site_links.
   *
   * @param \Drupal\node\NodeInterface|null $node
   *   The node.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   A result.
   */
  public function access(?NodeInterface $node) {
    if (!($node instanceof NodeInterface)) {
      return AccessResult::allowed();
    }

    if ($node->bundle() === 'external_site_link') {
      return AccessResult::forbidden();
    }

    return AccessResult::allowed();
  }

}
