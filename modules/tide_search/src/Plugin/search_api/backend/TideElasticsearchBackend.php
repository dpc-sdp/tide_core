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
    $values = $form_state->getValues();

    // Handle synonyms as before.
    $values['advanced']['synonyms'] = explode(\PHP_EOL, $form_state->getValue([
      'advanced',
      'synonyms',
    ], ''));

    // Save full configuration.
    $this->setConfiguration($values);

    // Save connector config.
    $this->configuration['connector'] = $form_state->getValue('connector');
    $connector = $this->getConnector();
    if ($connector instanceof PluginFormInterface) {
      $connector_form_state = SubformState::createForSubform($form['connector_config'], $form, $form_state);
      $connector->submitConfigurationForm($form['connector_config'], $connector_form_state);
      // Overwrite the form values with type casted values.
      $form_state->setValue('connector_config', $connector->getConfiguration());
    }

    // 👇 Save your custom field explicitly.
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
