<?php

namespace Drupal\tide_breadcrumbs;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\TermInterface;

/**
 * Service to build the full breadcrumb trail for a node.
 *
 * This service manages complex multi-site breadcrumbs by chaining trails across
 * different menu structures (Primary Site vs Section Sites) and discovering
 * taxonomy ancestors to ensure a complete hierarchical path.
 */
class TideBreadcrumbBuilder {

  /**
   * The menu link tree service.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuTree;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Discovered cache tags during the build process.
   *
   * @var array
   */
  protected $discoveredTags = [];

  /**
   * Static cache for the calculated trail.
   *
   * @var array
   */
  protected $staticTrail = [];

  /**
   * Static cache for ordered section terms per node.
   *
   * @var array
   */
  protected $staticSectionTerms = [];

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new TideBreadcrumbBuilder object.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menu_tree
   *   The menu link tree service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(
    MenuLinkTreeInterface $menu_tree,
    EntityTypeManagerInterface $entity_type_manager,
    Connection $database
  ) {
    $this->menuTree = $menu_tree;
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
  }

  /**
   * Main entry point: Chained Section Logic with Taxonomy Parent Discovery.
   *
   * Builds a full trail starting from the Primary Site home, relaying through
   * all relevant Section Site homes, and finally finding the node's position
   * within its specific menu.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node for which to build the trail.
   *
   * @return array
   *   An array of breadcrumb items, each containing 'title' and 'url'.
   */
  public function buildFullTrail(NodeInterface $node) {
    // Check static cache first. Using NID as key.
    $nid = $node->id() ?: 'new';
    if (isset($this->staticTrail[$nid])) {
      return $this->staticTrail[$nid];
    }

    // Initialize tags with the target node.
    $this->discoveredTags = $node->getCacheTags();

    $nodeTitle = $node->getTitle() ?: 'Title not found';
    // If the node is being created or cloned, return a simplified trail.
    if ($node->isNew()) {
      $primary_site_term = $node->get('field_node_primary_site')->entity;

      // Default to site root if no primary site is selected yet.
      $home_crumb = ['title' => 'Home', 'url' => '/'];

      if ($primary_site_term instanceof TermInterface) {
        $home_crumb = $this->getPrimaryHomeLink($primary_site_term);
      }

      $this->staticTrail['new'] = [$home_crumb];
      return $this->staticTrail['new'];
    }

    $targetNid = (string) $node->id();

    // Get all relevant section terms (including ancestors up to Level 2).
    $section_terms = $this->getOrderedSectionTerms($node);
    $primary_site_term = $node->get('field_node_primary_site')->entity;

    $chained_trail = [];
    $found_node_in_menu = FALSE;

    if ($primary_site_term instanceof TermInterface) {
      $primary_menu_id = $primary_site_term->get('field_site_main_menu')->target_id;

      // Start with Absolute Primary Home.
      $chained_trail[] = $this->getPrimaryHomeLink($primary_site_term);

      // THE RELAY: Chain from Parent -> Child -> Grandchild.
      foreach ($section_terms as $term) {
        $menu_id = $term->get('field_site_main_menu')->target_id;

        if (!$menu_id) {
          continue;
        }

        // Search current section menu for the node.
        $node_trail_in_this_menu = $this->getTrailFromMenu($menu_id, $targetNid, $primary_menu_id);

        if ($node_trail_in_this_menu) {
          // If this is the start of the chain, bridge from Primary Menu.
          if (count($chained_trail) === 1 && $primary_menu_id) {
            $bridge = $this->getTrailByUrl($primary_menu_id, $node_trail_in_this_menu[0]['url'], $primary_menu_id);
            if ($bridge) {
              $chained_trail = array_merge($chained_trail, $bridge);
            }
          }

          $chained_trail = array_merge($chained_trail, $node_trail_in_this_menu);
          $found_node_in_menu = TRUE;
          break;
        }
        else {
          // Node not here, add Section Home and continue relay.
          $section_root = $this->getMenuRootByWeight($menu_id, $primary_menu_id);
          if ($section_root) {
            if (count($chained_trail) === 1 && $primary_menu_id) {
              $bridge = $this->getTrailByUrl($primary_menu_id, $section_root['url'], $primary_menu_id);
              if ($bridge) {
                $chained_trail = array_merge($chained_trail, $bridge);
              }
            }
            $chained_trail[] = $section_root;
          }
        }
      }

      // FALLBACK: Node not found in any Section Menu.
      if (!$found_node_in_menu) {
        if (count($chained_trail) === 1 && $primary_menu_id) {
          // When falling back to the primary menu, skip adding the root crumb.
          // This prevents showing the 1st menu item directly after "Home".
          $primary_search = $this->getTrailFromMenu($primary_menu_id, $targetNid, $primary_menu_id, TRUE);
          if ($primary_search) {
            $chained_trail = array_merge($chained_trail, $primary_search);
            $found_node_in_menu = TRUE;
          }
        }

        if (!$found_node_in_menu) {
          $chained_trail[] = ['title' => $nodeTitle, 'url' => $node->toUrl()->toString()];
        }
      }
    }

    // Deduplicate URLs.
    $chained_trail = $this->deduplicateTrail($chained_trail);

    // Remove the current page item from the breadcrumb trail.
    if (!empty($chained_trail)) {
      // Check if the current page is homepage.
      $count = count($chained_trail);
      $primaryHomeLink = $this->getPrimaryHomeLink($primary_site_term);
      if ($count > 1 || ($count === 1 && $chained_trail[0]['url'] !== $primaryHomeLink['url'])) {
        $last_item = end($chained_trail);
        $nodeUrl = rtrim($node->toUrl()->toString(), '/');
        $lastItemUrl = rtrim($last_item['url'], '/');

        // Check if the last item is the current node by URL or Title.
        if ($lastItemUrl === $nodeUrl || $last_item['title'] === $nodeTitle) {
          array_pop($chained_trail);
        }
      }
    }

    $this->staticTrail[$targetNid] = $chained_trail;
    return $chained_trail;
  }

