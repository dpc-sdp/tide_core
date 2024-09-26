<?php

namespace Drupal\tide_search;

use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\Role;

/**
 * Tide search modules operations.
 */
class TideSearchOperation {

  /**
   * Remove tide_alert content type from data source if module exists.
   */
  public function removeTideAlertFromDatasource() {
    if (!(\Drupal::moduleHandler()->moduleExists('tide_alert'))) {
      return;
    }
    $config_factory = \Drupal::configFactory();
    $config = $config_factory->getEditable('search_api.index.node');
    $node_datasource_settings = $config->get('datasource_settings.entity:node.bundles.selected');
    if (is_array($node_datasource_settings) && !in_array('alert', $node_datasource_settings)) {
      $node_datasource_settings[] = 'alert';
      $config->set('datasource_settings.entity:node.bundles.selected', $node_datasource_settings);
      $config->save();
    }
  }

  /**
   * Adds all the exported configuration for the Search Listing config type.
   */
  public function createSearchListingContentType(array $config_location) {
    $configs = [
      'taxonomy.vocabulary.search_listing_custom_header_com' => 'taxonomy_vocabulary',
      'taxonomy.vocabulary.listing_layout_comp_taxonomy' => 'taxonomy_vocabulary',
      'taxonomy.vocabulary.listing_results_comp_taxonomy' => 'taxonomy_vocabulary',
      'taxonomy.vocabulary.searchable_fields' => 'taxonomy_vocabulary',
      'paragraphs.paragraphs_type.search_listing_header_component' => 'paragraphs_type',
      'paragraphs.paragraphs_type.listing_content_type' => 'paragraphs_type',
      'paragraphs.paragraphs_type.listing_custom_filter' => 'paragraphs_type',
      'paragraphs.paragraphs_type.listing_site' => 'paragraphs_type',
      'paragraphs.paragraphs_type.listing_user_custom_filter' => 'paragraphs_type',
      'paragraphs.paragraphs_type.searchable_fields' => 'paragraphs_type',
      'field.storage.paragraph.field_component' => 'field_storage_config',
      'field.storage.paragraph.field_header_configuration' => 'field_storage_config',
      'field.storage.paragraph.field_listing_custom_filter_conf' => 'field_storage_config',
      'field.storage.paragraph.field_listing_global_contenttype' => 'field_storage_config',
      'field.storage.paragraph.field_listing_site_site' => 'field_storage_config',
      'field.storage.paragraph.field_user_filter_configuration' => 'field_storage_config',
      'field.storage.paragraph.field_field' => 'field_storage_config',
      'field.storage.paragraph.field_input_label' => 'field_storage_config',
      'field.storage.paragraph.field_placeholder' => 'field_storage_config',
      'field.storage.taxonomy_term.field_elasticsearch_field' => 'field_storage_config',
      'field.storage.taxonomy_term.field_taxonomy_machine_name' => 'field_storage_config',
      'field.field.paragraph.search_listing_header_component.field_component' => 'field_config',
      'field.field.paragraph.search_listing_header_component.field_header_configuration' => 'field_config',
      'field.field.paragraph.listing_content_type.field_listing_global_contenttype' => 'field_config',
      'field.field.paragraph.listing_custom_filter.field_listing_custom_filter_conf' => 'field_config',
      'field.field.paragraph.listing_site.field_listing_site_site' => 'field_config',
      'field.field.paragraph.listing_user_custom_filter.field_user_filter_configuration' => 'field_config',
      'field.field.paragraph.searchable_fields.field_field' => 'field_config',
      'field.field.paragraph.searchable_fields.field_input_label' => 'field_config',
      'field.field.paragraph.searchable_fields.field_placeholder' => 'field_config',
      'field.field.taxonomy_term.searchable_fields.field_elasticsearch_field' => 'field_config',
      'field.field.taxonomy_term.searchable_fields.field_taxonomy_machine_name' => 'field_config',
      'node.type.tide_search_listing' => 'node_type',
      'field.storage.node.field_header_components' => 'field_storage_config',
      'field.storage.node.field_search_configuration' => 'field_storage_config',
      'field.storage.node.field_listing_query_config' => 'field_storage_config',
      'field.storage.node.field_listing_global_filters' => 'field_storage_config',
      'field.storage.node.field_search_input_placeholder' => 'field_storage_config',
      'field.storage.node.field_search_submit_label' => 'field_storage_config',
      'field.storage.node.field_listing_results_per_page' => 'field_storage_config',
      'field.storage.node.field_listing_user_filters' => 'field_storage_config',
      'field.storage.node.field_listing_results_config' => 'field_storage_config',
      'field.storage.node.field_layout_component' => 'field_storage_config',
      'field.storage.node.field_results_component' => 'field_storage_config',
      'field.storage.node.field_below_results_content' => 'field_storage_config',
      'field.storage.node.field_custom_sort_configuration' => 'field_storage_config',
      'field.field.node.tide_search_listing.field_featured_image' => 'field_config',
      'field.field.node.tide_search_listing.field_header_components' => 'field_config',
      'field.field.node.tide_search_listing.field_landing_page_intro_text' => 'field_config',
      'field.field.node.tide_search_listing.field_landing_page_summary' => 'field_config',
      'field.field.node.tide_search_listing.field_listing_global_filters' => 'field_config',
      'field.field.node.tide_search_listing.field_metatags' => 'field_config',
      'field.field.node.tide_search_listing.field_search_configuration' => 'field_config',
      'field.field.node.tide_search_listing.field_show_content_rating' => 'field_config',
      'field.field.node.tide_search_listing.field_tags' => 'field_config',
      'field.field.node.tide_search_listing.field_topic' => 'field_config',
      'field.field.node.tide_search_listing.field_listing_query_config' => 'field_config',
      'field.field.node.tide_search_listing.field_search_input_placeholder' => 'field_config',
      'field.field.node.tide_search_listing.field_search_submit_label' => 'field_config',
      'field.field.node.tide_search_listing.field_listing_results_per_page' => 'field_config',
      'field.field.node.tide_search_listing.field_listing_user_filters' => 'field_config',
      'field.field.node.tide_search_listing.field_listing_results_config' => 'field_config',
      'field.field.node.tide_search_listing.field_layout_component' => 'field_config',
      'field.field.node.tide_search_listing.field_results_component' => 'field_config',
      'field.field.node.tide_search_listing.field_below_results_content' => 'field_config',
      'field.field.node.tide_search_listing.field_custom_sort_configuration' => 'field_config',
      'core.entity_form_display.paragraph.search_listing_header_component.default' => 'entity_form_display',
      'core.entity_form_display.paragraph.listing_content_type.default' => 'entity_form_display',
      'core.entity_form_display.paragraph.listing_custom_filter.default' => 'entity_form_display',
      'core.entity_form_display.paragraph.listing_site.default' => 'entity_form_display',
      'core.entity_form_display.paragraph.listing_user_custom_filter.default' => 'entity_form_display',
      'core.entity_form_display.paragraph.searchable_fields.default' => 'entity_form_display',
      'core.entity_form_display.taxonomy_term.searchable_fields.default' => 'entity_form_display',
      'core.entity_view_display.node.tide_search_listing.default' => 'entity_view_display',
      'core.entity_view_display.node.tide_search_listing.teaser' => 'entity_view_display',
      'core.entity_view_display.paragraph.search_listing_header_component.default' => 'entity_view_display',
      'core.entity_view_display.paragraph.listing_content_type.default' => 'entity_view_display',
      'core.entity_view_display.paragraph.listing_custom_filter.default' => 'entity_view_display',
      'core.entity_view_display.paragraph.listing_site.default' => 'entity_view_display',
      'core.entity_view_display.paragraph.listing_user_custom_filter.default' => 'entity_view_display',
      'core.entity_view_display.paragraph.searchable_fields.default' => 'entity_view_display',
      'core.entity_view_display.taxonomy_term.searchable_fields.default' => 'entity_view_display',
    ];

    \Drupal::moduleHandler()->loadInclude('tide_core', 'inc', 'includes/helpers');
    foreach ($configs as $config => $type) {
      $config_read = _tide_read_config($config, $config_location, TRUE);
      $storage = \Drupal::entityTypeManager()->getStorage($type);
      $id = substr($config, strrpos($config, '.') + 1);
      if ($storage->load($id) == NULL) {
        // Try using an extended id as well.
        $id = substr($config, strrpos($config, '.', strrpos($config, '.') - strlen($config) - 1) + 1);
        if ($storage->load($id) == NULL) {
          error_log(" tide_search - importing config: " . $id);
          $config_entity = $storage->createFromStorageRecord($config_read);
          $config_entity->save();
        }
      }
    }
  }

