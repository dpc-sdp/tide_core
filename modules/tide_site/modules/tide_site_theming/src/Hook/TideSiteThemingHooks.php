<?php

namespace Drupal\tide_site_theming\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for the tide site theming module.
 */
class TideSiteThemingHooks {

  /**
   * Implements hook_field_group_form_process_alter().
   */
  #[Hook('field_group_form_process_alter')]
  public function fieldGroupFormProcessAlter(array &$element, &$group, &$complete_form) {
    // Grant access to site theming fields.
    if (!isset($element['#id'])) {
      return;
    }
    if ($element['#id'] == 'tide-site-theming-fileds' || $element['#id'] == 'tide-feature-flag-fields' || $element['#id'] == 'tide-site-favicon-field' || $element['#id'] == 'tide-site-header-corner-graphics') {
      $user = \Drupal::currentUser();
      $access_tide_site_theming_fields = 'tide site theming';
      if (!$user->hasPermission($access_tide_site_theming_fields)) {
        $element['#access'] = FALSE;
      }
    }
  }

}
