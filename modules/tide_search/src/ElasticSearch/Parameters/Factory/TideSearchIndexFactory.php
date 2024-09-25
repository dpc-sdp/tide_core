<?php

namespace Drupal\tide_search\ElasticSearch\Parameters\Factory;

use Drupal\elasticsearch_connector\ElasticSearch\Parameters\Factory\IndexFactory;
use Drupal\elasticsearch_connector\Entity\Cluster;
use Drupal\elasticsearch_connector\Event\PrepareIndexEvent;
use Drupal\search_api\Entity\Server;
use Drupal\search_api\IndexInterface;

/**
 * Customised implementation for creation of Elasticsearch Indices.
 */
class TideSearchIndexFactory extends IndexFactory {

  const HASH_LENGTH = 32;

  /**
   * Override the build parameters required to create an index.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index being processed.
   *
   * @return array
   *   Return the config for creation of index.
   */
  public static function create(IndexInterface $index) {
    $indexName = IndexFactory::getIndexName($index);
    $filteredIndexName = str_replace('--', '-', $indexName);
    $aliasPrefix = 'search-' . (\Drupal::request()->server->get('SEARCH_HASH') ?: '') . '-';
    $aliasName = $aliasPrefix . str_replace('_', '-', $filteredIndexName) . '-alias';

    $indexConfig = [
      'index' => $indexName,
      'body' => [
        'aliases' => [
          $aliasName => [
            'is_hidden' => FALSE,
          ],
        ],
        'settings' => [
          'number_of_shards' => $index->getOption('number_of_shards', 5),
          'number_of_replicas' => $index->getOption('number_of_replicas', 1),
          'analysis' => [
            "filter" => [
              "front_ngram" => [
                "type" => "edge_ngram",
                "min_gram" => "1",
                "max_gram" => "12",
              ],
            ],
            "analyzer" => [
              "i_prefix" => [
                "filter" => [
                  "cjk_width",
                  "lowercase",
                  "asciifolding",
                  "front_ngram",
                ],
                "tokenizer" => "standard",
              ],
              "q_prefix" => [
                "filter" => [
                  "cjk_width",
                  "lowercase",
                  "asciifolding",
                ],
                "tokenizer" => "standard",
              ],
            ],
          ],
        ],
        'mappings' => [
          "properties" => [
            "title" => [
              "type" => "text",
              "fields" => [
                "keyword" => [
                  "type" => "keyword",
                  "ignore_above" => 256,
                ],
                "prefix" => [
                  "type" => "text",
                  "index_options" => "docs",
                  "analyzer" => "i_prefix",
                  "search_analyzer" => "q_prefix",
                ],
              ],
            ],
            "summary_processed" => [
              "type" => "text",
              "fields" => [
                "keyword" => [
                  "type" => "keyword",
                  "ignore_above" => 256,
                ],
                "prefix" => [
                  "type" => "text",
                  "index_options" => "docs",
                  "analyzer" => "i_prefix",
                  "search_analyzer" => "q_prefix",
                ],
              ],
            ],
          ],
        ],
      ],
    ];

    // Allow other modules to alter index config before we create it.
    $dispatcher = \Drupal::service('event_dispatcher');
    $prepareIndexEvent = new PrepareIndexEvent($indexConfig, $indexName);
    $event = $dispatcher->dispatch($prepareIndexEvent, PrepareIndexEvent::PREPARE_INDEX);
    $indexConfig = $event->getIndexConfig();
    return $indexConfig;
  }

  /**
   * Overrides the elasticsearch_connector bulk delete params.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The Search API Index.
   * @param array $ids
   *   The ids of the entities to delete.
   *
   * @return array
   *   The query parameters.
   */
  public static function bulkDelete(IndexInterface $index, array $ids) {
    $params = IndexFactory::index($index, TRUE);

    // This convoluted path to the host domain is due to
    // https://www.drupal.org/project/search_api/issues/2976339 not populating
    // `search_api.server.server_id` config with the correct values.
    $cluster_id = Server::load($index->getServerId())->getBackend()->getCluster();
    $host = parse_url(Cluster::load($cluster_id)->url, PHP_URL_HOST);
    $hash = strstr($host, '.', TRUE);

    if (isset($hash) && strlen($hash) === self::HASH_LENGTH) {
      $params['index'] = $hash . '--' . $params['index'];
    }

    foreach ($ids as $id) {
      $params['body'][] = [
        'delete' => [
          '_index' => $params['index'],
          '_id' => $id,
        ],
      ];
    }

    return $params;
  }

  /**
   * Build parameters required to create an index mapping.
   *
   * @todo We need also:
   * $params['index'] - (Required)
   * ['type'] - The name of the document type
   * ['timeout'] - (time) Explicit operation timeout.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   Index object.
   *
   * @return array
   *   Parameters required to create an index mapping.
   */
  public static function mapping(IndexInterface $index) {
    $mapping = parent::mapping($index);
    $filtered = [];
    foreach ($mapping['body']['properties'] as $property_key => $property) {
      if (isset($property['boost'])) {
        unset($property['boost']);
      }
      $filtered[$property_key] = $property;
    };
    $mapping['body']['properties'] = $filtered;

    return $mapping;
  }

}
