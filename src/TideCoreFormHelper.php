<?php

namespace Drupal\tide_core;

use Drupal\Core\Form\FormStateInterface;
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

    $form['_header_style'] = [
      '#type' => 'container',
      '#group' => $group,
      '#weight' => -100,
    ];
    $form['_header_style']['_header_style_options'] = [
      '#title' => t('Header style'),
      '#description' => t('The header can be customised to incorporate corner graphics.'),
      '#type' => 'radios',
      '#required' => TRUE,
      '#options' => [
        'default' => t('Default appearance'),
        'corner' => t('Corner graphics'),
      ],
    ];
    $header_style = \Drupal::state()->get($node->uuid() . '-header_style');
    $form['_header_style']['_header_style_options']['#default_value'] = $header_style ?: 'default';
    // Add visibility states to the specified fields.
    foreach ($fields as $field) {
      if (isset($form[$field])) {
        $form[$field]['#states']['visible'] = [
          ':input[name="_header_style_options"]' => ['value' => 'corner'],
        ];
      }
    }
  }

}