  /**
   * Crawls taxonomy to find all parents between tagged term and Primary Site.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node containing site taxonomy references.
   *
   * @return \Drupal\taxonomy\TermInterface[]
   *   An array of ordered taxonomy terms from shallowest to deepest.
   */
  protected function getOrderedSectionTerms(NodeInterface $node) {
    $nid = $node->id() ?: 'new';
    if (isset($this->staticSectionTerms[$nid])) {
      return $this->staticSectionTerms[$nid];
    }

    if (!$node->hasField('field_node_primary_site') || $node->get('field_node_primary_site')->isEmpty()) {
      return [];
    }

    $primary_id = $node->get('field_node_primary_site')->target_id;
    $field_items = $node->get('field_node_site');
    $direct_terms = ($field_items instanceof EntityReferenceFieldItemListInterface) ? $field_items->referencedEntities() : [];

    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $all_relevant_terms = [];

    foreach ($direct_terms as $term) {
      // If the section term is the same as the primary site, skip it.
      // Prevents builder from treating Primary Site as its own Section.
      if ($term->id() == $primary_id) {
        continue;
      }

      // Add the term itself to discovery.
      $this->discoveredTags = array_merge($this->discoveredTags, $term->getCacheTags());

      // Load all ancestors.
      $ancestors = $term_storage->loadAllParents($term->id());
      foreach ($ancestors as $ancestor) {
        // Exclude Level 1 (Primary Site) but keep everything else (Level 2+).
        if ($ancestor->id() != $primary_id) {
          $all_relevant_terms[$ancestor->id()] = $ancestor;
          // Add ancestor terms to discovery.
          $this->discoveredTags = array_merge($this->discoveredTags, $ancestor->getCacheTags());
        }
      }
    }

    // Sort terms by depth so Parent comes before Grandchild.
    usort($all_relevant_terms, function ($a, $b) use ($term_storage) {
      $depth_a = count($term_storage->loadAllParents($a->id()));
      $depth_b = count($term_storage->loadAllParents($b->id()));
      return $depth_a <=> $depth_b;
    });

    $this->staticSectionTerms[$nid] = $all_relevant_terms;
    return $all_relevant_terms;
  }

  /**
   * Finds the root of a menu by weight, using title resolution logic.
   *
   * @param string $menu_name
   *   The machine name of the menu.
   * @param string|null $primary_menu_id
   *   The machine name of the primary menu for title logic.
   *
   * @return array|null
   *   The root crumb array or NULL if not found.
   */
  protected function getMenuRootByWeight($menu_name, $primary_menu_id = NULL) {
    $parameters = new MenuTreeParameters();
    $parameters->onlyEnabledLinks();
    $tree = $this->menuTree->load($menu_name, $parameters);

    $root_element = NULL;
    $min_weight = NULL;

    foreach ($tree as $element) {
      $weight = $element->link->getWeight();
      if ($min_weight === NULL || $weight < $min_weight) {
        $min_weight = $weight;
        $root_element = $element;
      }
    }

    if ($root_element) {
      $title = $this->resolveLinkTitle($root_element->link, $menu_name, $primary_menu_id);
      return [
        'title' => $title['title'] ?? $root_element->link->getTitle(),
        'url' => $root_element->link->getUrlObject()->toString(),
      ];
    }
    return NULL;
  }

