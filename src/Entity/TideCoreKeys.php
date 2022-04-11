<?php

namespace Drupal\tide_core\Entity;

use Drupal\key\Entity\Key;

/**
 * Override key entity to provide a custom function.
 */
final class TideCoreKeys extends Key {

  /**
   * Get value by key.
   */
  public function getValueByKey(string $key) {
    $key = strtolower($key);
    $values = $this->getKeyValues();
    if (empty($values)) {
      throw new \RuntimeException(sprintf('"%s" Key entity doesn\'t have a proper values.', $this->get('label')));
    }
    $key_values = array_keys($values);
    if (in_array($key, $key_values)) {
      return $values[$key];
    }
    return NULL;
  }

}
