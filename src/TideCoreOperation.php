<?php

namespace Drupal\tide_core;

use Drupal\block\Entity\Block;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\Role;
use Drupal\views\Entity\View;
use Drupal\workflows\Entity\Workflow;

/**
 * Helper class for install/update ops.
 */
class TideCoreOperation {

  /**
   * Creates terms for Topic vocabulary.
   */
  public function createTopicTermsVocabulary() {
    $vid = 'topic';

    $terms = [
      'Arts',
      'Business',
      'Education',
    ];
    foreach ($terms as $term) {
      Term::create([
        'name' => $term,
        'vid' => $vid,
        'parent' => [],
      ])->save();
    }
  }

  /**
   * Update default Editorial workflow of Content Moderation.
   */
  public function updateEditorialWorkflow() {
    $editorial_workflow = Workflow::load('editorial');
    if ($editorial_workflow) {
      $type_settings = [
        'states' => [
          'draft' => [
            'label' => 'Draft',
            'published' => FALSE,
            'default_revision' => FALSE,
            'weight' => -10,
          ],
          'needs_review' => [
            'published' => FALSE,
            'default_revision' => FALSE,
            'label' => 'Needs Review',
            'weight' => -9,
          ],
          'published' => [
            'label' => 'Published',
            'published' => TRUE,
            'default_revision' => TRUE,
            'weight' => -8,
          ],
          'archived' => [
            'label' => 'Archived',
            'weight' => -7,
            'published' => FALSE,
            'default_revision' => TRUE,
          ],
          'archive_pending' => [
            'label' => 'Archive pending',
            'published' => FALSE,
            'default_revision' => FALSE,
            'weight' => -6,
          ],
        ],
        'transitions' => [
          'create_archive_pending' => [
            'label' => 'Archive pending',
            'from' => ['draft', 'published', 'needs_review'],
            'to' => 'archive_pending',
            'weight' => -11,
          ],
          'create_new_draft' => [
            'label' => 'Create New Draft',
            'from' => ['draft', 'published', 'archive_pending'],
            'to' => 'draft',
            'weight' => -10,
          ],
          'needs_review' => [
            'label' => 'Needs Review',
            'from' => ['draft', 'archive_pending'],
            'to' => 'needs_review',
            'weight' => -9,
          ],
          'needs_review_draft' => [
            'label' => 'Send back to Draft',
            'from' => ['needs_review'],
            'to' => 'draft',
            'weight' => -8,
          ],
          'publish' => [
            'label' => 'Publish',
            'from' => ['draft', 'needs_review', 'published'],
            'to' => 'published',
            'weight' => -7,
          ],
          'archive' => [
            'label' => 'Archive',
            'from' => ['published'],
            'to' => 'archived',
            'weight' => -6,
          ],
          'archived_draft' => [
            'label' => 'Restore to Draft',
            'from' => ['archived'],
            'to' => 'draft',
            'weight' => -5,
          ],
          'archived_published' => [
            'label' => 'Restore',
            'from' => ['archived', 'archive_pending'],
            'to' => 'published',
            'weight' => -4,
          ],
        ],
        'entity_types' => [],
      ];
      $editorial_workflow->set('type_settings', $type_settings);
      $editorial_workflow->save();
    }
  }

  /**
   * Update authenticated user permission.
   */
  public function updateAuthenticatedUserPermission() {
    $permissions = [
      'access content',
      'edit own field_business_contact_number',
      'edit own field_business_name',
      'view media',
      'view own field_business_contact_number',
      'view own field_business_name',
    ];
    if (Role::load('authenticated') && !is_null(Role::load('authenticated'))) {
      user_role_grant_permissions(Role::load('authenticated')->id(), $permissions);
    }
  }

  /**
   * Deletes unsupported actions from /admin/contents view.
   */
  public function deleteUnsupportedActions() {
    // Actions whose provided by drupal core.
    $unsupported_actions = [
      'system.action.node_make_sticky_action',
      'system.action.node_make_unsticky_action',
      'system.action.node_promote_action',
      'system.action.node_save_action',
      'system.action.node_unpromote_action',
      'system.action.node_publish_action',
      'system.action.node_unpublish_action',
      'system.action.pathauto_update_alias_node',
    ];
    $config_storage = \Drupal::service('config.storage');
    $config_factory = \Drupal::configFactory();
    foreach ($unsupported_actions as $action) {
      if ($config_storage->read($action)) {
        $config = $config_factory->getEditable($action);
        $config->delete();
      }
    }
  }

