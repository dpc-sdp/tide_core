<?php

namespace Drupal\tide_media\Plugin\Validation\Constraint;

use Drupal\media\MediaInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validation for field_is_streamed and field_media_transcript.
 */
class MediaConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    $media_entity = $items->getEntity();
    if ($media_entity instanceof MediaInterface && $media_entity->bundle() == 'embedded_video') {
      if ($media_entity->hasField('field_is_streamed') && $media_entity->field_is_streamed->value == FALSE) {
        if ($media_entity->field_media_transcript->isEmpty()) {
          $this->context->addViolation($constraint->requiredField, ['%field_name' => $items->getDataDefinition()->getLabel()]);
        }
      }
    }
  }

}
