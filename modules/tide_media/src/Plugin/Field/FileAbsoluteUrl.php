<?php

namespace Drupal\tide_media\Plugin\Field;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\ComputedItemListTrait;

/**
 * Extends core URL field functionality.
 */
class FileAbsoluteUrl extends FieldItemList {

  use ComputedItemListTrait;

  /**
   * {@inheritdoc}
   */
  public function access($operation = 'view', AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $this->getEntity()
      ->get('uri')
      ->access($operation, $account, $return_as_object);
  }

  /**
   * Initialize the internal field list with the modified items.
   */
  protected function computeValue() {
    $url_list = [];
    foreach ($this->getEntity()->get('uri') as $delta => $uri_item) {
      $url = \Drupal::service('file_url_generator')->generateAbsoluteString($uri_item->value);
      $config = \Drupal::config('tide_media.settings');
      if ($config->get('force_https')) {
        $url = str_replace('http://', 'https://', $url);
      }
      $url_list[$delta] = $this->createItem($delta, $url);
    }
    $this->list = $url_list;
  }

}
