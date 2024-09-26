<?php

namespace Drupal\tide_news;

use Drupal\user\Entity\Role;
use Drupal\workflows\Entity\Workflow;

/**
 * Helper class for install/update ops.
 */
class TideNewsOperation {

  /**
   * Add news content type to editorial workflows.
   */
  public static function addToWorkflows() {
    $moduleHandler = \Drupal::service('module_handler');
    if (!\Drupal::service('config.installer')->isSyncing() && $moduleHandler->moduleExists('workflows')) {
      $editorial_workflow = Workflow::load('editorial');
      if ($editorial_workflow) {
        $editorial_workflow->getTypePlugin()
          ->addEntityTypeAndBundle('node', 'news');
        $editorial_workflow->save();
      }
    }
  }

  /**
   * Add news content type shceduled transitions.
   */
  public static function addToScheduledTransitions() {
    // Enable entity type/bundles for use with scheduled transitions.
    if (!(\Drupal::moduleHandler()->moduleExists('scheduled_transitions'))) {
      return;
    }
    $config_factory = \Drupal::configFactory();
    $config = $config_factory->getEditable('scheduled_transitions.settings');
    $bundles = $config->get('bundles');
    if ($bundles) {
      foreach ($bundles as $bundle) {
        $enabled_bundles = [];
        $enabled_bundles[] = $bundle['bundle'];
      }
      if (!in_array('news', $enabled_bundles)) {
        $bundles[] = ['entity_type' => 'node', 'bundle' => 'news'];
        $config->set('bundles', $bundles)->save();
      }
    }
    else {
      $bundles[] = ['entity_type' => 'node', 'bundle' => 'news'];
      $config->set('bundles', $bundles)->save();
    }
  }

  /**
   * Assign necessary permissions .
   */
  public static function assignNecessaryPermissions() {
    $role_permissions = [
      'editor' => [
        'create news content',
        'clone news content',
        'edit any news content',
        'edit own news content',
        'revert news revisions',
        'view news revisions',
      ],
      'site_admin' => [
        'add scheduled transitions node news',
        'create news content',
        'clone news content',
        'delete any news content',
        'delete news revisions',
        'delete own news content',
        'edit any news content',
        'edit own news content',
        'revert news revisions',
        'view news revisions',
        'view scheduled transitions node news',
      ],
      'approver' => [
        'add scheduled transitions node news',
        'create news content',
        'delete any news content',
        'delete news revisions',
        'delete own news content',
        'edit any news content',
        'edit own news content',
        'revert news revisions',
        'view news revisions',
        'view scheduled transitions node news',
      ],
      'contributer' => [
        'create news content',
        'clone news content',
        'delete any news content',
        'delete news revisions',
        'delete own news content',
        'edit any news content',
        'edit own news content',
        'revert news revisions',
        'view news revisions',
      ],
    ];

    foreach ($role_permissions as $role => $permissions) {
      if (Role::load($role) && !is_null(Role::load($role))) {
        user_role_grant_permissions(Role::load($role)->id(), $permissions);
      }
    }
  }

  /**
   * Add default value to Content Category.
   */
  public static function addDefaultValueToContentCategory() {
    \Drupal::moduleHandler()->loadInclude('tide_core', 'inc', 'includes/updates');
    _tide_core_field_content_category_default_value('news', 'News article');
  }

}
