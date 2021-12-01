<?php

namespace Drupal\tide_core;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Tags;
use Drupal\Core\Entity\EntityAutocompleteMatcher as CoreEntityAutocompleteMatcher;

/**
 * Using to create Tide custom EntityAutocompleteMatcher.
 */
class EntityAutocompleteMatcher extends CoreEntityAutocompleteMatcher {

  /**
   * Gets matched labels based on a given search string.
   */
  public function getMatches($target_type, $selection_handler, $selection_settings, $string = '') {
    // Match the selection handler for either 'default' or 'default:node'.
    if ($target_type == 'node' && $selection_handler == 'default') {
      $selection_handler = 'tide_core';
    }
    $matches = [];
    $options = $selection_settings + [
      'target_type' => $target_type,
      'handler' => $selection_handler,
    ];

    $handler = $this->selectionManager->getInstance($options);

    // Add a flag for our EntityReferenceSelection plugin.
    // @see TideNodeSelection::getReferenceableEntities()
    $handler->singleTargetBundle = FALSE;

    if (isset($string)) {

      if (!empty($options['handler_settings']['target_bundles'])
        && count($options['handler_settings']['target_bundles']) == 1) {
        $handler->singleTargetBundle = TRUE;
      }

      $config = \Drupal::config('tide_entity_autocomplete.settings');
      $limit = $config->get('limit');

      // Get an array of matching entities.
      $match_operator = !empty($selection_settings['match_operator']) ? $selection_settings['match_operator'] : 'CONTAINS';
      $entity_labels = $handler->getReferenceableEntities($string, $match_operator, $limit ? $limit : 100);

      // Loop through the entities and convert them into autocomplete output.
      foreach ($entity_labels as $values) {
        foreach ($values as $entity_id => $label) {
          $key = $label . ' (' . $entity_id . ')';
          // Strip things like starting/trailing white spaces, line breaks and
          // tags.
          $key = preg_replace('/\s\s+/', ' ', str_replace("\n", '', trim(Html::decodeEntities(strip_tags($key)))));
          // Names containing commas or quotes must be wrapped in quotes.
          $key = Tags::encode($key);
          $label = $label . ' (' . $entity_id . ')';
          $matches[] = ['value' => $key, 'label' => $label];
        }
      }
    }

    return $matches;
  }

}
