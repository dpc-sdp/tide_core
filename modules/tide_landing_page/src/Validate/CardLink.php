<?php

namespace Drupal\tide_landing_page\Validate;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Validation handler for navigation and promotion card link element.
 */
class CardLink {

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
    $link = $element['#value'];
    if ($link == '<front>' || $link == '<nolink>') {
      $error = TRUE;
    }
    if ($error) {
      if (isset($element['#title'])) {
        $tArgs = [
          '%name' => empty($element['#title']) ? $element['#parents'][0] : $element['#title'],
          '%value' => $link,
        ];
        $formState->setError(
          $element,
          new TranslatableMarkup('The %name value "%value" is not a valid value. Please enter a valid internal or external link value', $tArgs)
        );
      }
      else {
        $formState->setError($element);
      }
    }
  }

}
