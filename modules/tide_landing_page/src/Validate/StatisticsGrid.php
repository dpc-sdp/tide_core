<?php

namespace Drupal\tide_landing_page\Validate;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Validation handler for statistics grid component.
 */
class StatisticsGrid {

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
    $blocks = $element['subform']['field_statistic_block']['widget'];
    $numericKeysCount = self::countNumericKeys($blocks);

    if ($numericKeysCount < 2) {
      $formState->setError(
        $element,
        new TranslatableMarkup('The statistics grid component need minimum two blocks to be added.')
      );
    }
  }

  /**
   * Count the number of numeric keys in the array.
   *
   * @param array $array
   *   The array to be inspected.
   *
   * @return int
   *   The count of numeric keys.
   */
  public static function countNumericKeys(array $array): int {
    $numericKeys = array_filter(array_keys($array), 'is_numeric');
    return count($numericKeys);
  }

}