  /**
   * Update form display after importing the search listing content type.
   */
  public function updateSearchListingFormDisplay(array $config_location) {
    $config_id = 'core.entity_form_display.node.tide_search_listing.default';
    $config = \Drupal::configFactory()->getEditable($config_id);
    $config_read = _tide_read_config($config_id, $config_location, FALSE);
    $config->set('content', $config_read['content']);
    $config->set('hidden', $config_read['hidden']);
    $config->set('third_party_settings', $config_read['third_party_settings']);
    error_log(" tide_search - importing form config");
    $config->save();
  }

  /**
   * Add permissions for the search listing content type.
   */
  public function addSearchListingPermissions() {
    $role = Role::load('site_admin');
    if ($role) {
      error_log(" tide_search - adding permissions to site admin");
      $role->grantPermission('create tide_search_listing content');
      $role->grantPermission('delete own tide_search_listing content');
      $role->grantPermission('edit own tide_search_listing content');
      $role->grantPermission('add scheduled transitions node tide_search_listing');
      $role->grantPermission('view scheduled transitions node tide_search_listing');
      $role->save();
    }
    Role::load('anonymous')->grantPermission('access taxonomy overview')->save();
    Role::load('authenticated')->grantPermission('access taxonomy overview')->save();
  }

  /**
   * Add workflows for search listing content type.
   */
  public function addSearchListingWorkflows() {
    error_log(" tide_search - adding workflows for search listing");
    $config = \Drupal::configFactory()->getEditable('workflows.workflow.editorial');
    $dependencies = $config->get('dependencies.config');
    $dependencies[] = 'node.type.tide_search_listing';
    $config->set('dependencies.config', $dependencies);
    $type_settings = $config->get('type_settings.entity_types.node');
    $type_settings[] = 'tide_search_listing';
    $config->set('type_settings.entity_types.node', $type_settings);
    $config->save();
  }

