<?php

namespace Drupal\tide_data_driven_component\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the MachineNameFieldConstraint on the taxonomy_term entity.
 */
class MachineNameFieldConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    $item = $items->first();
    if ($item && !preg_match('/^[a-zA-Z0-9_]+$/', $item->value)) {
      $this->context->addViolation($constraint->message, []);
    }
    if ($item) {
      $existing = \Drupal::entityTypeManager()
        ->getStorage('taxonomy_term')
        ->loadByProperties([
          'field_machine_name' => $item->value,
        ]);
      if ($existing) {
        $this->context->addViolation($constraint->existingMessage, []);
      }
    }
  }

}
