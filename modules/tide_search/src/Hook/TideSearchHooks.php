<?php

namespace Drupal\tide_search\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\tide_publication\TidePublicationOperation;
use Drupal\tide_profile\TideProfileOperation;
use Drupal\tide_landing_page\TideLandingPageOperation;
use Drupal\tide_external_site_link\TideExternalSiteLinkOperation;
use Drupal\tide_event\TideEventOperation;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media\MediaInterface;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\IndexInterface;

/**
 * Hook implementations for the tide search module.
 */
class TideSearchHooks {

  /**
   * Implements hook_modules_installed().
   */
  #[Hook('modules_installed')]
  public function modulesInstalled(array $modules, bool $is_syncing): void {
    if ($is_syncing) {
      return;
    }

    $index = Index::load('node');
    if (!$index) {
      return;
    }

    $providers = [
      'tide_event' => [
        'field_event_date_start_value',
        TideEventOperation::class,
      ],
      'tide_external_site_link' => [
        'field_content_keywords',
        TideExternalSiteLinkOperation::class,
      ],
      'tide_landing_page' => [
        'field_landing_page_intro_text',
        TideLandingPageOperation::class,
      ],
      'tide_profile' => [
        'field_profile_intro_text',
        TideProfileOperation::class,
      ],
      'tide_publication' => [
        'field_publication_authors',
        TidePublicationOperation::class,
      ],
    ];

    $search_was_installed = in_array('tide_search', $modules, TRUE);
    foreach ($providers as $module => [$field_id, $operation]) {
      $provider_was_installed = in_array($module, $modules, TRUE);
      if (($search_was_installed || $provider_was_installed)
        && \Drupal::moduleHandler()->moduleExists($module)
        && !$index->getField($field_id)) {
        $operation::addFieldsToSearchApi();
      }
    }
  }

  /**
   * Implements hook_form_BASE_FORM_ID_alter().
   */
  #[Hook('form_node_form_alter')]
  public function formNodeFormAlter(&$form, FormStateInterface $form_state, $form_id) {
    if (!in_array($form_id, [
      'node_tide_search_listing_form',
      'node_tide_search_listing_edit_form',
      'node_tide_search_listing_quick_node_clone_form',
    ])) {
      return;
    }

    $field_config = 'node.tide_search_listing.field_custom_filters';
    $storage = \Drupal::entityTypeManager()->getStorage('field_config');

    if ($storage->load($field_config) !== NULL && $form_id === 'node_tide_search_listing_form') {
      $field_config_storage = $storage->load($field_config);
      $settings = $field_config_storage->getSettings();
      if (
        (
          is_array($settings['handler_settings'])
          && isset($settings['handler_settings']['target_bundles'])
          && !is_array($settings['handler_settings']['target_bundles'])
        )
        || empty($settings['handler_settings'])
      ) {
        $form['field_custom_filters']['#access'] = FALSE;
      }
    }
    $form['#process'][] = '_tide_search_form_node_form_process';
    $form['#after_build'][] = '_tide_search_form_node_form_after_build';

    // Prefill preset values.
    _tide_search_apply_preset_values($form, $form_state);
  }

  /**
   * Implements hook_paragraphs_type_widget_alter().
   */
  #[Hook('field_widget_single_element_paragraphs_form_alter')]
  public function fieldWidgetSingleElementParagraphsFormAlter(array &$element, FormStateInterface $form_state, array $context) {
    $current_user = \Drupal::currentUser();
    $admin_roles = ['administrator', 'site_admin'];
    $non_admin = !array_intersect($admin_roles, $current_user->getRoles());
    /** @var \Drupal\Core\Field\FieldItemListInterface $items */
    $items = $context['items'];
    $field_definition = $items->getFieldDefinition();
    $paragraph_field_name = $field_definition->getName();
    $widget_state = WidgetBase::getWidgetState($element['#field_parents'], $paragraph_field_name, $form_state);
    $paragraph = $widget_state['paragraphs'][$element['#delta']]['entity'];
    $paragraph_type = $paragraph ? $paragraph->bundle() : '';

    switch ($paragraph_type) {
      case 'searchable_fields':
        if ($non_admin) {
          if (!empty($element['subform']['field_placeholder'])) {
            $element['subform']['field_placeholder']['#access'] = FALSE;
          }
        }
        break;
    }
  }

  /**
   * Implements hook_search_api_index_items_alter().
   */
  #[Hook('search_api_index_items_alter')]
  public function searchApiIndexItemsAlter(IndexInterface $index, array &$items) {
    // Get any fields of type date and format it's value to align with RFC-3339.
    $index_fields = $index->getFields();
    $date_field_ids = [];
    foreach ($index_fields as $field_id => $index_field) {
      if ($index_field->getType() === 'date') {
        $date_field_ids[$field_id] = $field_id;
      }
    }
    foreach ($items as $item) {
      foreach ($date_field_ids as $field_id) {
        $date_field = $item->getField($field_id);
        if ($date_field) {
          $values = $date_field->getValues();
          foreach ($values as &$value) {
            $value = _tide_search_get_formatted_date($value);
          }
          unset($value);
          $date_field->setValues($values);
          $item->setField($field_id, $date_field);
        }
      }
    }
  }

