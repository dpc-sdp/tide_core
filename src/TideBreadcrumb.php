<?php

namespace Drupal\tide_core;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\TermInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Builds a simple, menu-based breadcrumb trail for a node.
 *
 * This is the always-on baseline breadcrumb engine that lives in tide_core.
 * The logic is deliberately minimal: it locates the node inside the main menu
 * of the site being viewed, walks up the parent chain to collect the ancestor
 * crumbs, and prepends a "Home" crumb.
 *
 * On a multisite CMS a node can belong to several sites. The trail is built for
 * the site currently being viewed (the JSON:API "site" query parameter), so the
 * per-site toggle and menu are resolved against that site rather than the
 * primary site. It falls back to the primary site when no site is in scope
 * (e.g. back-end rendering).
 *
 * The computed trail is passed through hook_tide_breadcrumb_alter() so other
 * modules (e.g. a reworked tide_breadcrumbs) can override or augment it before
 * it is consumed (JSON-LD BreadcrumbList, JSON:API, etc.).
 *
 * @see hook_tide_breadcrumb_alter()
 */
class TideBreadcrumb {

  /**
   * The menu link tree service.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuTree;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Per-request static cache of computed trails, keyed by node id.
   *
   * @var array
   */
  protected $trails = [];

  /**
   * Cache tags discovered while building the current trail.
   *
   * @var array
   */
  protected $cacheTags = [];

  /**
   * The node currently in scope (set by the JSON-LD computed field).
   *
   * @var \Drupal\node\NodeInterface|null
   */
  protected $contextNode;

  /**
   * Constructs a new TideBreadcrumb service.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menu_tree
   *   The menu link tree service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack, used to resolve the site currently being viewed.
   */
  public function __construct(MenuLinkTreeInterface $menu_tree, ModuleHandlerInterface $module_handler, RequestStack $request_stack) {
    $this->menuTree = $menu_tree;
    $this->moduleHandler = $module_handler;
    $this->requestStack = $request_stack;
  }

  /**
   * Builds the breadcrumb trail for a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to build the trail for.
   *
   * @return array
   *   An ordered list of crumbs, each an array with 'title' and 'url' keys,
   *   starting with "Home" and ending with the node's parent (the current
   *   node itself is not included). Empty when there is no resolvable trail.
   */
  public function build(NodeInterface $node): array {
    $nid = $node->id() ?: 'new';
    // Resolve the site being viewed first: the trail (and the per-site toggle)
    // vary per site on a multisite CMS, so the static cache is keyed by both.
    $site_term = $this->getViewingSite($node);
    $cache_key = $nid . ':' . ($site_term ? $site_term->id() : 'none');
    if (isset($this->trails[$cache_key])) {
      return $this->trails[$cache_key];
    }

    $this->cacheTags = $node->getCacheTags();
    $menu_name = NULL;
    $trail = [];

    if ($site_term instanceof TermInterface) {
      $this->cacheTags = Cache::mergeTags($this->cacheTags, $site_term->getCacheTags());

      // Per-site switch (default off): only build a breadcrumb when the site
      // being viewed has it explicitly enabled. When disabled, emit nothing and
      // skip the alter so other modules cannot re-add a trail for this site.
      if (!$this->siteBreadcrumbsEnabled($site_term)) {
        $this->trails[$cache_key] = [];
        return [];
      }

      // Always start at Home.
      $trail[] = $this->getHomeCrumb();

      // Find the node's ancestors within the site's main menu.
      $menu_name = $this->getSiteMainMenu($site_term);
      if ($menu_name && !$node->isNew()) {
        $this->cacheTags[] = 'config:system.menu.' . $menu_name;
        $ancestors = $this->getMenuAncestors($menu_name, (string) $node->id());
        if ($ancestors) {
          $trail = array_merge($trail, $ancestors);
        }
      }
    }

    // Let other modules override or augment the computed trail. This is the
    // organic override point: a future tide_breadcrumbs can substitute its
    // own calculation here, which in turn changes the JSON-LD BreadcrumbList.
    $context = ['node' => $node, 'menu' => $menu_name, 'site' => $site_term];
    $this->moduleHandler->alter('tide_breadcrumb', $trail, $node, $context);

    $this->trails[$cache_key] = $trail;
    return $trail;
  }