  /**
   * Add supplied terms to supplied vocabulary.
   */
  public function addTaxonomyTerms(string $vocabulary, array $terms) {
    foreach ($terms as $term) {
      Term::create([
        'name' => $term,
        'vid' => $vocabulary,
        'parent' => [],
      ])->save();
    }
  }

  /**
   * Add supplied terms to searchable fields.
   */
  public function addSearchableFieldsTerms() {
    $terms = [
      [
        'name' => 'Audience (Grants)',
        'field_elasticsearch_field' => 'field_audience_name',
        'field_taxonomy_machine_name' => 'audience',
      ],
      [
        'name' => 'Profile - Australia Day Ambassadors - Category',
        'field_elasticsearch_field' => 'field_profile_category_name',
        'field_taxonomy_machine_name' => 'vada_categories',
      ],
      [
        'name' => 'Profile - Expertise',
        'field_elasticsearch_field' => 'field_profile_expertise_name',
        'field_taxonomy_machine_name' => 'expertise',
      ],
      [
        'name' => 'Recommendations - Family Violence - Category',
        'field_elasticsearch_field' => 'field_fv_recommendation_category_name',
        'field_taxonomy_machine_name' => 'fv_recommendation_category',
      ],
      [
        'name' => 'Topic',
        'field_elasticsearch_field' => 'field_topic_name',
        'field_taxonomy_machine_name' => 'topic',
      ],
    ];
    foreach ($terms as $term) {
      Term::create([
        'name' => $term['name'],
        'vid' => 'searchable_fields',
        'field_elasticsearch_field' => $term['field_elasticsearch_field'],
        'field_taxonomy_machine_name' => $term['field_taxonomy_machine_name'],
        'parent' => [],
      ])->save();
    }
  }

