<?php

namespace Drupal\tide_search\Plugin\search_api\backend;

use Drupal\Core\Form\FormStateInterface;
use Drupal\elasticsearch_connector\Plugin\search_api\backend\SearchApiElasticsearchBackend;

/**
 * Custom Elasticsearch Search API Backend definition.
 *
 * @SearchApiBackend(
 *   id = "elasticsearch",
 *   label = @Translation("Custom Elasticsearch"),
 *   description = @Translation("Custom Elasticsearch backend with number_of_shards setting.")
 * )
 */
class TideElasticsearchBackend extends SearchApiElasticsearchBackend {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $defaults = parent::defaultConfiguration();
    $defaults['number_of_shards'] = 1;
    return $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['number_of_shards'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of Shards'),
      '#default_value' => $this->configuration['number_of_shards'],
      '#min' => 1,
      '#description' => $this->t('Number of primary shards for the Elasticsearch index.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Let the parent handle everything first.
    parent::submitConfigurationForm($form, $form_state);

    // Saving your custom field.
    $this->configuration['number_of_shards'] = (int) $form_state->getValue('number_of_shards');
  }

  /**
   * Get the configured number of shards.
   *
   * @return int
   *   The number of shards for the Elasticsearch index.
   */
  public function getNumberOfShards() {
    return (int) $this->configuration['number_of_shards'];
  }

}
