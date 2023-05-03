<?php

namespace Drupal\tide_core\Plugin\views\field;

use Drupal\user\Plugin\views\field\Roles;

/**
 * A handler to provide a list of roles sorted alphabetically.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("sorted_roles_views_field")
 */
class SortedRolesViewsField extends Roles {

/**
 * Prerender user roles.
 *
 * @param array $values
 * @return void
 */
  public function preRender(&$values) {
    $uids = [];
    $this->items = [];

    foreach ($values as $result) {
      $uids[] = $this->getValue($result);
    }

    if ($uids) {
      $roles = user_roles();
      asort($roles);
      $result = $this->database->query(
        'SELECT [u].[entity_id] AS [uid], [u].[roles_target_id] AS [rid]
         FROM {user__roles} [u]
         WHERE [u].[entity_id]
         IN ( :uids[] ) AND [u].[roles_target_id]
         IN ( :rids[] )', [':uids[]' => $uids, ':rids[]' => array_keys($roles)]
      );
      foreach ($result as $role) {
        $this->items[$role->uid][$role->rid]['role'] = $roles[$role->rid]->label();
        $this->items[$role->uid][$role->rid]['rid'] = $role->rid;
      }
      // Sort the roles for each user by role weight.
      $ordered_roles = array_flip(array_keys($roles));
      foreach ($this->items as &$user_roles) {
        // Create an array of rids that the user has in the role weight order.
        $sorted_keys = array_intersect_key($ordered_roles, $user_roles);
        // Merge with the unsorted array of role information which has the
        // effect of sorting it.
        $user_roles = array_merge($sorted_keys, $user_roles);
      }
    }
  }

}
