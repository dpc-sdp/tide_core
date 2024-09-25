<?php

namespace Drupal\tide_event\Validate;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Form API callback. Validate element value.
 */
class PriceConstraint {

  /**
   * Validates given element.
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form state.
   * @param array $form
   *   The complete form structure.
   */
  public static function validate(array &$element, FormStateInterface $formState, array &$form) {
    $error = FALSE;
    $webformKey = $element['#webform_key'];
    $price_from_value = $formState->getValue('price_from');
    $price_to_value = $formState->getValue($webformKey);

    // Skip empty unique fields or arrays (aka #multiple).
    if ($price_to_value === '' || is_array($price_to_value)) {
      return;
    }

    if ($price_to_value < $price_from_value) {
      $error = TRUE;
    }

    if ($error) {
      if (isset($element['#title'])) {
        $tArgs = [
          '%name' => empty($element['#title']) ? $element['#parents'][0] : $element['#title'],
          '%value' => $price_to_value,
        ];
        $formState->setError(
          $element,
          new TranslatableMarkup('The %name value "%value" should be greater or equal to "Price From" value. Please use a different amount.', $tArgs)
        );
      }
      else {
        $formState->setError($element);
      }
    }
  }

}