  /**
   * Generates a trail from a specific menu for a given node.
   *
   * @param string $menu_name
   *   The machine name of the menu to search.
   * @param string $targetNid
   *   The node ID to search for.
   * @param string|null $primary_menu_id
   *   The machine name of the primary menu.
   * @param bool $skip_root
   *   Whether to skip prepending the menu root/first item.
   *
   * @return array|null
   *   The trail array or NULL if the node is not in the menu.
   */
  protected function getTrailFromMenu($menu_name, $targetNid, $primary_menu_id = NULL, $skip_root = FALSE) {
    $parameters = new MenuTreeParameters();
    $tree = $this->menuTree->load($menu_name, $parameters);
    if (empty($tree)) {
      return NULL;
    }

    $trail = $this->searchTree($tree, $targetNid, 'nid', [], $menu_name, $primary_menu_id);

    if ($trail && !$skip_root) {
      $root_crumb = $this->getMenuRootByWeight($menu_name, $primary_menu_id);
      if ($root_crumb && $trail[0]['url'] !== $root_crumb['url']) {
        array_unshift($trail, $root_crumb);
      }
    }
    return $trail;
  }

  /**
   * Recursively searches a menu tree for a target NID or URL.
   *
   * @param array $tree
   *   The menu tree array.
   * @param string $target
   *   The NID or URL to search for.
   * @param string $mode
   *   Either 'nid' or 'url'.
   * @param array $trail
   *   The accumulated trail.
   * @param string|null $current_menu_id
   *   The ID of the menu currently being searched.
   * @param string|null $primary_menu_id
   *   The ID of the primary site menu.
   *
   * @return array|null
   *   The found trail or NULL.
   */
  protected function searchTree(array $tree, $target, $mode = 'nid', $trail = [], $current_menu_id = NULL, $primary_menu_id = NULL) {
    foreach ($tree as $element) {
      $link = $element->link;
      if (!$link->isEnabled()) {
        continue;
      }

      try {
        $currentUrl = $link->getUrlObject()->toString();
        $title_data = $this->resolveLinkTitle($link, $current_menu_id, $primary_menu_id);
        $title = $title_data['title'];
      }
      catch (\Exception $e) {
        continue;
      }

      $currentTrail = $trail;
      $currentTrail[] = ['title' => $title, 'url' => $currentUrl];

      $matched = FALSE;
      if ($mode === 'nid') {
        $urlObj = $link->getUrlObject();
        if ($urlObj->isRouted() && $urlObj->getRouteName() === 'entity.node.canonical') {
          $params = $urlObj->getRouteParameters();
          if (isset($params['node']) && (string) $params['node'] === (string) $target) {
            $matched = TRUE;
          }
        }
      }
      elseif ($mode === 'url') {
        $normTarget = rtrim(parse_url($target, PHP_URL_PATH), '/');
        $normCurrent = rtrim(parse_url($currentUrl, PHP_URL_PATH), '/');
        if ($normTarget === $normCurrent && !empty($normTarget)) {
          $matched = TRUE;
        }
      }

      if ($matched) {
        return $currentTrail;
      }

      if (!empty($element->subtree)) {
        if ($result = $this->searchTree($element->subtree, $target, $mode, $currentTrail, $current_menu_id, $primary_menu_id)) {
          return $result;
        }
      }
    }
    return NULL;
  }

  /**
   * Ensures Primary Menu items use Menu Title; Section items use Node Title.
   *
   * @param \Drupal\Core\Menu\MenuLinkInterface $link
   *   The menu link plugin.
   * @param string|null $current_menu_id
   *   The menu being searched.
   * @param string|null $primary_menu_id
   *   The primary site menu ID.
   *
   * @return string
   *   The resolved title.
   */
  protected function resolveLinkTitle($link, $current_menu_id, $primary_menu_id): array {
    // Primary menu items must use the menu link title.
    if ($current_menu_id === $primary_menu_id) {
      return [
        'title' => $link->getTitle(),
        'attributes' => [],
      ];
    }

    // Section items should try to use the node title.
    $urlObj = $link->getUrlObject();
    if ($urlObj->isRouted() && $urlObj->getRouteName() === 'entity.node.canonical') {
      $params = $urlObj->getRouteParameters();
      $nid = $params['node'] ?? NULL;

      if ($nid) {
        /** @var \Drupal\Core\Language\LanguageManagerInterface $language_manager */
        $lang_code = \Drupal::languageManager()->getCurrentLanguage()->getId();

        // Use direct db query to avoid triggering hook_node_storage_load().
        // This prevents the infinite loop.
        $query = $this->database->select('node_field_data', 'nfd');
        $query->fields('nfd', ['title']);
        $query->condition('nfd.nid', $nid);
        $query->condition('nfd.langcode', $lang_code);
        $node_title = $query->execute()->fetchField();

        // Fallback to default translation.
        if (!$node_title) {
          $node_title = $this->database->select('node_field_data', 'nfd')
            ->fields('nfd', ['title'])
            ->condition('nfd.nid', $nid)
            ->condition('nfd.default_langcode', 1)
            ->execute()->fetchField();
        }

        if ($node_title) {
          $this->discoveredTags[] = "node:$nid";

          return [
            'title' => $node_title,
            'attributes' => ['data-breadcrumb-source' => 'node'],
          ];
        }
      }
    }

    // Fallback to Menu Link Title.
    return [
      'title' => $link->getTitle(),
      'attributes' => [],
    ];
  }

