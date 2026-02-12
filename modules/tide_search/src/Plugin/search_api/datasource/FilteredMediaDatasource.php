<?php

namespace Drupal\tide_search\Plugin\search_api\datasource;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\search_api\Datasource\DatasourcePluginBase;

/**
 * Indexes Media entities based on a configurable boolean field and bundles.
 *
 * @SearchApiDatasource(
 * id = "filtered_media",
 * label = @Translation("Filtered Media"),
 * description = @Translation("Indexes media entities based on a configurable filter field and selected bundles."),
 * entity_type_id = "media"
 * )
 */
class FilteredMediaDatasource extends DatasourcePluginBase implements PluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'indexing_field' => '',
      'bundles' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    // Fetch all available Media Types (bundles).
    $media_types = \Drupal::entityTypeManager()->getStorage('media_type')->loadMultiple();
    $options = [];
    foreach ($media_types as $type) {
      $options[$type->id()] = $type->label();
    }

    $form['bundles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Media Bundles'),
      '#description' => $this->t('Select the media bundles that should be eligible for indexing.'),
      '#options' => $options,
      '#default_value' => $config['bundles'] ?: [],
      '#required' => TRUE,
    ];

    // Dynamically find all Boolean fields on Media.
    $field_manager = \Drupal::service('entity_field.manager');
    $storage_definitions = $field_manager->getFieldStorageDefinitions('media');

    $field_options = [];
    foreach ($storage_definitions as $field_name => $storage_definition) {
      // Only boolean fields.
      if ($storage_definition->getType() === 'boolean') {
        $field_options[$field_name] = $this->t('@label (@name)', [
          '@label' => $storage_definition->getLabel() ?: $field_name,
          '@name' => $field_name,
        ]);
      }
    }

    asort($field_options);

    $form['indexing_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Filtering Field'),
      '#description' => $this->t('Select the boolean field that controls whether an item is indexed.'),
      '#options' => $field_options,
      '#default_value' => $config['indexing_field'],
      '#empty_option' => $this->t('- Select Field -'),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Basic validation is handled by #required => TRUE.
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Filter the checkboxes array to remove unselected items (value 0).
    $values = $form_state->getValues();
    $values['bundles'] = array_values(array_filter($values['bundles']));

    $this->setConfiguration($values);
  }

  /**
   * {@inheritdoc}
   */
  public function getItemIds($page = NULL) {
    $config = $this->getConfiguration();

    if (empty($config['bundles']) || empty($config['indexing_field'])) {
      return NULL;
    }

    $limit = 50;
    $database = \Drupal::database();

    // Construct the table name based on the dynamic field name.
    $table_name = 'media__' . $config['indexing_field'];
    $column_name = $config['indexing_field'] . '_value';

    if (!$database->schema()->tableExists($table_name)) {
      \Drupal::logger('tide_search')->error(
        'Search API Indexing failed: The table %table does not exist. Please check the "Filtering Field" setting in your datasource configuration.',
        ['%table' => $table_name]
      );
      return NULL;
    }

    try {
      $query = $database->select($table_name, 't');
      $query->fields('t', ['entity_id']);

      // Apply filters based on configuration.
      $query->condition('t.bundle', $config['bundles'], 'IN');
      $query->condition('t.' . $column_name, 1);
      $query->orderBy('t.entity_id', 'ASC');

      if ($page !== NULL) {
        $query->range($page * $limit, $limit);
      }

      $ids = $query->execute()->fetchCol();

      if (empty($ids) && $page === 0) {
        \Drupal::logger('tide_search')->notice('Filtered Media datasource returned 0 items for bundles: %bundles using field: %field.',
          [
                     '%bundles' => implode(', ', $config['bundles']), 
                     '%field' => $config['indexing_field'],
                   ]);
      }

      return $ids ? array_values(array_map('strval', $ids)) : NULL;
    }
    catch (\Exception $e) {
      \Drupal::logger('tide_search')->error(
        'Database error during Filtered Media indexing: @message',
        ['@message' => $e->getMessage()]
      );
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(array $ids) {
    try {
      $entities = \Drupal::entityTypeManager()
        ->getStorage('media')
        ->loadMultiple($ids);

      $items = [];
      foreach ($entities as $id => $entity) {
        $items[$id] = $entity->getTypedData();
      }
      // To save memory during large re-indexes.
      // Clear the static entity cache.
      if (count($ids) > 20) {
        \Drupal::entityTypeManager()->getStorage('media')->resetCache($ids);
      }
      return $items;
    }
    catch (\Exception $e) {
      \Drupal::logger('tide_search')->error(
        'Failed to load media entities for indexing: @message',
        ['@message' => $e->getMessage()]
      );
      return [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getItemId($item) {
    return $item->getValue()->id();
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    $config = $this->getConfiguration();
    // Use the first selected bundle to determine property definitions.
    // Or return all media fields.
    $bundle = !empty($config['bundles']) ? reset($config['bundles']) : 'document';
    return \Drupal::service('entity_field.manager')->getFieldDefinitions('media', $bundle);
  }

}