  /**
   * Allow node type and taxonomy vocab over JSONAPI.
   */
  public function allowJsonApiResources() {
    $config_factory = \Drupal::configFactory();
    $config = $config_factory->getEditable('jsonapi_extras.jsonapi_resource_config.node_type--node_type');
    $config->set('disabled', FALSE);
    $config->save();

    $config = $config_factory->getEditable('jsonapi_extras.jsonapi_resource_config.taxonomy_vocabulary--taxonomy_vocabulary');
    $config->set('disabled', FALSE);
    $config->save();
  }

  /**
   * Ensure that the required field config for search for Ripple 2.0 is present.
   */
  public function setCoreSearchApiFields() {
    error_log(" tide_search - setting core search api fields for ripple 2.0");
    $config = \Drupal::configFactory()->getEditable('search_api.index.node');

    $dependencies = $config->get('dependencies.config');
    $new_dependencies = [
      'field.storage.node.field_landing_page_component',
      'field.storage.paragraph.field_paragraph_body',
      'field.storage.paragraph.field_paragraph_summary',
      'field.storage.node.field_event_details',
      'field.storage.paragraph.field_paragraph_location',
    ];
    foreach ($new_dependencies as $new_dependency) {
      if (!in_array($new_dependency, $dependencies)) {
        $dependencies[] = $new_dependency;
      }
    }
    $config->set('dependencies.config', $dependencies);

    $fields = $config->get('field_settings');
    $fields['field_event_details_event_locality'] = [
      'label' => 'Event Details » Location » The locality',
      'datasource_id' => 'entity:node',
      'property_path' => 'field_event_details:entity:field_paragraph_location:locality',
      'type' => 'text',
      'dependencies' => [
        'config' => [
          'field.storage.node.field_event_details',
          'field.storage.paragraph.field_paragraph_location',
        ],
        'module' => [
          'paragraphs',
        ],
      ],
    ];
    $fields['field_paragraph_body'] = [
      'label' => 'Content components » Paragraph » Body',
      'datasource_id' => 'entity:node',
      'property_path' => 'field_landing_page_component:entity:field_paragraph_body',
      'type' => 'text',
      'dependencies' => [
        'config' => [
          'field.storage.node.field_landing_page_component',
          'field.storage.paragraph.field_paragraph_body',
        ],
        'module' => [
          'paragraphs',
        ],
      ],
    ];
    $fields['field_paragraph_summary'] = [
      'label' => 'Content components » Paragraph » Summary',
      'datasource_id' => 'entity:node',
      'property_path' => 'field_landing_page_component:entity:field_paragraph_summary',
      'type' => 'text',
      'dependencies' => [
        'config' => [
          'field.storage.node.field_landing_page_component',
          'field.storage.paragraph.field_paragraph_summary',
        ],
        'module' => [
          'paragraphs',
        ],
      ],
    ];
    $config->set('field_settings', $fields);

    $filters = $config->get('processor_settings.html_filter.fields');
    $new_filters = [
      'field_paragraph_body',
      'field_paragraph_summary',
      'field_event_details_event_locality',
    ];
    foreach ($new_filters as $new_filter) {
      if (!in_array($new_filter, $filters)) {
        $filters[] = $new_filter;
      }
    }
    $config->set('processor_settings.html_filter.fields', $filters);

    $config->save();
  }

}
