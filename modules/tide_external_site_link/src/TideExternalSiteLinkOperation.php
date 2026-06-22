<?php

namespace Drupal\tide_external_site_link;

use Drupal\search_api\Item\Field;
use Drupal\user\Entity\Role;
use Drupal\workflows\Entity\Workflow;

/**
 * Helper class for install/update ops.
 */
class TideExternalSiteLinkOperation {

  /**
   * Add to editorial workflows.
   */
  public static function addToWorkflows() {
    if (!(\Drupal::moduleHandler()->moduleExists('workflows'))) {
      return;
    }

    $editorial_workflow = Workflow::load('editorial');

    if ($editorial_workflow) {
      $editorial_workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'external_site_link');
      $editorial_workflow->save();
    }
  }

  /**
   * Add external site link content type scheduled transitions.
   */
  public static function addToScheduledTransitions() {
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
      if (!in_array('external_site_link', $enabled_bundles)) {
        $bundles[] = ['entity_type' => 'node', 'bundle' => 'external_site_link'];
        $config->set('bundles', $bundles)->save();
      }
    }
    else {
      $bundles[] = ['entity_type' => 'node', 'bundle' => 'external_site_link'];
      $config->set('bundles', $bundles)->save();
    }
  }

  /**
   * Assign permissions.
   */
  public static function assignNecessaryPermissions() {
    $role_permissions = [
      'editor' => [
        'clone external_site_link content',
        'create external_site_link content',
        'edit any external_site_link content',
        'edit own external_site_link content',
        'revert external_site_link revisions',
        'view external_site_link revisions',
      ],
      'site_admin' => [
        'add scheduled transitions node external_site_link',
        'clone external_site_link content',
        'create external_site_link content',
        'delete any external_site_link content',
        'delete external_site_link revisions',
        'delete own external_site_link content',
        'edit any external_site_link content',
        'edit own external_site_link content',
        'revert external_site_link revisions',
        'view external_site_link revisions',
        'view scheduled transitions node external_site_link',
      ],
      'approver' => [
        'add scheduled transitions node external_site_link',
        'create external_site_link content',
        'delete any external_site_link content',
        'delete external_site_link revisions',
        'delete own external_site_link content',
        'edit any external_site_link content',
        'edit own external_site_link content',
        'revert external_site_link revisions',
        'view external_site_link revisions',
        'view scheduled transitions node external_site_link',
      ],
      'contributor' => [
        'clone external_site_link content',
        'create external_site_link content',
        'delete any external_site_link content',
        'delete external_site_link revisions',
        'delete own external_site_link content',
        'edit any external_site_link content',
        'edit own external_site_link content',
        'revert external_site_link revisions',
        'view external_site_link revisions',
      ],
    ];

    foreach ($role_permissions as $role => $permissions) {
      if (Role::load($role) && !is_null(Role::load($role))) {
        user_role_grant_permissions(Role::load($role)->id(), $permissions);
      }
    }
  }

  /**
   * Add fields to search API.
   */
  public static function addFieldsToSearchApi() {
    $moduleHandler = \Drupal::service('module_handler');

    if (!$moduleHandler->moduleExists('tide_search')) {
      return;
    }

    $index_storage = \Drupal::entityTypeManager()->getStorage('search_api_index');
    $index = $index_storage->load('node');

    // Index the keywords field.
    $keywords_field = new Field($index, 'field_content_keywords');
    $keywords_field->setType('text');
    $keywords_field->setPropertyPath('field_content_keywords');
    $keywords_field->setDatasourceId('entity:node');
    $keywords_field->setLabel('Content keywords');
    $index->addField($keywords_field);

    $index->save();
  }

  /**
   * Add site restriction support to site and primary site fields.
   */
  public static function addSiteRestrictionSupport() {
    if (!(\Drupal::moduleHandler()->moduleExists('tide_site_restriction'))) {
      return;
    }

    $config = \Drupal::configFactory()->getEditable('core.entity_form_display.node.external_site_link.default');
    $config->set('content.field_node_primary_site.type', 'tide_site_restriction_field_widget');
    $config->set('content.field_node_site.type', 'tide_site_restriction_field_widget');
    $config->save();
  }

}
