<?php

namespace Drupal\tide_search\Plugin\search_api\backend;

use Drupal\Core\Form\FormStateInterface;
use Drupal\elasticsearch_connector\Plugin\search_api\backend\SearchApiElasticsearchBackend;
use Drupal\search_api\IndexInterface;
use Elasticsearch\Common\Exceptions\ElasticsearchException;

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

  /**
   * {@inheritdoc}
   */
  public function indexItems(IndexInterface $index, array $items) {
    if (empty($items)) {
      return [];
    }

    // Array to store ids of items that are successfully indexed.
    $successfully_indexed_ids = [];

    try {
      $response = $this->client->bulk(
        $this->indexFactory->bulkIndex($index, $items)
      );

      // Process the response to separate successes from failures.
      // The bulk API returns 200 OK even if some items failed processing.
      if (isset($response['items'])) {
        foreach ($response['items'] as $item_response) {
          $operation = key($item_response);
          $result = $item_response[$operation];

          // Extract the document ID.
          $id = $result['_id'] ?? NULL;

          if (isset($result['status']) && $result['status'] >= 200 && $result['status'] < 300) {
            if ($id) {
              $successfully_indexed_ids[] = $id;
            }
          }
          else {
            // Log specific errors for individual items
            // without failing the entire batch.
            $error_reason = $result['error']['reason'] ?? 'Unknown error';
            $caused_by = $result['error']['caused_by']['reason'] ?? '';
            $error_type = $result['error']['type'] ?? 'Unknown type';

            $this->logger->error('Failed to index item %id. Type: %type, Reason: %reason, Caused by: %caused_by', [
              '%id' => $id ?? 'unknown',
              '%type' => $error_type,
              '%reason' => $error_reason,
              '%caused_by' => $caused_by,
            ]);
          }
        }
      }
    }
    catch (ElasticsearchException $e) {
      $this->logger->error('Elasticsearch error: @message', ['@message' => $e->getMessage()]);
      return [];
    }
    catch (\Exception $e) {
      // Catch any other unexpected exceptions.
      $this->logger->error('Unexpected error during indexing: @message', ['@message' => $e->getMessage()]);
      return [];
    }

    return $successfully_indexed_ids;
  }

}
