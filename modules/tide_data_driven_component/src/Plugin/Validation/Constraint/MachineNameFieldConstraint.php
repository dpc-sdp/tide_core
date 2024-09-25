<?php

namespace Drupal\tide_data_driven_component\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Validate the machine name field.
 *
 * @Constraint(
 *   id = "TideMachineNameField",
 *   label = @Translation("Tide Machine Name Field", context = "Validation"),
 *   type = "entity:taxonomy_term"
 * )
 */
class MachineNameFieldConstraint extends Constraint {

  /**
   * Format issue message.
   *
   * @var string
   */
  public $message = 'The format is incorrect.';

  /**
   * Duplication issue message.
   *
   * @var string
   */
  public $existingMessage = 'The input value exists, please try another one.';

}