  /**
   * Gets a trail based on a specific URL within a menu.
   *
   * @param string $menu_name
   *   The menu machine name.
   * @param string $url
   *   The URL to search for.
   * @param string|null $primary_menu_id
   *   The primary menu machine name.
   *
   * @return array|null
   *   The trail or NULL.
   */
  protected function getTrailByUrl($menu_name, $url, $primary_menu_id = NULL) {
    $parameters = new MenuTreeParameters();
    $tree = $this->menuTree->load($menu_name, $parameters);
    return $this->searchTree($tree, $url, 'url', [], $menu_name, $primary_menu_id);
  }

  /**
   * Generates the starting crumb for the primary site home.
   *
   * @param \Drupal\taxonomy\TermInterface $site_term
   *   The primary site taxonomy term.
   *
   * @return array
   *   The home crumb array with the title forced to 'Home'.
   */
  protected function getPrimaryHomeLink(TermInterface $site_term) {
    // Add taxonomy term tags to discovery.
    $this->discoveredTags = array_merge($this->discoveredTags, $site_term->getCacheTags());

    $url = '/';
    if (!$site_term->get('field_site_homepage')->isEmpty()) {
      $home_node = $site_term->get('field_site_homepage')->entity;
      if ($home_node instanceof NodeInterface && !$home_node->isNew()) {
        $url = $home_node->toUrl()->toString();
        // Add home node tags to discovery.
        $this->discoveredTags = array_merge($this->discoveredTags, $home_node->getCacheTags());
      }
    }

    return ['title' => 'Home', 'url' => $url];
  }

  /**
   * Removes duplicate items from the trail based on the URL path.
   *
   * @param array $trail
   *   The raw trail array.
   *
   * @return array
   *   The deduplicated trail.
   */
  protected function deduplicateTrail(array $trail) {
    $unique = [];
    $seen = [];
    foreach ($trail as $item) {
      $normUrl = rtrim(parse_url($item['url'], PHP_URL_PATH), '/');
      if (!isset($seen[$normUrl])) {
        $unique[] = $item;
        $seen[$normUrl] = TRUE;
      }
    }
    return $unique;
  }

  /**
   * Returns cache tags for automatic invalidation.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The current node.
   *
   * @return array
   *   An array of unique cache tags.
   */
  public function getCacheTags(NodeInterface $node) {
    $nid = $node->id() ?: 'new';

    // Ensure the trail has been built to discover tags.
    if (!isset($this->staticTrail[$nid])) {
      $this->buildFullTrail($node);
    }

    $tags = $this->discoveredTags;

    // Primary Site Menu Dependency.
    if ($node->hasField('field_node_primary_site') && !$node->get('field_node_primary_site')->isEmpty()) {
      $site = $node->get('field_node_primary_site')->entity;
      if ($site instanceof TermInterface) {
        $tags = array_merge($tags, $site->getCacheTags());

        if ($site->hasField('field_site_main_menu') && !$site->get('field_site_main_menu')->isEmpty()) {
          $tags[] = 'config:system.menu.' . $site->get('field_site_main_menu')->target_id;
        }
      }
    }

    // Section Site Menu Dependencies.
    $section_terms = $this->getOrderedSectionTerms($node);
    foreach ($section_terms as $term) {
      if ($term->hasField('field_site_main_menu') && !$term->get('field_site_main_menu')->isEmpty()) {
        $tags[] = 'config:system.menu.' . $term->get('field_site_main_menu')->target_id;
      }
    }

    return array_unique($tags);
  }

  /**
   * Returns cache contexts for language and path awareness.
   *
   * @return array
   *   An array of cache contexts.
   */
  public function getCacheContexts() {
    return ['url.path', 'languages', 'user.permissions'];
  }

}
