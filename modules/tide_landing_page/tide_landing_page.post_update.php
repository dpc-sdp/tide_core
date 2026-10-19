<?php

/**
 * @file
 * Post-update functions for Tide Landing Page.
 */

use Drupal\Core\Entity\Entity\EntityViewDisplay;

/**
 * Restore featured-card displays that contain landing-page form widgets.
 */
function tide_landing_page_post_update_repair_featured_card_display(): void {
  $config = \Drupal::configFactory()->getEditable('core.entity_view_display.node.landing_page.featured_card');
  $values = $config->getRawData();
  $content = $values['content'] ?? [];

  // A form display was exported as a view display on affected sites. Match its
  // summary and image widgets so valid, customized view displays are untouched.
  if (($content['field_landing_page_summary']['type'] ?? NULL) !== 'string_textarea'
    || ($content['field_featured_image']['type'] ?? NULL) !== 'entity_browser_entity_reference') {
    return;
  }

  // Restore the original output: a summary limited to 200 characters.
  // Freeze these settings here rather than loading mutable install-time config.
  $values['content'] = [
    'content_moderation_control' => [
      'weight' => 0,
      'region' => 'content',
      'settings' => [],
      'third_party_settings' => [],
    ],
    'field_landing_page_summary' => [
      'type' => 'smart_trim',
      'label' => 'hidden',
      'weight' => 1,
      'region' => 'content',
      'settings' => [
        'trim_length' => 200,
        'trim_type' => 'chars',
        'trim_suffix' => '',
        'wrap_output' => FALSE,
        'wrap_class' => 'trimmed',
        'more' => [
          'display_link' => FALSE,
          'target_blank' => FALSE,
          'link_trim_only' => FALSE,
          'class' => 'more-link',
          'text' => 'More',
          'aria_label' => 'Read more about [node:title]',
        ],
        'summary_handler' => 'full',
        'trim_options' => [
          'text' => FALSE,
          'trim_zero' => FALSE,
        ],
      ],
      'third_party_settings' => [],
    ],
  ];
  $values['hidden'] = ($values['hidden'] ?? []) + array_fill_keys(array_keys($content), TRUE);
  $values['hidden']['links'] = TRUE;
  foreach (array_keys($values['content']) as $field_name) {
    unset($values['hidden'][$field_name]);
  }
  unset($values['third_party_settings']['field_group']);

  // Remove the invalid formatter IDs before instantiating the display. Saving
  // the existing entity preserves its identity and status, and recalculates
  // dependencies without the copied form widgets and form-only field groups.
  $display = EntityViewDisplay::create($values);
  $display->enforceIsNew(FALSE);
  $display->save();
}
