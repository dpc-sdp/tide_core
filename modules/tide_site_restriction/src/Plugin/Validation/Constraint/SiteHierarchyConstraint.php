<?php

namespace Drupal\tide_site_restriction\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Ensures a maximum of one primary site and one site section term is selected.
 *
 * @Constraint(
 * id = "SiteHierarchy",
 * label = @Translation("Site Hierarchy Constraint", context = "Validation"),
 * )
 */
class SiteHierarchyConstraint extends Constraint {
  /**
   * Validation error message.
   *
   * @var string
   */
  public $message = 'You can select a maximum of one primary site and one site section.';

}
