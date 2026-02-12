<?php

namespace Drupal\tide_search\Plugin\search_api\datasource;

use Drupal\search_api\Datasource\DatasourcePluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;

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

    $form['indexing_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Filtering Field'),
      '#description' => $this->t('The machine name of the boolean field (e.g., field_is_indexed).'),
      '#default_value' => $config['indexing_field'],
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

    return $ids ? array_values(array_map('strval', $ids)) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(array $ids) {
    $entities = \Drupal::entityTypeManager()
      ->getStorage('media')
      ->loadMultiple($ids);

    $items = [];
    foreach ($entities as $id => $entity) {
      $items[$id] = $entity->getTypedData();
    }
    return $items;
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
