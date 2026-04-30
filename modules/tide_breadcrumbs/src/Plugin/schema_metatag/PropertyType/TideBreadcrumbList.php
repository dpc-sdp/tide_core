<?php

namespace Drupal\tide_breadcrumbs\Plugin\schema_metatag\PropertyType;

use Drupal\node\NodeInterface;
use Drupal\schema_metatag\Plugin\schema_metatag\PropertyType\BreadcrumbList;

/**
 * Tide-aware BreadcrumbList that reads from the tide_breadcrumb computed
 * field rather than running Drupal's BreadcrumbManager against the current
 * route.
 */
class TideBreadcrumbList extends BreadcrumbList {

  /**
   * {@inheritdoc}
   */
  public function getItems($input_value) {
    if (empty($input_value)) {
      return [];
    }

    $node = $this->resolveCurrentNode();
    if (!$node instanceof NodeInterface || !$node->hasField('tide_breadcrumb')) {
      return [];
    }

    $trail = $node->get('tide_breadcrumb')->getValue();
    if (empty($trail)) {
      return [];
    }

    $items = [];
    $position = 1;
    foreach ($trail as $crumb) {
      $title = $crumb['title'] ?? '';
      $url = $crumb['url'] ?? '';
      if ($url === '' || $title === '') {
        continue;
      }
      $items[$position] = [
        '@id' => $url,
        'name' => $title,
        'item' => $url,
      ];
      $position++;
    }

    // Append the current page so the trail ends where the user actually is.
    $current = $node->toUrl()->toString(TRUE)->getGeneratedUrl();
    if ($current !== '') {
      $items[$position] = [
        '@id' => $current,
        'name' => $node->label(),
        'item' => $current,
      ];
    }

    return $items;
  }

  /**
   * Resolves the in-scope node from the current route.
   */
  protected function resolveCurrentNode(): ?NodeInterface {
    foreach (['node', 'entity'] as $name) {
      $param = $this->routeMatch->getParameter($name);
      if ($param instanceof NodeInterface) {
        return $param;
      }
    }
    return NULL;
  }

}
