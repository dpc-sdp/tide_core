<?php

namespace Drupal\tide_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Form\FormStateInterface;
use Drupal\tide_site\TideSiteFields;
use Drupal\user\Entity\Role;
use Drupal\workflows\Entity\Workflow;

/**
 * Hook implementations for the tide test module.
 */
class TideTestHooks {

  /**
   * Implements hook_entity_bundle_create().
   */
  #[Hook('entity_bundle_create')]
  public function entityBundleCreate($entity_type_id, $bundle) {
    if ($entity_type_id == 'node' && $bundle == 'test') {
      // Grant permissions on Test content type to Approver and Editor.
      $roles = ['approver', 'editor'];
      $permissions = [
        'create test content',
        'delete any test content',
        'delete own test content',
        'delete test revisions',
        'edit any test content',
        'edit own test content',
        'revert test revisions',
        'view test revisions',
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

      // Enable Editorial workflow.
      $editorial_workflow = Workflow::load('editorial');
      if ($editorial_workflow) {
        $editorial_workflow->getTypePlugin()->addEntityTypeAndBundle($entity_type_id, $bundle);
        $editorial_workflow->save();
      }
    }
  }

  /**
   * Implements hook_modules_installed().
   */
  #[Hook('modules_installed')]
  public function modulesInstalled($modules) {
    module_set_weight('tide_test', 1000);

    // Invoke hook_entity_bundle_create again so that other modules can change
    // entity form display of the `test` content type which was reverted by
    // default config import during module installation.
    if (!\Drupal::service('config.installer')->isSyncing() && in_array('tide_test', $modules)) {
      /** @var \Drupal\Core\Extension\ModuleHandler $moduleHandler */
      $moduleHandler = \Drupal::service('module_handler');
      $moduleHandler->invokeAll('entity_bundle_create', ['node', 'test']);
    }
  }

  /**
   * Implements hook_field_widget_form_alter().
   *
   * @todo Extract into a service.
   */
  #[Hook('field_widget_single_element_form_alter')]
  public function fieldWidgetSingleElementFormAlter(&$element, FormStateInterface $form_state, $context) {
    // Check if the tide_site module is enabled.
    $moduleHandler = \Drupal::service('module_handler');
    if ($moduleHandler->moduleExists('tide_site')) {
      // Add default values for the Site fields of Test content type, so that
      // their mandatory nature won't affect other Behat tests without
      // knowledge of Tide Site.
      /** @var \Drupal\Core\Field\BaseFieldDefinition $field_definition */
      $field_definition = $context['items']->getFieldDefinition();
      if ($field_definition && $field_definition->getTargetEntityTypeId() == 'node' && $field_definition->getTargetBundle() == 'test') {
        if (TideSiteFields::isSiteField($field_definition->getName(), TideSiteFields::FIELD_SITE)) {
          $options = array_keys($element['#options']);
          if (empty($element['#default_value'])) {
            $element['#default_value'] = [reset($options)];
          }
        }

        if (TideSiteFields::isSiteField($field_definition->getName(), TideSiteFields::FIELD_PRIMARY_SITE)) {
          if (empty($element['#default_value'])) {
            $options = array_keys($element['#options']);
            $element['#default_value'] = reset($options);
          }
        }
      }
    }
  }

  /**
   * Implements hook_config_ignore_settings_alter().
   */
  #[Hook('config_ignore_settings_alter')]
  public function configIgnoreSettingsAlter(array &$settings) {
    // Ignore all test config.
    $settings[] = '*.test';
    $settings[] = '*.test*';
    $settings[] = '*.test.*';
    $settings[] = '*.field_test*';
    $settings[] = '*_test*';
    $settings[] = '*.node--test';
  }

}
