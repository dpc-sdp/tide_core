<?php

/**
 * @file
 * This file supports other modules in their hook_update_N.
 */

use Drupal\Core\Entity\Entity\EntityFormDisplay;
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

/**
 * Set form display for field_content_category field.
 */
function _tide_core_content_category_form_display(string $bundle) {
  $entity_form_display = EntityFormDisplay::load('node.' . $bundle . '.default');
  $detail = $entity_form_display->getComponent('field_tags');
  $weight = $detail['weight'];
  $content = [
    "type" => "term_reference_tree",
    "weight" => $weight + 1,
    "region" => "content",
    "settings" => [
      "start_minimized" => TRUE,
      "leaves_only" => TRUE,
      "select_parents" => FALSE,
      "cascading_selection" => 0,
      "max_depth" => 0,
    ],
    "third_party_settings" => [],
  ];
  $field_content_category_component = $entity_form_display->getComponent('field_content_category');
  if ($field_content_category_component === NULL) {
    $entity_form_display->setComponent('field_content_category', $content)->save();
  }
}
