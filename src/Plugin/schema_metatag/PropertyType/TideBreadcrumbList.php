<?php

namespace Drupal\tide_core\Plugin\schema_metatag\PropertyType;

use Drupal\node\NodeInterface;
use Drupal\schema_metatag\Plugin\schema_metatag\PropertyType\BreadcrumbList;

/**
 * Tide BreadcrumbList.
 *
 * Tide-aware BreadcrumbList that sources its trail from the tide_core
 * breadcrumb service (which applies hook_tide_breadcrumb_alter()) rather than
 * running Drupal's BreadcrumbManager against the current route. This makes the
 * JSON-LD BreadcrumbList correct in headless/JSON:API contexts and overridable
 * by other modules.
 *
 * @see \Drupal\tide_core\TideBreadcrumb
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
    if (!$node instanceof NodeInterface) {
      return [];
    }

    /** @var \Drupal\tide_core\TideBreadcrumb $builder */
    $builder = \Drupal::service('tide_core.breadcrumb');
    $trail = $builder->build($node);

    // No trail (e.g. the site has breadcrumbs disabled) means no BreadcrumbList
    // at all — don't emit a lone current-page item.
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
   * Resolves the in-scope node.
   *
   * Prefers the node explicitly set on the breadcrumb service by the JSON-LD
   * computed field (reliable under JSON:API, where the route match is the
   * JSON:API route), then falls back to the current route's node parameter.
   *
   * @return \Drupal\node\NodeInterface|null
   *   The node, or NULL when none is in scope.
   */
  protected function resolveCurrentNode(): ?NodeInterface {
    /** @var \Drupal\tide_core\TideBreadcrumb $builder */
    $builder = \Drupal::service('tide_core.breadcrumb');
    $node = $builder->getContextNode();
    if ($node instanceof NodeInterface) {
      return $node;
    }

    foreach (['node', 'entity'] as $name) {
      $param = $this->routeMatch->getParameter($name);
      if ($param instanceof NodeInterface) {
        return $param;
      }
    }
    return NULL;
  }

}
