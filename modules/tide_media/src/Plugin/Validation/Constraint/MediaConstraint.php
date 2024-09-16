<?php

namespace Drupal\tide_media\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Validation messages.
 *
 * @Constraint(
 *   id = "media_field_constraint",
 *   label = @Translation("Media field validator", context = "Validation"),
 *   type = "entity:media"
 * )
 */
class MediaConstraint extends Constraint {

  /**
   * Empty field message.
   *
   * @var string
   */
  public $requiredField = '%field_name field is required.';

}