  /**
   * Sets the node currently in scope.
   *
   * Used by the JSON-LD computed field so the schema BreadcrumbList property
   * type can resolve the node under JSON:API, where the route match does not
   * carry an entity.node.canonical parameter.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node in scope.
   */
  public function setContextNode(NodeInterface $node): void {
    $this->contextNode = $node;
  }

  /**
   * Returns the node currently in scope, if any.
   *
   * @return \Drupal\node\NodeInterface|null
   *   The node in scope, or NULL.
   */
  public function getContextNode(): ?NodeInterface {
    return $this->contextNode;
  }

  /**
   * Resolves the node's primary site taxonomy term.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   *
   * @return \Drupal\taxonomy\TermInterface|null
   *   The primary site term, or NULL when not set.
   */
  protected function getPrimarySite(NodeInterface $node): ?TermInterface {
    if (!$node->hasField('field_node_primary_site') || $node->get('field_node_primary_site')->isEmpty()) {
      return NULL;
    }
    $term = $node->get('field_node_primary_site')->entity;
    return $term instanceof TermInterface ? $term : NULL;
  }

  /**
   * Resolves the site currently being viewed for a node.
   *
   * On a multisite CMS a node can belong to several sites. The headless
   * front-end requests content with a "site" query parameter, so the breadcrumb
   * must be built for that site. Only honoured when the node belongs to the
   * requested site; otherwise the node's primary site is used (e.g. back-end
   * rendering, where no site parameter is present).
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   *
   * @return \Drupal\taxonomy\TermInterface|null
   *   The site term to build the breadcrumb for, or NULL.
   */
  public function getViewingSite(NodeInterface $node): ?TermInterface {
    $primary = $this->getPrimarySite($node);

    $request = $this->requestStack->getCurrentRequest();
    $site_id = $request ? $request->query->get('site') : NULL;
    if (empty($site_id)) {
      return $primary;
    }

    foreach ($this->getNodeSiteTerms($node) as $term) {
      if ((string) $term->id() === (string) $site_id) {
        return $term;
      }
    }
    return $primary;
  }

  /**
   * Returns all site terms a node belongs to (primary and section sites).
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   *
   * @return \Drupal\taxonomy\TermInterface[]
   *   Site terms keyed by term id.
   */
  protected function getNodeSiteTerms(NodeInterface $node): array {
    $terms = [];
    foreach (['field_node_primary_site', 'field_node_site'] as $field) {
      if (!$node->hasField($field)) {
        continue;
      }
      $items = $node->get($field);
      if (!$items instanceof EntityReferenceFieldItemListInterface) {
        continue;
      }
      foreach ($items->referencedEntities() as $term) {
        if ($term instanceof TermInterface) {
          $terms[$term->id()] = $term;
        }
      }
    }
    return $terms;
  }

  /**
   * Resolves the main menu machine name for a site term.
   *
   * @param \Drupal\taxonomy\TermInterface $site_term
   *   The site taxonomy term.
   *
   * @return string|null
   *   The menu machine name, or NULL when not configured.
   */
  protected function getSiteMainMenu(TermInterface $site_term): ?string {
    if ($site_term->hasField('field_site_main_menu') && !$site_term->get('field_site_main_menu')->isEmpty()) {
      return $site_term->get('field_site_main_menu')->target_id;
    }
    return NULL;
  }

  /**
   * Checks whether a site has breadcrumbs enabled.
   *
   * Controlled by the boolean field_enable_breadcrumbs on the Sites vocabulary.
   * Defaults to off: breadcrumbs are only built for sites that opt in.
   *
   * @param \Drupal\taxonomy\TermInterface $site_term
   *   The site taxonomy term.
   *
   * @return bool
   *   TRUE when breadcrumbs are enabled for the site.
   */
  protected function siteBreadcrumbsEnabled(TermInterface $site_term): bool {
    return $site_term->hasField('field_enable_breadcrumbs')
      && (bool) $site_term->get('field_enable_breadcrumbs')->value;
  }

