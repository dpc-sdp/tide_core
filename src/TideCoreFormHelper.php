<?php

namespace Drupal\tide_core;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\node\NodeInterface;

/**
 * Helper functions for altering node forms.
 */
class TideCoreFormHelper {

  /**
   * Adds a header style radio selector with conditional visibility logic.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param \Drupal\node\NodeInterface $node
   *   The node entity.
   * @param array $settings
   *   Associative array with:
   *   - 'group': Group ID to attach the container to.
   *   - 'fields': Array of field machine names to show/hide.
   *   - 'after_build': (optional) After-build callback function name.
   */
  public static function addHeaderStyleSelector(array &$form, FormStateInterface $form_state, NodeInterface $node, array $settings) {
    $group = $settings['group'] ?? 'group_customised_header';
    $fields = $settings['fields'] ?? [];
    $after_build = $settings['after_build'] ?? NULL;

    $form['_header_style'] = [
      '#type' => 'container',
      '#group' => $group,
      '#weight' => -100,
    ];

    $form['_header_style']['_header_style_options'] = [
      '#title' => new TranslatableMarkup('Header style'),
      '#description' => new TranslatableMarkup('The header can be customised to incorporate corner graphics.'),
      '#type' => 'radios',
      '#required' => TRUE,
      '#options' => [
        'default' => new TranslatableMarkup('Default appearance'),
        'corner' => new TranslatableMarkup('Corner graphics'),
      ],
    ];

    // Set default value based on image field presence.
    $header_style = 'default';
    foreach ($fields as $field) {
      if ($node->hasField($field) && !$node->get($field)->isEmpty()) {
        $header_style = 'corner';
        break;
      }
    }

    $form['_header_style']['_header_style_options']['#default_value'] = $header_style;

    // Add visibility states to the specified fields.
    foreach ($fields as $field) {
      if (isset($form[$field])) {
        $form[$field]['#states']['visible'] = [
          ':input[name="_header_style_options"]' => ['value' => 'corner'],
        ];
      }
    }

    if ($after_build) {
      $form['#after_build'][] = $after_build;
    }
  }

  /**
   * Updates the default header style based on form state field values.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param string[] $fields
   *   The list of image fields to check.
   *
   * @return array
   *   The updated form array.
   */
  public static function updateHeaderStyleFromState(array $form, FormStateInterface $form_state, array $fields) {
    $header_style = 'default';

    foreach ($fields as $field) {
      $target_id = $form_state->getValue([$field, 'target_id']);
      if (!empty($target_id)) {
        $header_style = 'corner';
        break;
      }
    }

    $form['_header_style']['_header_style_options']['#default_value'] = $header_style;

    return $form;
  }

  /**
   * Update the widget for field_node_site on a specific content type.
   *
   * @param string $content_type
   *   The machine name of the content type.
   */
  public static function updateFieldNodeSiteWidget(string $content_type): void {
    $storage = \Drupal::entityTypeManager()->getStorage('entity_form_display');
    $form_display_id = "node.$content_type.default";

    // Load existing form display
    $form_display = $storage->load($form_display_id);

    // Only proceed if the form display exists
    if (!$form_display) {
      return;
    }

    // Update only the field_node_site widget config
    $form_display->setComponent('field_node_site', [
      'type' => 'term_reference_tree',
      'weight' => 15,
      'region' => 'content',
      'settings' => [
        'start_minimized' => TRUE,
        'leaves_only' => TRUE,
        'select_parents' => FALSE,
        'cascading_selection' => 0,
        'max_depth' => 3,
      ],
      'third_party_settings' => [],
    ]);

    // Save the updated form display without affecting other fields
    $form_display->save();
  }

}
