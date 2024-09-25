<?php

namespace Drupal\tide_search\Commands;

use Drupal\search_api\Utility\CommandHelper;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class TideSearchCommands extends DrushCommands {

  /**
   * Audit nodeids that needs to be published/indexed based on search index.
   *
   * @usage drush tide-search-audit-nodes
   *   Update the domains on the site taxonomy based on an environment variable.
   *
   * @command tide:search-audit-nodes
   * @aliases tide-san,tide-search-audit-nodes
   *
   * @throws \Exception
   */
  public function auditSearchContent($indexId) {
    $message = '';
    try {
      $nids_in_search_index = self::getNidsFromSearchIndex($indexId);
      $nids_of_published_content = self::getPublishedNodeIds();
      // Content that is published but NOT searchable in the index.
      $not_in_index = array_diff($nids_of_published_content, $nids_in_search_index);
      $ops = "Not in index";
      $description = "published but not indexed in the search";
      if (!empty($not_in_index)) {
        $not_in_index = implode(", ", $not_in_index);
        self::auditIntoLog($not_in_index, $ops, $description);
        $message = $message . "The following nodes are published but not indexed in the search - " . $not_in_index . "\n";
      }
      // Content that is in the search index but is NOT published.
      $not_published = array_diff($nids_in_search_index, $nids_of_published_content);
      $ops = "Not published";
      $description = "in search index but not published";
      if (!empty($not_published)) {
        $not_published = implode(", ", $not_published);
        self::auditIntoLog($not_published, $ops, $description);
        $message = $message . "The following nodes are in search index but not published - " . $not_published . "\n";
      }
      $message = ($not_published || $not_in_index) ? $message . "Logged in audit trail as well." : "Nothing to log.";
      return $message;
    }
    catch (ConsoleException $exception) {
      throw new \Exception($exception->getMessage());
    }
  }

  /**
   * Helper to log into the audit trail.
   */
  public static function auditIntoLog($nids, $ops, $description) {
    $log = [
      "type" => "tide_search",
      "operation" => $ops,
      "description" => t("The following nodes are %des - %nids", [
        "%nids" => $nids,
        "%des" => $description,
      ]),
      "ref_numeric" => 1,
      "ref_char" => "drush sapi-nsc node results",
    ];
    // Add the log to the "admin_audit_trail" table.
    if (function_exists("admin_audit_trail_insert")) {
      admin_audit_trail_insert($log);
    }
  }

  /**
   * Helper to get node Ids from search index.
   */
  public static function getNidsFromSearchIndex($indexId) {
    $command_helper = new CommandHelper(\Drupal::entityTypeManager(), \Drupal::moduleHandler(), \Drupal::service('event_dispatcher'), 'dt');
    $es_nids = [];
    $indexes = $command_helper->loadIndexes([$indexId]);
    if (empty($indexes[$indexId])) {
      throw new ConsoleException(t('@index was not found'));
    }
    $total = $indexes[$indexId]->getTrackerInstance()->getTotalItemsCount();
    $no_of_batches = ceil($total / 1000);
    // If the result set is more than 1000 then run it in batch.
    if ($no_of_batches > 1) {
      $nid_starting_point = 0;
      for ($i = 1; $i <= $no_of_batches; $i++) {
        try {
          $query = $indexes[$indexId]->query();
          $query->range(0, 1000);
          $query->sort('nid');
          $query->addCondition('nid', $nid_starting_point, '>');
          $results = $query->execute();
        }
        catch (SearchApiException $e) {
          throw new ConsoleException($e->getMessage(), 0, $e);
        }
        foreach ($results->getAllExtraData()['elasticsearch_response']['hits']['hits'] as $item) {
          $es_nids[] = $item['_source']['nid'][0];
        }
        // The last nid will be the starting point for next batch.
        $nid_starting_point = end($es_nids);
      }
      // Will remove if any duplicate values.
      $es_nids = array_unique($es_nids);
      return $es_nids;
    }
    // Run in single request.
    try {
      $query = $indexes[$indexId]->query();
      $query->range(0, $total);
      $results = $query->execute();
    }
    catch (SearchApiException $e) {
      throw new ConsoleException($e->getMessage(), 0, $e);
    }
    foreach ($results->getAllExtraData()['elasticsearch_response']['hits']['hits'] as $item) {
      $es_nids[] = $item['_source']['nid'][0];
    }
    $es_nids = array_unique($es_nids);
    return $es_nids;
  }

  /**
   * Helper to get published node ids.
   */
  public static function getPublishedNodeIds() {
    $pointer = 0;
    $published_nids = [];
    // Running in batches to avoid running out of memory error.
    do {
      $nids = \Drupal::entityTypeManager()
        ->getStorage('node')
        ->getQuery()
        ->condition('type', 'alert', '!=')
        ->condition('status', 1)
        ->sort('nid')
        ->condition('nid', $pointer, '>')
        ->range(0, 100)
        ->execute();
      $published_nids = array_merge($published_nids, $nids);
      $pointer = end($nids);
    } while ($nids);

    return $published_nids;
  }

}
