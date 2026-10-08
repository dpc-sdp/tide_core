<?php

namespace Drupal\tide_data_driven_component\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Hook implementations for the tide data driven component module.
 */
class TideDataDrivenComponentHooks {

  /**
   * Implements hook_entity_bundle_field_info_alter().
   */
  #[Hook('entity_bundle_field_info_alter')]
  public function entityBundleFieldInfoAlter(&$fields, EntityTypeInterface $entity_type, $bundle) {
    if ($entity_type->id() == 'taxonomy_term' && $bundle == 'data_driven_component') {
      if (!empty($fields['field_machine_name'])) {
        $fields['field_machine_name']->addConstraint('TideMachineNameField');
      }
    }
  }

}
