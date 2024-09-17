<?php

namespace Drupal\tide_profile;

use Drupal\search_api\Item\Field;
use Drupal\user\Entity\Role;
use Drupal\workflows\Entity\Workflow;

/**
 * Helper class for install/update ops.
 */
class TideProfileOperation {

  /**
   * Add news content type to editorial workflows.
   */
  public static function addToWorkflows() {
    // Enable Editorial workflow if workflow module is enabled.
    if (!(\Drupal::moduleHandler()->moduleExists('workflows'))) {
      return;
    }
    $editorial_workflow = Workflow::load('editorial');
    if ($editorial_workflow) {
      $editorial_workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'profile');
      $editorial_workflow->save();
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
      if (!in_array('profile', $enabled_bundles)) {
        $bundles[] = ['entity_type' => 'node', 'bundle' => 'profile'];
        $config->set('bundles', $bundles)->save();
      }
    }
    else {
      $bundles[] = ['entity_type' => 'node', 'bundle' => 'profile'];
      $config->set('bundles', $bundles)->save();
    }
  }

  /**
   * Assign necessary permissions .
   */
  public static function assignNecessaryPermissions() {
    $role_permissions = [
      'editor' => [
        'clone profile content',
        'create profile content',
        'edit any profile content',
        'edit own profile content',
        'revert profile revisions',
        'view profile revisions',
      ],
      'site_admin' => [
        'add scheduled transitions node profile',
        'clone profile content',
        'create profile content',
        'delete any profile content',
        'delete profile revisions',
        'delete own profile content',
        'edit any profile content',
        'edit own profile content',
        'revert profile revisions',
        'view profile revisions',
        'view scheduled transitions node profile',
      ],
      'approver' => [
        'add scheduled transitions node profile',
        'create profile content',
        'delete any profile content',
        'delete profile revisions',
        'delete own profile content',
        'edit any profile content',
        'edit own profile content',
        'revert profile revisions',
        'view profile revisions',
        'view scheduled transitions node profile',
      ],
      'contributor' => [
        'clone profile content',
        'create profile content',
        'delete any profile content',
        'delete profile revisions',
        'delete own profile content',
        'edit any profile content',
        'edit own profile content',
        'revert profile revisions',
        'view profile revisions',
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
    $index_storage = \Drupal::entityTypeManager()
      ->getStorage('search_api_index');
    $index = $index_storage->load('node');

    // Index the Introduction field.
    $field_profile_intro = new Field($index, 'field_profile_intro_text');
    $field_profile_intro->setType('text');
    $field_profile_intro->setPropertyPath('field_profile_intro_text');
    $field_profile_intro->setDatasourceId('entity:node');
    $field_profile_intro->setLabel('Profile Introduction Text');
    $index->addField($field_profile_intro);

    // Index field_life_span field.
    $field_life_span = new Field($index, 'field_life_span');
    $field_life_span->setType('string');
    $field_life_span->setPropertyPath('field_life_span');
    $field_life_span->setDatasourceId('entity:node');
    $field_life_span->setLabel('Profile Lifespan');
    $index->addField($field_life_span);

    // Index profile category tid field.
    $field_profile_category = new Field($index, 'field_profile_category');
    $field_profile_category->setType('integer');
    $field_profile_category->setPropertyPath('field_profile_category');
    $field_profile_category->setDatasourceId('entity:node');
    $field_profile_category->setLabel('Profile Category');
    $index->addField($field_profile_category);

    // Index profile category:name field.
    $field_profile_category_name = new Field($index, 'field_profile_category_name');
    $field_profile_category_name->setType('string');
    $field_profile_category_name->setPropertyPath('field_profile_category:entity:name');
    $field_profile_category_name->setDatasourceId('entity:node');
    $field_profile_category_name->setLabel('Profile Category » Taxonomy term » Name');
    $index->addField($field_profile_category_name);

    // Index profile category:uuid field.
    $field_profile_category_uuid = new Field($index, 'field_profile_category_uuid');
    $field_profile_category_uuid->setType('string');
    $field_profile_category_uuid->setPropertyPath('field_profile_category:entity:uuid');
    $field_profile_category_uuid->setDatasourceId('entity:node');
    $field_profile_category_uuid->setLabel('Profile Category » Taxonomy term » UUID');
    $index->addField($field_profile_category_uuid);

    // Index profile expertise tid field.
    $field_profile_expertise = new Field($index, 'field_profile_expertise');
    $field_profile_expertise->setType('integer');
    $field_profile_expertise->setPropertyPath('field_expertise');
    $field_profile_expertise->setDatasourceId('entity:node');
    $field_profile_expertise->setLabel('Profile Expertise');
    $index->addField($field_profile_expertise);

    // Index profile expertise:name field.
    $field_profile_expertise_name = new Field($index, 'field_profile_expertise_name');
    $field_profile_expertise_name->setType('string');
    $field_profile_expertise_name->setPropertyPath('field_expertise:entity:name');
    $field_profile_expertise_name->setDatasourceId('entity:node');
    $field_profile_expertise_name->setLabel('Profile Expertise » Taxonomy term » Name');
    $index->addField($field_profile_expertise_name);

    // Index profile expertise:uuid field.
    $field_profile_expertise_uuid = new Field($index, 'field_profile_expertise_uuid');
    $field_profile_expertise_uuid->setType('string');
    $field_profile_expertise_uuid->setPropertyPath('field_expertise:entity:uuid');
    $field_profile_expertise_uuid->setDatasourceId('entity:node');
    $field_profile_expertise_uuid->setLabel('Profile Expertise » Taxonomy term » UUID');
    $index->addField($field_profile_expertise_uuid);

    // Index profile location tid field.
    $field_location = new Field($index, 'field_location');
    $field_location->setType('integer');
    $field_location->setPropertyPath('field_location');
    $field_location->setDatasourceId('entity:node');
    $field_location->setLabel('Profile Location');
    $index->addField($field_location);

    // Index profile location:name field.
    $field_location_name = new Field($index, 'field_location_name');
    $field_location_name->setType('string');
    $field_location_name->setPropertyPath('field_location:entity:name');
    $field_location_name->setDatasourceId('entity:node');
    $field_location_name->setLabel('Profile Location » Taxonomy term » Name');
    $index->addField($field_location_name);

    // Index profile location:uuid field.
    $field_location_uuid = new Field($index, 'field_location_uuid');
    $field_location_uuid->setType('string');
    $field_location_uuid->setPropertyPath('field_location:entity:uuid');
    $field_location_uuid->setDatasourceId('entity:node');
    $field_location_uuid->setLabel('Profile Location » Taxonomy term » UUID');
    $index->addField($field_location_uuid);

    // Index Induction Year field.
    $field_year = new Field($index, 'field_year');
    $field_year->setType('string');
    $field_year->setPropertyPath('field_year');
    $field_year->setDatasourceId('entity:node');
    $field_year->setLabel('Induction Year');
    $index->addField($field_year);

    $index->save();
  }

}
