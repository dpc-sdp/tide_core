<?php

/**
 * @file
 * API hooks.
 */

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\search_api\IndexInterface;

/**
 * Exclude some fields from entity reference filters.
 *
 * @param \Drupal\search_api\IndexInterface $index
 *   The search index.
 * @param array $reference_fields
 *   The entity reference fields to be used to build filters.
 * @param \Drupal\Core\Field\FieldItemListInterface $items
 *   The field item list.
 * @param int $delta
 *   The delta of the current field item.
 *
 * @return string[]
 *   The list of field ID to exclude.
 */
function hook_tide_content_collection_entity_reference_fields_exclude(IndexInterface $index, array $reference_fields = [], FieldItemListInterface $items = NULL, $delta = NULL) {
  $excludes = [];
  if ($index->id() == 'node') {
    $excludes = ['field_node_site', 'field_primary_site', 'uid'];
  }
  return $excludes;
}

/**
 * Build internal extra filters.
 *
 * @param \Drupal\search_api\IndexInterface $index
 *   The search index.
 * @param \Drupal\Core\Field\FieldItemListInterface $items
 *   The field item list.
 * @param int $delta
 *   The delta of the current field item.
 * @param array $filters
 *   The values of all filters captured in the YAML configuration.
 *
 * @return array
 *   The form elements of the internal filters, keyed by field ID in the index.
 *   The Listing will skip all entity reference fields and fields not indexed.
 *   To disable the filter operator select for an internal extra filter, set the
 *   special key #disable_filter_operator to TRUE.
 */
function hook_tide_content_collection_internal_extra_filters_build(IndexInterface $index, FieldItemListInterface $items = NULL, $delta = NULL, array $filters = []) {
  $elements = [
    'field_project_status' => [
      '#type' => 'checkboxes',
      '#title' => t('Select Project Statuses'),
      '#options' => [
        'In progress' => t('In progress'),
        'Completed' => t('Completed'),
      ],
      '#default_value' => $filters['field_project_status']['values'] ?? [],
      '#disable_filter_operator' => TRUE,
    ],
  ];

  return $elements;
}

/**
 * Alter the internal extra filters.
 *
 * @param array $internal_extra_filters
 *   Form elements of the internal extra filters.
 * @param \Drupal\search_api\IndexInterface $index
 *   The search index.
 * @param array $context
 *   internal Extra context with the following items:
 *   - items: Field item list.
 *   - delta: the delta of the current field item.
 *   - filters: the values of all filters captured in the YAML configuration.
 */
function hook_tide_content_collection_internal_extra_filters_build_alter(array &$internal_extra_filters, IndexInterface $index, array &$context) {
  if ($index->id() == 'node') {
    unset($internal_extra_filters['field_budget']);
  }
}
