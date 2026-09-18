<?php

namespace Drupal\tide_profile\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\user\Entity\Role;

/**
 * Hook implementations for the tide profile module.
 */
class TideProfileHooks {

  /**
   * Implements hook_entity_bundle_create().
   */
  #[Hook('entity_bundle_create')]
  public function entityBundleCreate($entity_type_id, $bundle) {
    if ($entity_type_id == 'node' && $bundle == 'profile') {
      // Grant permissions on Test content type to Approver and Editor.
      $roles = ['approver', 'editor'];
      $permissions = [
        'create profile content',
        'delete any profile content',
        'delete own profile content',
        'delete profile revisions',
        'edit any profile content',
        'edit own profile content',
        'revert profile revisions',
        'view profile revisions',
      ];

      foreach ($roles as $role_name) {
        $role = Role::load($role_name);
        if ($role) {
          foreach ($permissions as $permission) {
            $role->grantPermission($permission);
          }
          $role->save();
        }
      }
    }
  }

}
