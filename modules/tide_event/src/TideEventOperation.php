<?php

namespace Drupal\tide_event;

use Drupal\search_api\Item\Field;
use Drupal\user\Entity\Role;
use Drupal\workflows\Entity\Workflow;

/**
 * Helper class for install/update ops.
 */
class TideEventOperation {

  /**
   * Add fields to Search API.
   */
  public static function addFieldsToSearchApi() {

    $moduleHandler = \Drupal::service('module_handler');
    if (!$moduleHandler->moduleExists('tide_search')) {
      return;
    }
    $index_storage = \Drupal::entityTypeManager()
      ->getStorage('search_api_index');
    $index = $index_storage->load('node');

    // Index start date of event date field.
    $field_start_date = new Field($index, 'field_event_date_start_value');
    $field_start_date->setType('date');
    $field_start_date->setPropertyPath('field_event_details:entity:field_paragraph_date_range');
    $field_start_date->setDatasourceId('entity:node');
    $field_start_date->setLabel('Event Details » Date Range » Start date value');
    $index->addField($field_start_date);

    // Index end date of the event date field.
    $field_end_date = new Field($index, 'field_event_date_end_value');
    $field_end_date->setType('date');
    $field_end_date->setPropertyPath('field_event_details:entity:field_paragraph_date_range:end_value');
    $field_end_date->setDatasourceId('entity:node');
    $field_end_date->setLabel('Event Details » Date Range » End date value');
    $index->addField($field_end_date);

    // Index event category tid field.
    $field_event_category_tid = new Field($index, 'field_event_category_tid');
    $field_event_category_tid->setType('integer');
    $field_event_category_tid->setPropertyPath('field_event_category');
    $field_event_category_tid->setDatasourceId('entity:node');
    $field_event_category_tid->setLabel('Event Category');
    $index->addField($field_event_category_tid);

    // Index event category:name field.
    $field_event_category_name = new Field($index, 'field_event_category_name');
    $field_event_category_name->setType('string');
    $field_event_category_name->setPropertyPath('field_event_category:entity:name');
    $field_event_category_name->setDatasourceId('entity:node');
    $field_event_category_name->setLabel('Event Category » Taxonomy term » Name');
    $index->addField($field_event_category_name);

    // Index event category:uuid field.
    $field_event_category_uuid = new Field($index, 'field_event_category_uuid');
    $field_event_category_uuid->setType('string');
    $field_event_category_uuid->setPropertyPath('field_event_category:entity:uuid');
    $field_event_category_uuid->setDatasourceId('entity:node');
    $field_event_category_uuid->setLabel('Event Category » Taxonomy term » UUID');
    $index->addField($field_event_category_uuid);

    // Index field_news_intro_text field.
    $field_event_intro_text = new Field($index, 'field_event_intro_text');
    $field_event_intro_text->setType('text');
    $field_event_intro_text->setPropertyPath('field_news_intro_text');
    $field_event_intro_text->setDatasourceId('entity:node');
    $field_event_intro_text->setLabel('Event Introduction Text');
    $index->addField($field_event_intro_text);

    // Index field_event_description field.
    $field_event_description = new Field($index, 'field_event_description');
    $field_event_description->setType('text');
    $field_event_description->setPropertyPath('field_event_description');
    $field_event_description->setDatasourceId('entity:node');
    $field_event_description->setLabel('Event Description');
    $index->addField($field_event_description);

    // Index field_event_details_event_address_1 field.
    $field_event_details_event_address_1 = new Field($index, 'field_event_details_event_address_1');
    $field_event_details_event_address_1->setType('text');
    $field_event_details_event_address_1->setPropertyPath('field_event_details:entity:field_paragraph_location:address_line1');
    $field_event_details_event_address_1->setDatasourceId('entity:node');
    $field_event_details_event_address_1->setLabel('Event Details » Location »  The first line of the address block');
    $index->addField($field_event_details_event_address_1);

    // Index field_event_details_event_address_line2 field.
    $field_event_details_event_address_2 = new Field($index, 'field_event_details_event_address_line2');
    $field_event_details_event_address_2->setType('text');
    $field_event_details_event_address_2->setPropertyPath('field_event_details:entity:field_paragraph_location:address_line2');
    $field_event_details_event_address_2->setDatasourceId('entity:node');
    $field_event_details_event_address_2->setLabel('Event Details » Location »  The second line of the address block');
    $index->addField($field_event_details_event_address_2);

    // Index field_event_details_event_administrative_area field.
    $field_event_details_event_administrative_area = new Field($index, 'field_event_details_event_administrative_area');
    $field_event_details_event_administrative_area->setType('text');
    $field_event_details_event_administrative_area->setPropertyPath('field_event_details:entity:field_paragraph_location:administrative_area');
    $field_event_details_event_administrative_area->setDatasourceId('entity:node');
    $field_event_details_event_administrative_area->setLabel('Event Details » Location » The top-level administrative subdivision of the country');
    $index->addField($field_event_details_event_administrative_area);

    // Index field_event_details_event_locality field.
    $field_event_details_event_locality = new Field($index, 'field_event_details_event_locality');
    $field_event_details_event_locality->setType('text');
    $field_event_details_event_locality->setPropertyPath('field_event_details:entity:field_paragraph_location:locality');
    $field_event_details_event_locality->setDatasourceId('entity:node');
    $field_event_details_event_locality->setLabel('Event Details » Location » The locality');
    $index->addField($field_event_details_event_locality);

    // Index field_event_details_event_postal_code field.
    $field_event_details_event_postal_code = new Field($index, 'field_event_details_event_postal_code');
    $field_event_details_event_postal_code->setType('text');
    $field_event_details_event_postal_code->setPropertyPath('field_event_details:entity:field_paragraph_location:postal_code');
    $field_event_details_event_postal_code->setDatasourceId('entity:node');
    $field_event_details_event_postal_code->setLabel('Event Details » Location » The postal code');
    $index->addField($field_event_details_event_postal_code);

    // Index field_event_details_event_price_from field.
    $field_event_details_event_price_from = new Field($index, 'field_event_details_event_price_from');
    $field_event_details_event_price_from->setType('string');
    $field_event_details_event_price_from->setPropertyPath('field_event_details:entity:field_paragraph_event_price_from');
    $field_event_details_event_price_from->setDatasourceId('entity:node');
    $field_event_details_event_price_from->setLabel('Event Details » Price');
    $index->addField($field_event_details_event_price_from);

    // Index field_event_details_event_price_to field.
    $field_event_details_event_price_to = new Field($index, 'field_event_details_event_price_to');
    $field_event_details_event_price_to->setType('string');
    $field_event_details_event_price_to->setPropertyPath('field_event_details:entity:field_paragraph_event_price_to');
    $field_event_details_event_price_to->setDatasourceId('entity:node');
    $field_event_details_event_price_to->setLabel('Event Details » Price to');
    $index->addField($field_event_details_event_price_to);

    // Index field_event_details_event_requirements_name field.
    $field_event_details_event_requirements_name = new Field($index, 'field_event_details_event_requirements_name');
    $field_event_details_event_requirements_name->setType('string');
    $field_event_details_event_requirements_name->setPropertyPath('field_event_details:entity:field_event_requirements:entity:name');
    $field_event_details_event_requirements_name->setDatasourceId('entity:node');
    $field_event_details_event_requirements_name->setLabel('Event Details » Event Requirements » Name');
    $index->addField($field_event_details_event_requirements_name);

    // Index field_event_details_event_requirements_uuid field.
    $field_event_details_event_requirements_uuid = new Field($index, 'field_event_details_event_requirements_uuid');
    $field_event_details_event_requirements_uuid->setType('string');
    $field_event_details_event_requirements_uuid->setPropertyPath('field_event_details:entity:field_event_requirements:entity:uuid');
    $field_event_details_event_requirements_uuid->setDatasourceId('entity:node');
    $field_event_details_event_requirements_uuid->setLabel('Event Details » Event Requirements » UUID');
    $index->addField($field_event_details_event_requirements_uuid);

    // Index field_event_details_event_requirements_tid field.
    $field_event_details_event_requirements_tid = new Field($index, 'field_event_details_event_requirements_tid');
    $field_event_details_event_requirements_tid->setType('integer');
    $field_event_details_event_requirements_tid->setPropertyPath('field_event_details:entity:field_event_requirements');
    $field_event_details_event_requirements_tid->setDatasourceId('entity:node');
    $field_event_details_event_requirements_tid->setLabel('Event Details » Event Requirements');
    $index->addField($field_event_details_event_requirements_tid);

    $index->save();
  }

