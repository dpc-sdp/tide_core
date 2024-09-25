<?php

namespace Drupal\tide_grant;

use Drupal\Component\Utility\Random;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use Drupal\workflows\Entity\Workflow;

/**
 * Helper class for install/update ops.
 */
class TideGrantOperation {

  /**
   * Enable Editorial workflow if workflow module is enabled.
   */
  public function enableEditorialWorkflow() {
    $moduleHandler = \Drupal::service('module_handler');
    if (!\Drupal::service('config.installer')->isSyncing() && $moduleHandler->moduleExists('workflows')) {
      $editorial_workflow = Workflow::load('editorial');
      if ($editorial_workflow) {
        $editorial_workflow->getTypePlugin()
          ->addEntityTypeAndBundle('node', 'grant');
        $editorial_workflow->save();
      }
    }
  }

  /**
   * Add Grant Author user.
   */
  public function addGrantAuthorUser() {
    if (empty(Role::load('grant_author'))) {
      return;
    }

    // Create new Grant Author user.
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $user = User::create();

    $random = new Random();
    $pass = $random->string();
    $mail = $random->string();

    // Mandatory settings.
    $user->setPassword($pass);
    $user->enforceIsNew();
    $user->setEmail($mail . '@noreply.vic');
    $user->setUsername('Grant Author');
    $user->addRole('grant_author');

    // Optional settings.
    $user->set("init", 'email');
    $user->set("langcode", $language);
    $user->set("preferred_langcode", $language);
    $user->set("preferred_admin_langcode", $language);
    $user->activate();

    // Save user.
    $user->save();
  }

  /**
   * Assign necessary permissions.
   */
  public function assignNecessaryPermissions() {
    $role_permissions = [
      'editor' => [
        'clone grant content',
        'clone grant content',
        'create grant content',
        'edit any grant content',
        'edit own grant content',
        'revert grant revisions',
        'view grant revisions',
      ],
      'site_admin' => [
        'update grant author details',
        'add scheduled transitions node grant',
        'clone grant content',
        'create grant content',
        'delete any grant content',
        'delete grant revisions',
        'delete own grant content',
        'edit any grant content',
        'edit own grant content',
        'revert grant revisions',
        'view grant revisions',
        'view scheduled transitions node grant',
      ],
      'approver' => [
        'add scheduled transitions node grant',
        'create grant content',
        'delete any grant content',
        'delete grant revisions',
        'delete own grant content',
        'edit any grant content',
        'edit own grant content',
        'revert grant revisions',
        'view grant revisions',
        'view scheduled transitions node grant',
      ],
      'grant_author' => [
        'create grant content',
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
  public function addDefaultValueToContentCategory() {
    \Drupal::moduleHandler()->loadInclude('tide_core', 'inc', 'includes/updates');
    _tide_core_field_content_category_default_value('grant', 'Grant');
  }

}
