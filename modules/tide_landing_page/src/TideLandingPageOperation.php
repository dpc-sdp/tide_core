<?php

namespace Drupal\tide_landing_page;

use Drupal\field\Entity\FieldConfig;
use Drupal\search_api\Item\Field;
use Drupal\user\Entity\Role;
use Drupal\workflows\Entity\Workflow;

/**
 * Helper class for install/update ops.
 */
class TideLandingPageOperation {

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
      $editorial_workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'landing_page');
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
      if (!in_array('landing_page', $enabled_bundles)) {
        $bundles[] = ['entity_type' => 'node', 'bundle' => 'landing_page'];
        $config->set('bundles', $bundles)->save();
      }
    }
    else {
      $bundles[] = ['entity_type' => 'node', 'bundle' => 'landing_page'];
      $config->set('bundles', $bundles)->save();
    }
  }

  /**
   * Add the Embedded Webform paragraph to Landing Page component if exists.
   */
  public static function addWebformComponent() {
    /** @var \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler */
    $moduleHandler = \Drupal::service('module_handler');
    // Add the Embedded Webform paragraph to Landing Page component if exists.
    if ($moduleHandler->moduleExists('tide_webform')) {
      return;
    }
    $field_config = FieldConfig::loadByName('node', 'landing_page', 'field_landing_page_component');
    if ($field_config) {
      $handler_settings = $field_config->getSetting('handler_settings');
      $handler_settings['target_bundles']['embedded_webform'] = 'embedded_webform';
      $field_config->setSetting('handler_settings', $handler_settings);
      $field_config->save();
    }
  }

  /**
   * Assign necessary permissions .
   */
  public static function assignNecessaryPermissions() {
    $role_permissions = [
      'editor' => [
        'clone landing_page content',
        'create landing_page content',
        'edit any landing_page content',
        'edit own landing_page content',
        'revert landing_page revisions',
        'view landing_page revisions',
      ],
      'site_admin' => [
        'add scheduled transitions node landing_page',
        'clone landing_page content',
        'create landing_page content',
        'delete any landing_page content',
        'delete landing_page revisions',
        'delete own landing_page content',
        'edit any landing_page content',
        'edit own landing_page content',
        'revert landing_page revisions',
        'view landing_page revisions',
        'view scheduled transitions node landing_page',
      ],
      'approver' => [
        'add scheduled transitions node landing_page',
        'create landing_page content',
        'delete any landing_page content',
        'delete landing_page revisions',
        'delete own landing_page content',
        'edit any landing_page content',
        'edit own landing_page content',
        'revert landing_page revisions',
        'view landing_page revisions',
        'view scheduled transitions node landing_page',
      ],
      'contributor' => [
        'clone landing_page content',
        'create landing_page content',
        'delete any landing_page content',
        'delete landing_page revisions',
        'delete own landing_page content',
        'edit any landing_page content',
        'edit own landing_page content',
        'revert landing_page revisions',
        'view landing_page revisions',
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

    // Index the Intro field.
    $field_landing_page_intro = new Field($index, 'field_landing_page_intro_text');
    $field_landing_page_intro->setType('text');
    $field_landing_page_intro->setPropertyPath('field_landing_page_intro_text');
    $field_landing_page_intro->setDatasourceId('entity:node');
    $field_landing_page_intro->setLabel('Introduction text');
    $index->addField($field_landing_page_intro);

    // Index the summary field.
    $field_landing_page_summary = new Field($index, 'field_landing_page_summary');
    $field_landing_page_summary->setType('text');
    $field_landing_page_summary->setPropertyPath('field_landing_page_summary');
    $field_landing_page_summary->setDatasourceId('entity:node');
    $field_landing_page_summary->setBoost(1);
    $field_landing_page_summary->setLabel('Summary');
    $index->addField($field_landing_page_summary);

    // Index the field field_paragraph_body.
    $field_paragraph_body = new Field($index, 'field_paragraph_body');
    $field_paragraph_body->setType('text');
    $field_paragraph_body->setPropertyPath('field_landing_page_component:entity:field_paragraph_body');
    $field_paragraph_body->setDatasourceId('entity:node');
    $field_paragraph_body->setLabel('Content components » Paragraph » Body');
    $index->addField($field_paragraph_body);

    // Index the field field_paragraph_topic.
    $field_paragraph_topic = new Field($index, 'field_paragraph_topic');
    $field_paragraph_topic->setType('integer');
    $field_paragraph_topic->setPropertyPath('field_landing_page_component:entity:field_paragraph_topic');
    $field_paragraph_topic->setDatasourceId('entity:node');
    $field_paragraph_topic->setLabel('Content components » Paragraph » Topic');
    $index->addField($field_paragraph_topic);

    // Index the field field_paragraph_topic_name.
    $field_paragraph_topic_name = new Field($index, 'field_paragraph_topic_name');
    $field_paragraph_topic_name->setType('integer');
    $field_paragraph_topic_name->setPropertyPath('field_landing_page_component:entity:field_paragraph_topic:entity:name');
    $field_paragraph_topic_name->setDatasourceId('entity:node');
    $field_paragraph_topic_name->setBoost(5);
    $field_paragraph_topic_name->setLabel('Content components » Paragraph » Topic » Taxonomy term » Name');
    $index->addField($field_paragraph_topic_name);

    // Index the field field_paragraph_title.
    $field_paragraph_title = new Field($index, 'field_paragraph_title');
    $field_paragraph_title->setType('text');
    $field_paragraph_title->setPropertyPath('field_landing_page_component:entity:field_paragraph_title');
    $field_paragraph_title->setDatasourceId('entity:node');
    $field_paragraph_title->setLabel('Content components » Paragraph » Title');
    $field_paragraph_title->setBoost(13);
    $index->addField($field_paragraph_title);

    // Index the summary field field_paragraph_summary.
    $field_paragraph_summary = new Field($index, 'field_paragraph_summary');
    $field_paragraph_summary->setType('text');
    $field_paragraph_summary->setPropertyPath('field_landing_page_component:entity:field_paragraph_summary');
    $field_paragraph_summary->setDatasourceId('entity:node');
    $field_paragraph_summary->setLabel('Content components » Paragraph » Summary');
    $index->addField($field_paragraph_summary);

    // Index the summary field field_paragraph_accordion_name.
    $field_paragraph_accordion_name = new Field($index, 'field_paragraph_accordion_name');
    $field_paragraph_accordion_name->setType('text');
    $field_paragraph_accordion_name->setPropertyPath('field_landing_page_component:entity:field_paragraph_accordion_name');
    $field_paragraph_accordion_name->setDatasourceId('entity:node');
    $field_paragraph_accordion_name->setBoost(5);
    $field_paragraph_accordion_name->setLabel('Content components » Paragraph » Accordion Content » Paragraph » Item Name');
    $index->addField($field_paragraph_accordion_name);

    // Index the summary field field_paragraph_accordion_body.
    $field_paragraph_accordion_body = new Field($index, 'field_paragraph_accordion_body');
    $field_paragraph_accordion_body->setType('text');
    $field_paragraph_accordion_body->setPropertyPath('field_landing_page_component:entity:field_paragraph_accordion_body');
    $field_paragraph_accordion_body->setDatasourceId('entity:node');
    $field_paragraph_accordion_body->setLabel('Content components » Paragraph » Accordion Content » Paragraph » Body');
    $index->addField($field_paragraph_accordion_body);

    // Index the summary field field_landing_page_contact_name.
    $field_landing_page_contact_name = new Field($index, 'field_landing_page_contact_name');
    $field_landing_page_contact_name->setType('text');
    $field_landing_page_contact_name->setPropertyPath('field_landing_page_contact:entity:field_paragraph_name');
    $field_landing_page_contact_name->setDatasourceId('entity:node');
    $field_landing_page_contact_name->setBoost(1);
    $field_landing_page_contact_name->setLabel('Content components » Paragraph » Name');
    $index->addField($field_landing_page_contact_name);

    // Index the summary field field_landing_page_contact_body.
    $field_landing_page_contact_body = new Field($index, 'field_landing_page_contact_body');
    $field_landing_page_contact_body->setType('text');
    $field_landing_page_contact_body->setPropertyPath('field_landing_page_contact:entity:field_paragraph_body');
    $field_landing_page_contact_body->setDatasourceId('entity:node');
    $field_landing_page_contact_body->setLabel('Content components » Paragraph » Body');
    $index->addField($field_landing_page_contact_body);

    // Index the summary field field_paragraph_topic_uuid.
    $field_paragraph_topic_uuid = new Field($index, 'field_paragraph_topic_uuid');
    $field_paragraph_topic_uuid->setType('string');
    $field_paragraph_topic_uuid->setPropertyPath('field_landing_page_component:entity:field_paragraph_topic:entity:uuid');
    $field_paragraph_topic_uuid->setDatasourceId('entity:node');
    $field_paragraph_topic_uuid->setLabel('Content components » Paragraph » Topic » Taxonomy term » UUID');
    $index->addField($field_paragraph_topic_uuid);

    $index->save();
  }

}
