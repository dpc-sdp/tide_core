<?php

namespace Drupal\tide_media\Validate;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Validation handler for timelines component.
 */
class Timelines {

  /**
   * Validates given element.
   *
   * @param array $element
   *   The paragraph element to process.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form state.
   * @param array $form
   *   The complete form structure.
   */
  public static function validate(array &$element, FormStateInterface $formState, array &$form) {
    $error = FALSE;
    if (!isset($element['subform']['field_timeline']['widget']['#max_delta'])) {
      return;
    }
    // Gets the number of timeline component under timelines component.
    $max_delta = $element['subform']['field_timeline']['widget']['#max_delta'];
    $timeline_widget = $element['subform']['field_timeline']['widget'];
    $current_milestones = [];
    // Gets the current milestone fields value under timelines component.
    for ($x = 0; $x <= $max_delta; $x++) {
      if (isset($timeline_widget[$x])) {
        $current_milestones[] = $timeline_widget[$x]['subform']['field_current_milestone']['widget']['value']['#value'];
      }
    }
    // Counts the total number of current milestone checkboxes that are checked.
    $current_milestone_count = count(
      array_filter(
        $current_milestones,
        function ($a) {
          return $a == 1;
        }
      )
    );
    // Set error if more than one checkbox selected.
    if ($current_milestone_count > 1) {
      $error = TRUE;
    }
    if ($error) {
      // This ensures to set the error message for each checkbox.
      for ($x = 0; $x <= $max_delta; $x++) {
        if ($timeline_widget[$x]['subform']['field_current_milestone']['widget']['value']['#value'] == 1) {
          $formState->setError(
            $timeline_widget[$x]['subform']['field_current_milestone']['widget']['value'],
            new TranslatableMarkup('Please select only one current milestone from the timelines component.')
          );
        }
      }
    }
  }

}