  /**
   * Assign necessary permissions .
   */
  public static function assignNecessaryPermissions($revoke) {
    $role_permissions = [
      'editor' => [
        'clone event content',
        'create event content',
        'edit any event content',
        'edit own event content',
        'revert event revisions',
        'view event revisions',
      ],
      'site_admin' => [
        'add scheduled transitions node event',
        'create event content',
        'clone event content',
        'delete any event content',
        'delete event revisions',
        'delete own event content',
        'edit any event content',
        'edit own event content',
        'revert event revisions',
        'view event revisions',
        'view scheduled transitions node event',
      ],
      'approver' => [
        'add scheduled transitions node event',
        'create event content',
        'delete any event content',
        'delete event revisions',
        'delete own event content',
        'edit any event content',
        'edit own event content',
        'revert event revisions',
        'view event revisions',
        'view scheduled transitions node event',
      ],
      'contributer' => [
        'create event content',
        'clone event content',
        'delete any event content',
        'delete event revisions',
        'delete own event content',
        'edit any event content',
        'edit own event content',
        'revert event revisions',
        'view event revisions',
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

  /**
   * Enable editorial workflow and shceduled transitions.
   */
  public static function enableNecessaryModules() {
    // Enable Editorial workflow if workflow module is enabled.
    $moduleHandler = \Drupal::service('module_handler');
    if ($moduleHandler->moduleExists('workflows')) {
      $editorial_workflow = Workflow::load('editorial');
      if ($editorial_workflow) {
        $editorial_workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'event');
        $editorial_workflow->save();
      }
    }

    // Enable entity type/bundles for use with scheduled transitions.
    if (\Drupal::moduleHandler()->moduleExists('scheduled_transitions')) {
      $config_factory = \Drupal::configFactory();
      $config = $config_factory->getEditable('scheduled_transitions.settings');
      $bundles = $config->get('bundles');
      if ($bundles) {
        foreach ($bundles as $bundle) {
          $enabled_bundles = [];
          $enabled_bundles[] = $bundle['bundle'];
        }
        if (!in_array('event', $enabled_bundles)) {
          $bundles[] = ['entity_type' => 'node', 'bundle' => 'event'];
          $config->set('bundles', $bundles)->save();
        }
      }
      else {
        $bundles[] = ['entity_type' => 'node', 'bundle' => 'event'];
        $config->set('bundles', $bundles)->save();
      }
    }
  }

  /**
   * Add default value to Content Category.
   */
  public static function addDefaultValueToContentCategory() {
    \Drupal::moduleHandler()->loadInclude('tide_core', 'inc', 'includes/updates');
    _tide_core_field_content_category_default_value('event', 'Event');
  }

}
