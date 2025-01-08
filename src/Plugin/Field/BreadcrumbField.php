<?php

namespace Drupal\tide_core\Plugin\Field;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\node\NodeInterface;

/**
 * Breadcrumb field.
 */
class BreadcrumbField extends FieldItemList {

  use ComputedItemListTrait;

  /**
   * Computes the field value.
   */
  protected function computeValue() {
    $request = \Drupal::request();
    $site_value = $request->query->get('site');
    $this->list = [];
    $node = $this->getEntity();
    $custom_field_data = $this->getHierarchyDetails($node);
    if (isset($custom_field_data[$site_value])) {
      $breadcrumbs = $custom_field_data[$site_value];
      foreach ($breadcrumbs as $index => $breadcrumb) {
        $this->list[] = $this->createItem($index, $breadcrumb);
      }
    }
  }

  /**
   * Doc comment is empty.
   */
  private function getHierarchyDetails(NodeInterface $current_node) {
    $hierarchy = $this->getFlexibleHierarchy($current_node);
    $result = [];
    $storage = \Drupal::entityTypeManager()->getStorage('path_alias');
    foreach ($hierarchy as $node) {
      if ($node->hasField('field_breadcrumb_name') && !$node->get('field_breadcrumb_name')->isEmpty()) {
        $title = $node->field_breadcrumb_name->value;
      }
      $alias_objects = $storage->loadByProperties(
        [
          'path' => '/node/' . $node->id(),
        ]
      );
      foreach ($alias_objects as $alias_object) {
        $alias = $alias_object->getAlias();
        $parts = explode('/', $alias);

        if (count($parts) >= 3 && strpos($parts[1], 'site-') === 0) {
          $site_id = substr($parts[1], 5);
          $url = '/' . implode('/', array_slice($parts, 2));

          if (!isset($result[$site_id])) {
            $result[$site_id] = [];
          }

          // Check if this URL already exists for this site.
          $exists = FALSE;
          foreach ($result[$site_id] as $item) {
            if ($item['url'] === $url) {
              $exists = TRUE;
              break;
            }
          }

          if (!$exists) {
            $result[$site_id][] = [
              'url' => $url,
              'name' => empty($title) ? $node->getTitle() : $title,
            ];
          }
        }
      }
    }
    return $result;
  }

  /**
   * Doc comment is empty.
   */
  private function getFlexibleHierarchy(NodeInterface $node) {
    if (!$node) {
      return [];
    }
    return $this->getFlexibleAncestors($node);
  }

  /**
   * Doc comment is empty.
   */
  private function getFlexibleAncestors(NodeInterface $node) {
    if (!$node->hasField('field_breadcrumb_parent') || $node->get('field_breadcrumb_parent')->isEmpty()) {
      return [$node];
    }
    $parent = $node->get('field_breadcrumb_parent')->first()->entity;
    if (!$parent || !($parent instanceof NodeInterface)) {
      return [$node];
    }
    $ancestors = $this->getFlexibleAncestors($parent);
    $ancestors[] = $node;
    return $ancestors;
  }

}
