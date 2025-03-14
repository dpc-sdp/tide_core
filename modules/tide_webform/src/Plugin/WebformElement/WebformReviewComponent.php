<?php

namespace Drupal\tide_webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElement\Item;

/**
 * Provides a 'review_component' element.
 *
 * Note: This is essentially a placeholder field for the frontend.
 *
 * @WebformElement(
 *   id = "webform_review_component",
 *   default_key = "review_component",
 *   label = @Translation("Review component"),
 *   description = @Translation("Displays a review component."),
 *   category = @Translation("Composite elements"),
 * )
 */
class WebformReviewComponent extends Item {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties(): array {
    return [
      'title' => $this->t('Review component'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    // Remove unneeded form settings.
    unset($form['form']);
    unset($form['markup']);
    unset($form['validation']);
    unset($form['element_description']);

    return $form;
  }

}
