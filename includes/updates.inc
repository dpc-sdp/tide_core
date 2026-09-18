<?php

/**
 * @file
 * This file supports other modules in their hook_update_N.
 */

use Drupal\field\Entity\FieldConfig;
use Drupal\taxonomy\Entity\Term;

/**
 * Set default values for the field_content_category field.
 *
 * @param string $bundle
 *   The bundle of the entity.
 * @param mixed $term_names
 *   A single term name or an array of term names to set as default values.
 */
function _tide_core_field_content_category_default_value(string $bundle, $term_names) {
  // Ensure $term_names is always an array.
  if (!is_array($term_names)) {
    $term_names = [$term_names];
  }

  $default_values = [];

  foreach ($term_names as $term_name) {
    $query = \Drupal::entityQuery('taxonomy_term')
      ->condition('name', $term_name)
      ->condition('vid', 'content_category')
      ->condition('parent', 0, '<>')
      ->accessCheck(TRUE);

    $results = $query->execute();
    if (!empty($results)) {
      $tid = reset($results);
      $uuid = Term::load($tid)->uuid();
      if (!empty($uuid)) {
        $default_values[] = ['target_uuid' => $uuid];
      }
    }
  }

  if (!empty($default_values)) {
    /** @var \Drupal\field\Entity\FieldConfig $config */
    $config = FieldConfig::loadByName('node', $bundle, 'field_content_category');
    $config->set('default_value', $default_values)->save();
  }
}
