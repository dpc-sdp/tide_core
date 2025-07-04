<?php

namespace Drupal\tide_site_restriction;

use Drupal\user\Entity\Role;

/**
 * Tide site restriction modules operations.
 */
class TideSiteRestrictionOperation {

  /**
   * Adds sub_sites_filter filter.
   */
  public static function addSubSitesFilter() {
    $value = [
      'id' => 'sub_sites_filter',
      'table' => 'node_field_data',
      'field' => 'sub_sites_filter',
      'relationship' => 'none',
      'group_type' => 'group',
      'admin_label' => '',
      'operator' => 'or',
      'value' => [],
      'group' => 1,
      'exposed' => TRUE,
      'expose' => [
        'operator_id' => 'sub_sites_filter_op',
        'label' => 'Sub-Sites',
        'description' => '',
        'use_operator' => FALSE,
        'operator' => 'sub_sites_filter_op',
        'operator_limit_selection' => FALSE,
        'operator_list' => [],
        'identifier' => 'sub_sites_filter',
        'required' => FALSE,
        'remember' => FALSE,
        'multiple' => TRUE,
        'remember_roles' => [
          'authenticated' => 'authenticated',
          'anonymous' => '0',
          'administrator' => '0',
          'approver' => '0',
          'site_admin' => '0',
          'editor' => '0',
          'previewer' => '0',
        ],
        'reduce' => 0,
      ],
      'is_grouped' => FALSE,
      'group_info' => [
        'label' => '',
        'description' => '',
        'identifier' => '',
        'optional' => TRUE,
        'widget' => 'select',
        'multiple' => FALSE,
        'remember' => FALSE,
        'default_group' => 'All',
        'default_group_multiple' => [],
        'group_items' => [],
      ],
      'reduce_duplicates' => 0,
      'entity_type' => 'node',
      'plugin_id' => 'sub_sites_filter',
    ];
    $view_config = \Drupal::service('config.factory')
      ->getEditable('views.view.summary_contents_filters');
    $display = $view_config->get('display');
    if (isset($display['default']['display_options']['filters']['field_node_site_target_id'])) {
      $new_filters = [];
      foreach ($display['default']['display_options']['filters'] as $key => $detail) {
        if ($key === 'field_node_site_target_id') {
          $new_filters['sub_sites_filter'] = $value;
          continue;
        }
        $new_filters[$key] = $detail;
      }
      $display['default']['display_options']['filters'] = $new_filters;
      $view_config->set('display', $display);
      $view_config->save();
    }
  }

  /**
   * Set tide_site_restriction on content forms.
   */
  public static function installWidgets() {
    $bundles = \Drupal::entityTypeManager()->getStorage('node_type')->loadMultiple();
    foreach ($bundles as $bundle) {
      $config_name = 'core.entity_form_display.node.' . $bundle->id() . '.default';
      $config = \Drupal::configFactory()->getEditable($config_name);
      $config->set('content.field_node_primary_site.type', 'tide_site_restriction_field_widget');
      $config->set('content.field_node_site.type', 'tide_site_restriction_field_widget');
      $config->save();
    }
  }

  /**
   * Add necessary settings.
   */
  public static function addNecessarySettings() {
    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $entity_form_display */
    $entity_form_display = \Drupal::entityTypeManager()
      ->getStorage('entity_form_display')
      ->load('user.user.default');
    if ($entity_form_display) {
      $entity_form_display->setComponent('field_user_site', [
        'weight' => 31,
        'settings' => [],
        'third_party_settings' => [],
        'type' => 'options_buttons',
        'region' => 'content',
      ])->save();
    }

    $bundleInfo = \Drupal::service('entity_type.bundle.info');
    $entity_form_display_strorage = \Drupal::entityTypeManager()->getStorage('entity_form_display');
    $fields = [
      'field_node_primary_site' => 'node',
      'field_node_site' => 'node',
      'field_media_site' => 'media',
    ];
    foreach ($fields as $field_name => $type) {
      foreach ($bundleInfo->getBundleInfo($type) as $bundle => $item) {
        $entity_form_display = $entity_form_display_strorage->load($type . '.' . $bundle . '.default');
        foreach ($fields as $field) {
          $options = $entity_form_display->getComponent($field);
          $options['type'] = 'tide_site_restriction_field_widget';
          $entity_form_display->setComponent($field, $options)->save();
        }
      }
    }
    // Grants new permissions to Site Admins.
    $role = Role::load('site_admin');
    if ($role) {
      $role->grantPermission('administer site restriction');
      $role->grantPermission('bypass site restriction');
      $role->save();
    }
    $role = Role::load('approver_plus');
    if ($role) {
      $role->grantPermission('administer site restriction');
      $role->save();
    }
  }

}