  /**
   * Implements hook_admin_audit_trail_handlers().
   */
  #[Hook('admin_audit_trail_handlers')]
  public function adminAuditTrailHandlers() {
    // Page event log handler.
    $handlers = [];
    $handlers['tide_search'] = [
      'title' => t('Tide Search'),
    ];
    return $handlers;
  }

  /**
   * Implements hook_config_schema_info_alter().
   */
  #[Hook('config_schema_info_alter')]
  public function configSchemaInfoAlter(&$schema) {
    if (isset($schema['plugin.plugin_configuration.search_api_backend.elasticsearch']['mapping'])) {
      $schema['plugin.plugin_configuration.search_api_backend.elasticsearch']['mapping']['num_of_shards'] = [
        'type' => 'integer',
        'label' => 'The number of shards',
      ];
    }
  }

  /**
   * Implements hook_entity_presave().
   */
  #[Hook('entity_presave')]
  public function entityPresave(EntityInterface $entity) {
    if ($entity->getEntityTypeId() === 'node' && $entity->bundle() === 'tide_search_listing') {
      // Self service search listing.
      $current_user = \Drupal::currentUser();
      $admin_roles = ['administrator', 'site_admin'];
      $admin = array_intersect($admin_roles, $current_user->getRoles());
      // Only set field values for non admins.
      if (!$admin) {
        $result_type = $entity->get('field_listing_result_type')->value;

        if ($result_type) {
          // Mapping of result type to dependent fields.
          $mapping = [
            'list' => [
              'layout' => 'TideSearchResultsList',
              'results' => 'TideSearchResult',
            ],
            'grants' => [
              'layout' => 'TideSearchResultsList',
              'results' => 'TideGrantSearchResult',
            ],
            'card' => [
              'layout' => 'TideSearchResultsGrid',
              'results' => 'TideSearchResultCard',
            ],
          ];

          if (isset($mapping[$result_type])) {
            $map = $mapping[$result_type];

            // Lookup taxonomy terms by name.
            $layout_term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties([
              'name' => $map['layout'],
              'vid' => 'listing_layout_comp_taxonomy',
            ]);
            $results_term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties([
              'name' => $map['results'],
              'vid' => 'listing_results_comp_taxonomy',
            ]);

            // Get the first term ID found.
            $layout_tid = $layout_term ? reset($layout_term)->id() : NULL;
            $results_tid = $results_term ? reset($results_term)->id() : NULL;

            // Set entity reference fields.
            if ($layout_tid) {
              $entity->set('field_layout_component', ['target_id' => $layout_tid]);
            }
            if ($results_tid) {
              $entity->set('field_results_component', ['target_id' => $results_tid]);
            }
          }
        }
        // Set sort config JSON.
        $sort_by = $entity->get('field_sort_by_config_non_admin')->value;
        if ($sort_by) {
          // Mapping of sort_by values to JSON configuration.
          $sort_map = [
            'news'          => '[{ "field_news_date": "desc" }]',
            'publications'   => '[{ "field_publication_date": "desc" }]',
            'grants'        => '[{"_score": "desc"},{"field_node_dates_end_value": "asc"},{"title.keyword": "asc"}]',
            'events'         => '[{ "field_event_date_start_value": "asc" }]',
            'landing_page'  => '[{ "created": "desc" }]',
          ];

          if (isset($sort_map[$sort_by])) {
            $entity->set('field_custom_sort_configuration', $sort_map[$sort_by]);
          }
        }
      }
      // Clean user filters.
      _tide_search_clean_empty_paragraphs($entity, 'field_listing_user_filters', 'searchable_fields', 'field_field');

      // Clean global filters: listing_content_type.
      _tide_search_clean_empty_paragraphs($entity, 'field_listing_global_filters', 'listing_content_type', 'field_listing_global_contenttype');

      // Clean global filters: listing_site.
      _tide_search_clean_empty_paragraphs($entity, 'field_listing_global_filters', 'listing_site', 'field_listing_site_site');
    }
  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme($existing, $type, $theme, $path) {
    return [
      'node_add_list__non_admin' => [
        'template' => 'node-add-list--non-admin',
        'base hook' => 'node_add_list',
      ],
    ];
  }

  /**
   * Implements hook_theme_suggestions_HOOK().
   */
  #[Hook('theme_suggestions_node_add_list')]
  public function themeSuggestionsNodeAddList(array $variables) {
    $suggestions = [];
    $current_user = \Drupal::currentUser();
    $admin_roles = ['administrator', 'site_admin'];
    $admin = array_intersect($admin_roles, $current_user->getRoles());
    if (!$admin && $current_user->hasPermission('create tide_search_listing content')) {
      $suggestions[] = 'node_add_list__non_admin';
    }

    return $suggestions;
  }

  /**
   * Implements hook_entity_insert().
   */
  #[Hook('media_insert')]
  public function mediaInsert(MediaInterface $entity) {
    _tide_search_search_api_sync($entity, 'insert');
  }

  /**
   * Implements hook_entity_update().
   */
  #[Hook('media_update')]
  public function mediaUpdate(MediaInterface $entity) {
    _tide_search_search_api_sync($entity, 'update');
  }

  /**
   * Implements hook_entity_delete().
   */
  #[Hook('media_delete')]
  public function mediaDelete(MediaInterface $entity) {
    _tide_search_search_api_sync($entity, 'delete');
  }

}