  /**
   * Builds the "Home" crumb for a site.
   *
   * The front-end home of a Tide site is its domain root, so the URL is the
   * site root ("/"), which downstream resolves to the site's base URL (e.g.
   * "https://www.example.vic.gov.au") rather than the homepage node alias.
   *
   * @return array
   *   A crumb array with 'title' (always "Home") and 'url'.
   */
  protected function getHomeCrumb(): array {
    return ['title' => 'Home', 'url' => '/'];
  }

  /**
   * Returns the ancestor crumbs for a node within a menu.
   *
   * Locates the node's link in the menu and returns every crumb above it
   * (menu root down to the node's parent). The node's own link is omitted.
   *
   * @param string $menu_name
   *   The menu machine name to search.
   * @param string $target_nid
   *   The node id to locate.
   *
   * @return array
   *   Ordered ancestor crumbs (shallowest first); empty when the node is not
   *   in the menu or sits at the top level.
   */
  protected function getMenuAncestors(string $menu_name, string $target_nid): array {
    $parameters = (new MenuTreeParameters())->onlyEnabledLinks();
    $tree = $this->menuTree->load($menu_name, $parameters);
    if (empty($tree)) {
      return [];
    }

    $path = $this->searchTree($tree, $target_nid);
    if (!$path) {
      return [];
    }

    // Drop the node's own link; keep only its ancestors.
    array_pop($path);
    return $path;
  }

  /**
   * Recursively searches a menu tree for the path to a node.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeElement[] $tree
   *   The (sub)tree to search.
   * @param string $target_nid
   *   The node id to locate.
   * @param array $path
   *   The accumulated path of crumbs to the current branch.
   *
   * @return array|null
   *   The full path of crumbs from the menu root to the matched node
   *   (inclusive), or NULL when the node is not found in this branch.
   */
  protected function searchTree(array $tree, string $target_nid, array $path = []): ?array {
    foreach ($tree as $element) {
      $link = $element->link;
      if (!$link->isEnabled()) {
        continue;
      }

      try {
        $url_object = $link->getUrlObject();
        $crumb = [
          'title' => $link->getTitle(),
          'url' => $url_object->toString(),
        ];
      }
      catch (\Exception $e) {
        continue;
      }

      $current_path = $path;
      $current_path[] = $crumb;

      if ($this->linkPointsToNode($url_object, $target_nid)) {
        return $current_path;
      }

      if (!empty($element->subtree)) {
        $result = $this->searchTree($element->subtree, $target_nid, $current_path);
        if ($result) {
          return $result;
        }
      }
    }
    return NULL;
  }

  /**
   * Checks whether a menu link URL points to a given node.
   *
   * @param \Drupal\Core\Url $url_object
   *   The menu link URL object.
   * @param string $target_nid
   *   The node id to compare against.
   *
   * @return bool
   *   TRUE when the URL is the canonical route of the target node.
   */
  protected function linkPointsToNode($url_object, string $target_nid): bool {
    if (!$url_object->isRouted() || $url_object->getRouteName() !== 'entity.node.canonical') {
      return FALSE;
    }
    $params = $url_object->getRouteParameters();
    return isset($params['node']) && (string) $params['node'] === $target_nid;
  }

  /**
   * Returns the cache tags for a node's breadcrumb.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   *
   * @return array
   *   Cache tags that invalidate the breadcrumb when its inputs change.
   */
  public function getCacheTags(NodeInterface $node): array {
    $nid = $node->id() ?: 'new';
    if (!isset($this->trails[$nid])) {
      $this->build($node);
    }
    return array_unique($this->cacheTags);
  }

  /**
   * Returns the cache contexts for breadcrumbs.
   *
   * @return array
   *   Cache contexts.
   */
  public function getCacheContexts(): array {
    // The trail varies by the site being viewed (JSON:API "site" parameter).
    return ['url.path', 'url.query_args:site', 'languages'];
  }

}
