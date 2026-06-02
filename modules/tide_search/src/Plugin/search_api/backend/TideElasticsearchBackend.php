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

    // Static variable to track logged errors.
    static $logged_errors = [];
    if (count($logged_errors) > 5000) {
      $logged_errors = [];
    }

    $successfully_indexed_ids = [];

    try {
      $params = $this->indexFactory->bulkIndex($index, $items);
      // Ensure params is valid before sending.
      if (empty($params)) {
        return [];
      }

      $response = $this->client->bulk($params);

      if (isset($response['items']) && is_array($response['items'])) {
        foreach ($response['items'] as $item_response) {
          $operation = key($item_response);
          $result = $item_response[$operation] ?? [];
          $id = $result['_id'] ?? NULL;
          $status = $result['status'] ?? 0;
          if ($status >= 200 && $status < 300) {
            if ($id) {
              $successfully_indexed_ids[] = $id;
            }
          }
          else {
            // Determine a static key for the error log. If ID is missing,
            // use a generic key to avoid flooding logs with "Unknown ID".
            $log_key = $id ? (string) $id : 'unknown_id_error';

            if (!isset($logged_errors[$log_key])) {
              $error_data = $result['error'] ?? 'Unknown error';

              if (is_array($error_data)) {
                $error_reason = $error_data['reason'] ?? 'Unknown reason';
                $caused_by = $error_data['caused_by']['reason'] ?? '';
                $error_type = $error_data['type'] ?? 'Unknown type';
              }
              else {
                // If it's a string, treat the whole thing as the reason.
                $error_reason = (string) $error_data;
                $caused_by = '';
                $error_type = 'String Error Format';
              }

              $this->logger->error('Failed to index item %id. Type: %type, Reason: %reason, Caused by: %caused_by', [
                '%id' => $id ?? 'NULL',
                '%type' => $error_type,
                '%reason' => $error_reason,
                '%caused_by' => $caused_by,
              ]);

              // Mark as logged.
              $logged_errors[$log_key] = TRUE;
            }
          }
        }
      }
    }
    catch (ElasticsearchException $e) {
      $this->logger->error('Elasticsearch error: @message', ['@message' => $e->getMessage()]);
      return [];
    }
    catch (\Exception $e) {
      $this->logger->error('Unexpected error during indexing: @message', ['@message' => $e->getMessage()]);
      return [];
    }

    return $successfully_indexed_ids;
  }

}
