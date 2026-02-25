<?php

namespace Drupal\tide_breadcrumbs;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\node\NodeInterface;

/**
 * Represents the computed breadcrumb trail field for nodes.
 *
 * This field dynamically generates a breadcrumb trail based on the node's
 * primary site and section site taxonomy terms, bridging multiple menu
 * structures into a single unified trail.
 */
class BreadcrumbComputedField extends FieldItemList {
  use ComputedItemListTrait;

  /**
   * Computes the breadcrumb trail value.
   *
   * Fetches the trail from the tide_breadcrumbs.builder service and populates
   * the field items with 'title' and 'url' properties for each crumb.
   */
  protected function computeValue() {
    $node = $this->getEntity();
    // Ensure we are working with a node entity.
    if (!$node instanceof NodeInterface) {
      return;
    }

    /** @var \Drupal\tide_breadcrumbs\TideBreadcrumbBuilder $breadcrumb_service */
    $breadcrumb_service = \Drupal::service('tide_breadcrumbs.builder');
    $trail = $breadcrumb_service->buildFullTrail($node);

    if (!empty($trail) && is_array($trail)) {
      // Reset the list to ensure no stale data exists during computation.
      $this->list = [];

      foreach ($trail as $delta => $item) {
        // Create an item for each crumb in the trail.
        $this->list[$delta] = $this->createItem($delta, [
          'title' => $item['title'],
          'url' => $item['url'],
        ]);
      }
    }
  }

  /**
   * {@inheritdoc}
   *
   * Overridden to ensure the value is computed before being returned.
   */
  public function getValue() {
    if (!$this->valueComputed) {
      $this->computeValue();
    }
    return parent::getValue();
  }

}
