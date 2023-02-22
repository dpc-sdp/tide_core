<?php

namespace Drupal\tide_core\Plugin\Linkit\Matcher;

use Drupal\Component\Utility\Html;
use Drupal\linkit\MatcherBase;
use Drupal\linkit\Suggestion\DescriptionSuggestion;
use Drupal\linkit\Suggestion\SuggestionCollection;

/**
 * Provides specific linkit matchers for Phone number.
 *
 * @Matcher(
 *   id = "phone",
 *   label = @Translation("Phone"),
 * )
 */
class PhoneMatcher extends MatcherBase {

  /**
   * {@inheritdoc}
   */
  public function execute($string) {
    $suggestions = new SuggestionCollection();
    $regex = ["options" => ["regexp" => "/^(\+?\(61\)|\(\+?61\)|\+?61|\(0[1-9]\)|0[1-9])?( ?-?[0-9]){7,9}$/"]];
    if (filter_var($string, FILTER_VALIDATE_REGEXP, $regex)) {
      $suggestion = new DescriptionSuggestion();
      $suggestion->setLabel($this->t('Phone number: @tel', ['@tel' => $string]))
        ->setPath('tel:' . Html::escape($string))
        ->setGroup($this->t('Phone'));
      $suggestions->addSuggestion($suggestion);
    }
    return $suggestions;
  }

}
