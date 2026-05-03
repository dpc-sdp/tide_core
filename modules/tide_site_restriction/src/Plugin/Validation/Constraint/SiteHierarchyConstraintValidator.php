<?php

namespace Drupal\tide_site_restriction\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the Site Hierarchy constraint.
 *
 * Ensures that users (excluding administrators) can select a maximum of
 * one top-level taxonomy term and one sub-level taxonomy term.
 */
class SiteHierarchyConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   *
   * Checks the selection counts for root and child terms in a taxonomy field.
   *
   * @param mixed $items
   *   The field values to validate.
   * @param \Symfony\Component\Validator\Constraint $constraint
   *   The constraint being applied.
   */
  public function validate($items, Constraint $constraint) {
    $config = \Drupal::config('tide_site_restriction.settings');
    $is_enabled = $config->get('limit_site_hierarchy');

    if ($is_enabled === FALSE) {
      return;
    }

    $current_user = \Drupal::currentUser();
    if (in_array('administrator', $current_user->getRoles())) {
      return;
    }

    if (!isset($items) || count($items) === 0) {
      return;
    }

    $root_count = 0;
    $child_count = 0;
    $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');

    foreach ($items as $item) {
      if ($term_id = $item->target_id) {
        $parents = $term_storage->loadParents($term_id);
        empty($parents) ? $root_count++ : $child_count++;
      }
    }

    if ($root_count > 1 || $child_count > 1) {
      $this->context->addViolation($constraint->message);
    }

  }

}