  /**
   * Add contact field to user account form display.
   */
  public function addBusinessFieldsToUserAccountForm() {
    $config_factory = \Drupal::configFactory();
    $config = $config_factory->getEditable('core.entity_form_display.user.user.default');
    $config->set('content.field_notes.weight', 12);
    $config->set('content.field_notes.type', 'text_textarea');
    $config->set('content.field_notes.region', 'content');
    $config->set('content.field_notes.settings.rows', 5);
    $config->set('content.field_notes.settings.placeholder', '');
    $config->set('content.field_notes.third_party_settings', []);

    $config->set('content.field_business_name.weight', 10);
    $config->set('content.field_business_name.type', 'string_textfield');
    $config->set('content.field_business_name.region', 'content');
    $config->set('content.field_business_name.settings.size', 60);
    $config->set('content.field_business_name.settings.placeholder', '');
    $config->set('content.field_business_name.third_party_settings', []);

    $config->set('content.field_business_contact_number.weight', 11);
    $config->set('content.field_business_contact_number.type', 'telephone_default');
    $config->set('content.field_business_contact_number.region', 'content');
    $config->set('content.field_business_contact_number.settings.placeholder', '');
    $config->set('content.field_business_contact_number.third_party_settings', []);

    $config->save();
  }

  /**
   * Updates files view.
   */
  public function useCustomFilesView() {
    module_load_include('inc', 'tide_core', 'includes/helpers');
    $config_location = [\Drupal::service('extension.list.module')->getPath('tide_core') . '/config/optional'];
    $config_read = _tide_read_config('views.view.enhanced_files', $config_location, TRUE);
    $storage = \Drupal::entityTypeManager()->getStorage('view');
    if ($storage->load('enhanced_files') == NULL) {
      $config_entity = $storage->createFromStorageRecord($config_read);
      $config_entity->save();
    }
    $view = View::load('files');
    if ($view) {
      if ($view->status() == TRUE) {
        $view->setStatus(FALSE);
        $view->save();
      }
    }
  }

  /**
   * Changes the diff modules general_settings.revision_pager_limit to 16.
   */
  public function changeDiffSettings() {
    if (\Drupal::moduleHandler()->moduleExists('diff')) {
      $config = \Drupal::configFactory()
        ->getEditable('diff.settings');
      if (!$config->isNew() && !empty($config->get('general_settings.revision_pager_limit'))) {
        $config->set('general_settings.revision_pager_limit', 16)->save();
      }
    }
  }

  /**
   * Enable TFA.
   */
  public static function enableTfaNecessaryModules() {
    $moduleHandler = \Drupal::service('module_handler');
    $moduleInstaller = \Drupal::service('module_installer');
    // Enable Real AES.
    if (!$moduleHandler->moduleExists('real_aes')) {
      $moduleInstaller->install(['real_aes']);
    }
    // Enable Two-factor Authentication (TFA).
    if (!$moduleHandler->moduleExists('tfa')) {
      $moduleInstaller->install(['tfa']);
    }
  }

  /**
   * Disabled site alert for TFA routes.
   */
  public static function disabledSiteAlertTfa() {
    $block = Block::load('tide_site_alert_header');
    if (\Drupal::service('module_handler')->moduleExists('tide_site_alert') && $block) {
      $block->setVisibilityConfig('request_path', [
        'id' => 'request_path',
        'pages' => "/user/*/security/tfa\n/user/*/security/tfa/*",
        'negate' => TRUE,
      ]);
      $block->save();
    }
  }

  /**
   * Update TFA settings.
   */
  public static function updateTfaSettings(array $config_install, array $config_optional) {
    \Drupal::moduleHandler()->loadInclude('tide_core', 'inc', 'includes/helpers');
    $configs_files_install = [
      'key.key.tfa_encryption_key',
      'encrypt.profile.tfa_encryption',
    ];

    $config_files_optional = [
      'encrypt.settings',
      'tfa.settings',
    ];

    foreach ($configs_files_install as $install) {
      _tide_ensure_config($install, $config_install);
    }

    foreach ($config_files_optional as $optional) {
      _tide_ensure_config($optional, $config_optional);
    }
  }

  /**
   * Setup TFA role permissions.
   */
  public static function setupTfaRolePermissions() {
    $permissions = ['setup own tfa'];
    $permissions = ['admin tfa settings'];

    $roles_permissions = [
      'site_admin' => ['admin tfa settings'],
      'authenticated' => ['setup own tfa'],
    ];

    foreach ($roles_permissions as $rid => $permissions) {
      user_role_grant_permissions($rid, $permissions);
    }
  }

}
