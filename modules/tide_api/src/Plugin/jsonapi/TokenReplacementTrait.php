<?php

namespace Drupal\tide_api\Plugin\jsonapi;

trait TokenReplacementTrait {

  /**
   * Recursively processes token replacements in nested array structures.
   */
  private function replaceTokensInDefaultValue(array &$element, $token_service) {
    if (!is_array($element)) {
      return;
    }

    // Replace tokens in current element's #default_value if it exists.
    if (!empty($element['#default_value'])) {
      $element['#default_value'] = $token_service->replace($element['#default_value']);
    }

    // Recursively process all child elements.
    foreach ($element as $key => &$child) {
      if (is_array($child)) {
        $this->replaceTokensInDefaultValue($child, $token_service);
      }
    }
  }
}
