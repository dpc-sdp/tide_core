<?php

/**
 * @file
 * Hooks provided by the Tide Core module.
 */

use Drupal\node\NodeInterface;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the breadcrumb trail tide_core computes for a node.
 *
 * Tide Core builds a simple, menu-based breadcrumb trail for a node (the
 * "Home" crumb followed by the node's ancestor menu links) and passes it
 * through this alter before it is consumed — most notably by the schema.org
 * BreadcrumbList that feeds the node's JSON-LD computed field.
 *
 * This is the supported override point: a module can replace the baseline
 * trail with its own calculation (for example a taxonomy-driven or
 * multi-site trail) and thereby change the JSON-LD output, without tide_core
 * needing to know about it.
 *
 * @param array $trail
 *   The breadcrumb trail, an ordered list of crumbs. Each crumb is an array
 *   with:
 *   - title: (string) The visible label of the crumb.
 *   - url: (string) The crumb URL. May be a site-relative path (with a
 *     /site-XXXX/ prefix); downstream consumers normalise it to an absolute
 *     front-end URL. The current node is NOT included — consumers append it.
 * @param \Drupal\node\NodeInterface $node
 *   The node the trail is being built for.
 * @param array $context
 *   Additional context:
 *   - node: (\Drupal\node\NodeInterface) The node (same as $node).
 *   - menu: (string|null) The machine name of the site main menu searched,
 *     or NULL when the node has no resolvable primary site menu.
 *
 * @see \Drupal\tide_core\TideBreadcrumb::build()
 */
function hook_tide_breadcrumb_alter(array &$trail, NodeInterface $node, array $context) {
  // Example: prepend an extra crumb for a specific content type.
  if ($node->bundle() === 'news') {
    array_splice($trail, 1, 0, [
      ['title' => 'News', 'url' => '/news'],
    ]);
  }
}

/**
 * @} End of "addtogroup hooks".
 */
