<?php

namespace Drupal\tide_site;

/**
 * Manages cache dependencies between nodes.
 */
class BreadcrumbCacheDependencyManager {
  const CACHE_KEY = 'node_breadcrumb_dependencies';

  /**
   * Cache Backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Constructor.
   */
  public function __construct() {
    $this->cache = \Drupal::cache();
  }

  /**
   * Gets the dependency graph.
   *
   * @return array
   *   Dependency relationship data.
   */
  public function getDependencyGraph() {
    $cache = $this->cache->get(self::CACHE_KEY);
    if ($cache && isset($cache->data)) {
      return $cache->data;
    }
    return [
      'forward' => [],
      'reverse' => [],
    ];
  }

  /**
   * Saves the dependency graph.
   *
   * @param array $graph
   *   Dependency relationship data.
   *
   * @return bool
   *   Whether the save was successful
   */
  public function saveDependencyGraph($graph) {
    try {
      $this->cache->set(self::CACHE_KEY, $graph);
      return TRUE;
    }
    catch (\Exception $e) {
      \Drupal::logger('tide_site')->error('Failed to save dependency graph: @message', ['@message' => $e->getMessage()]);
      return FALSE;
    }
  }

  /**
   * Internal helper to add a single dependency relationship to the graph.
   *
   * @param array &$graph
   *   Reference to the dependency graph.
   * @param int $nid
   *   Current node ID.
   * @param int $parent_nid
   *   Parent node ID.
   */
  private function addSingleDependency(array &$graph, $nid, $parent_nid) {
    if (!isset($graph['forward'][$parent_nid])) {
      $graph['forward'][$parent_nid] = [];
    }
    if (!in_array($nid, $graph['forward'][$parent_nid])) {
      $graph['forward'][$parent_nid][] = $nid;
    }

    if (!isset($graph['reverse'][$nid])) {
      $graph['reverse'][$nid] = [];
    }
    if (!in_array($parent_nid, $graph['reverse'][$nid])) {
      $graph['reverse'][$nid][] = $parent_nid;
    }
  }

  /**
   * Adds node dependency relationship(s)
   *
   * @param int|array $nid
   *   Current node ID or array of dependencies.
   * @param int|null $parent_nid
   *   Parent node ID (optional when $nid is array)
   *
   * @return bool
   *   Whether the operation was successful
   */
  public function addDependency($nid, $parent_nid = NULL) {
    try {
      $graph = $this->getDependencyGraph();

      if (is_array($nid)) {
        // Handle multiple dependencies.
        foreach ($nid as $child => $parent) {
          $this->addSingleDependency($graph, $child, $parent);
        }
      }
      else {
        // Handle single dependency.
        $this->addSingleDependency($graph, $nid, $parent_nid);
      }

      return $this->saveDependencyGraph($graph);
    }
    catch (\Exception $e) {
      \Drupal::logger('tide_site')->error('Failed to add dependency: @message', ['@message' => $e->getMessage()]);
      return FALSE;
    }
  }

  /**
   * Removes a node dependency relationship.
   *
   * @param int $nid
   *   Current node ID.
   * @param int $parent_nid
   *   Parent node ID.
   *
   * @return bool
   *   Whether the operation was successful
   */
  public function removeDependency($nid, $parent_nid) {
    try {
      $graph = $this->getDependencyGraph();
      if (isset($graph['forward'][$parent_nid])) {
        $index = array_search($nid, $graph['forward'][$parent_nid]);
        if ($index !== FALSE) {
          unset($graph['forward'][$parent_nid][$index]);
          $graph['forward'][$parent_nid] = array_values($graph['forward'][$parent_nid]);
        }
      }
      if (isset($graph['reverse'][$nid])) {
        $index = array_search($parent_nid, $graph['reverse'][$nid]);
        if ($index !== FALSE) {
          unset($graph['reverse'][$nid][$index]);
          $graph['reverse'][$nid] = array_values($graph['reverse'][$nid]);
        }
      }
      return $this->saveDependencyGraph($graph);
    }
    catch (\Exception $e) {
      \Drupal::logger('tide_site')->error('Failed to remove dependency: @message', ['@message' => $e->getMessage()]);
      return FALSE;
    }
  }

  /**
   * Gets all affected nodes (including parent chain and child chain)
   *
   * @param int $nid
   *   Current node ID.
   *
   * @return array
   *   All node IDs that need to be updated
   */
  public function getAllAffectedNodes($nid) {
    $graph = self::getDependencyGraph();
    $affected_nodes = [$nid];
    $visited = [$nid => TRUE];
    $queue = [$nid];
    while (!empty($queue)) {
      $current_nid = array_shift($queue);
      if (isset($graph['reverse'][$current_nid])) {
        foreach ($graph['reverse'][$current_nid] as $parent_nid) {
          if (!isset($visited[$parent_nid])) {
            $visited[$parent_nid] = TRUE;
            $affected_nodes[] = $parent_nid;
            $queue[] = $parent_nid;
          }
        }
      }
    }
    $queue = [$nid];
    while (!empty($queue)) {
      $current_nid = array_shift($queue);
      if (isset($graph['forward'][$current_nid])) {
        foreach ($graph['forward'][$current_nid] as $child_nid) {
          if (!isset($visited[$child_nid])) {
            $visited[$child_nid] = TRUE;
            $affected_nodes[] = $child_nid;
            $queue[] = $child_nid;
          }
        }
      }
    }
    return array_unique($affected_nodes);
  }

