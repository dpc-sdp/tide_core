<?php

namespace Drupal\tide_publication;

use Drupal\search_api\Item\Field;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\user\Entity\Role;
use Drupal\workflows\Entity\Workflow;

/**
 * Helper class for install/update ops.
 */
class TidePublicationOperation {

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
      $editorial_workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'publication');
      $editorial_workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'publication_page');
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
      if (!in_array('publication', $enabled_bundles)) {
        $bundles[] = ['entity_type' => 'node', 'bundle' => 'publication'];
        $config->set('bundles', $bundles)->save();
      }
      if (!in_array('publication_page', $enabled_bundles)) {
        $bundles[] = [
          'entity_type' => 'node',
          'bundle' => 'publication_page',
        ];
        $config->set('bundles', $bundles)->save();
      }
    }
    else {
      $publication_bundles = [
        'publication',
        'publication_page',
      ];
      foreach ($publication_bundles as $bundle) {
        $bundles[] = ['entity_type' => 'node', 'bundle' => $bundle];
        $config->set('bundles', $bundles);
      }
      $config->save();
    }
  }

  /**
   * Assign necessary permissions .
   */
  public static function assignNecessaryPermissions($revoke) {
    $publication_bundles = [
      'publication',
      'publication_page',
    ];
    foreach ($publication_bundles as $bundle) {
      $role_permissions = [
        'editor' => [
          'clone ' . $bundle . ' content',
          'create ' . $bundle . ' content',
          'edit any ' . $bundle . ' content',
          'edit own ' . $bundle . ' content',
          'revert ' . $bundle . ' revisions',
          'view ' . $bundle . ' revisions',
        ],
        'site_admin' => [
          'add scheduled transitions node ' . $bundle,
          'clone ' . $bundle . ' content',
          'create ' . $bundle . ' content',
          'delete any ' . $bundle . ' content',
          'delete ' . $bundle . ' revisions',
          'delete own ' . $bundle . ' content',
          'edit any ' . $bundle . ' content',
          'edit own ' . $bundle . ' content',
          'revert ' . $bundle . ' revisions',
          'view ' . $bundle . ' revisions',
          'view scheduled transitions node ' . $bundle,
        ],
        'approver' => [
          'add scheduled transitions node ' . $bundle,
          'create ' . $bundle . ' content',
          'delete any ' . $bundle . ' content',
          'delete ' . $bundle . ' revisions',
          'delete own ' . $bundle . ' content',
          'edit any ' . $bundle . ' content',
          'edit own ' . $bundle . ' content',
          'revert ' . $bundle . ' revisions',
          'view ' . $bundle . ' revisions',
          'view scheduled transitions node ' . $bundle,
        ],
        'contributor' => [
          'clone ' . $bundle . ' content',
          'create ' . $bundle . ' content',
          'delete any ' . $bundle . ' content',
          'delete ' . $bundle . ' revisions',
          'delete own ' . $bundle . ' content',
          'edit any ' . $bundle . ' content',
          'edit own ' . $bundle . ' content',
          'revert ' . $bundle . ' revisions',
          'view ' . $bundle . ' revisions',
        ],
      ];
      foreach ($role_permissions as $role => $permissions) {
        if (Role::load($role) && !is_null(Role::load($role))) {
          if ($revoke == TRUE) {
            user_role_revoke_permissions(Role::load($role)->id(), $permissions);
          }
          else {
            user_role_grant_permissions(Role::load($role)->id(), $permissions);
          }
        }
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
    /** @var \Drupal\search_api\IndexInterface $index */
    $index = $index_storage->load('node');

    // Index the Authors field.
    if (!$index->getField('field_publication_authors')) {
      $field_publication_authors = new Field($index, 'field_publication_authors');
      $field_publication_authors->setType('text');
      $field_publication_authors->setPropertyPath('field_publication_authors');
      $field_publication_authors->setDatasourceId('entity:node');
      $field_publication_authors->setLabel('Publication authors');
      $index->addField($field_publication_authors);
    }

    // Index the Publication date field.
    if (!$index->getField('field_publication_date')) {
      $field_publication_date = new Field($index, 'field_publication_date');
      $field_publication_date->setType('date');
      $field_publication_date->setPropertyPath('field_publication_date');
      $field_publication_date->setDatasourceId('entity:node');
      $field_publication_date->setBoost(1);
      $field_publication_date->setLabel('Date of Publication');
      $index->addField($field_publication_date);
    }

    // Index the Publication entity_reference field.
    if (!$index->getField('field_publication_title')) {
      $field_publication_title = new Field($index, 'field_publication_title');
      $field_publication_title->setType('string');
      $field_publication_title->setPropertyPath('field_publication:entity:title');
      $field_publication_title->setDatasourceId('entity:node');
      $field_publication_title->setBoost(1);
      $field_publication_title->setLabel('Publication » Content » Title');
      $index->addField($field_publication_title);
    }
    if (!$index->getField('field_publication_nid')) {
      $field_publication_nid = new Field($index, 'field_publication_nid');
      $field_publication_nid->setType('integer');
      $field_publication_nid->setPropertyPath('field_publication:entity:nid');
      $field_publication_nid->setDatasourceId('entity:node');
      $field_publication_nid->setBoost(1);
      $field_publication_nid->setLabel('Publication » Content » ID');
      $index->addField($field_publication_nid);
    }
    if (!$index->getField('field_publication_uuid')) {
      $field_publication_uuid = new Field($index, 'field_publication_uuid');
      $field_publication_uuid->setType('string');
      $field_publication_uuid->setPropertyPath('field_publication:entity:uuid');
      $field_publication_uuid->setDatasourceId('entity:node');
      $field_publication_uuid->setBoost(1);
      $field_publication_uuid->setLabel('Publication » Content » UUID');
      $index->addField($field_publication_uuid);
    }

    $index->save();
  }

  /**
   * Creates a new vocabulary.
   *
   * @param string $vocabulary
   *   The new vocabulary.
   */
  public static function createVocabulary(string $vocabulary) {
    $vocabulary = Vocabulary::create([
      'vid' => $vocabulary,
      'description' => '',
      'name' => ucfirst($vocabulary),
    ]);
    $vocabulary->save();
  }

}