  /**
   * Gets the complete parent chain (breadcrumb chain) for a node.
   *
   * @param int $nid
   *   Node ID.
   *
   * @return array
   *   Array of parent node IDs ordered by hierarchy,
   *   from top level to current node
   */
  public function getParentChain($nid) {
    $graph = self::getDependencyGraph();
    $chain = [];
    $current_nid = $nid;
    $visited = [$nid => TRUE];
    while (isset($graph['reverse'][$current_nid]) && !empty($graph['reverse'][$current_nid])) {
      $parent_nid = reset($graph['reverse'][$current_nid]);
      if (isset($visited[$parent_nid])) {
        break;
      }
      $visited[$parent_nid] = TRUE;
      array_unshift($chain, $parent_nid);
      $current_nid = $parent_nid;
    }
    $chain[] = $nid;
    return $chain;
  }

  /**
   * Gets the complete children tree structure for a node.
   *
   * @param int $nid
   *   Node ID.
   *
   * @return array
   *   Tree structure of child nodes
   */
  public function getChildrenTree($nid) {
    $graph = self::getDependencyGraph();
    return self::buildChildrenTree($nid, $graph, []);
  }

  /**
   * Recursively builds the children tree.
   *
   * @param int $nid
   *   Current node ID.
   * @param array $graph
   *   Dependency graph.
   * @param array $visited
   *   Visited nodes (to prevent cycles)
   *
   * @return array
   *   Tree structure array
   */
  private static function buildChildrenTree($nid, $graph, $visited) {
    if (isset($visited[$nid])) {
      return [];
    }
    $visited[$nid] = TRUE;
    $tree = ['nid' => $nid, 'children' => []];
    if (isset($graph['forward'][$nid])) {
      foreach ($graph['forward'][$nid] as $child_nid) {
        $child_tree = self::buildChildrenTree($child_nid, $graph, $visited);
        if (!empty($child_tree)) {
          $tree['children'][] = $child_tree;
        }
      }
    }
    return $tree;
  }

  /**
   * Gets all child node IDs (flattened list)
   *
   * @param int $nid
   *   Node ID.
   *
   * @return array
   *   Array of all child node IDs (including children of children)
   */
  public static function getAllChildrenIds($nid) {
    $tree = self::getChildrenTree($nid);
    return self::flattenTree($tree);
  }

  /**
   * Flattens a tree structure into an array of IDs.
   *
   * @param array $tree
   *   Tree structure array.
   *
   * @return array
   *   Flattened array of IDs
   */
  private static function flattenTree($tree) {
    $ids = [];
    if (isset($tree['nid'])) {
      $ids[] = $tree['nid'];
    }
    if (!empty($tree['children'])) {
      foreach ($tree['children'] as $child) {
        $ids = array_merge($ids, self::flattenTree($child));
      }
    }
    return array_unique($ids);
  }

  /**
   * Removes a node and its dependencies.
   *
   * @param int $nid
   *   ID of the node to delete.
   *
   * @return bool
   *   Whether the operation was successful
   */
  public function removeNode($nid) {
    try {
      $graph = $this->getDependencyGraph();
      $parent_nid = isset($graph['reverse'][$nid]) ? reset($graph['reverse'][$nid]) : NULL;
      $children = $graph['forward'][$nid] ?? [];
      if ($parent_nid) {
        foreach ($children as $child_nid) {
          $this->removeDependency($child_nid, $nid);
          $this->addDependency($child_nid, $parent_nid);
        }
      }
      if (isset($graph['forward'][$nid])) {
        unset($graph['forward'][$nid]);
      }
      if (isset($graph['reverse'][$nid])) {
        unset($graph['reverse'][$nid]);
      }
      foreach ($graph['forward'] as &$forward_list) {
        $index = array_search($nid, $forward_list);
        if ($index !== FALSE) {
          unset($forward_list[$index]);
          $forward_list = array_values($forward_list);
        }
      }
      foreach ($graph['reverse'] as &$reverse_list) {
        $index = array_search($nid, $reverse_list);
        if ($index !== FALSE) {
          unset($reverse_list[$index]);
          $reverse_list = array_values($reverse_list);
        }
      }
      return $this->saveDependencyGraph($graph);
    }
    catch (\Exception $e) {
      \Drupal::logger('tide_site')->error('Failed to remove node: @message', ['@message' => $e->getMessage()]);
      return FALSE;
    }
  }

  /**
   * Converts an array of node IDs to cache tags.
   */
  public function nodeIdsToNodeCacheTags(array $input): array {
    if (empty($input)) {
      return [];
    }
    return array_map(fn($value) => "node:$value", $input);
  }

}
