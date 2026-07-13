<?php

namespace Drupal\tide_core;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
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
   * The menu link plugin manager.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $menuLinkManager;

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
   * Per-request static cache of computed trails, keyed by node and site id.
   *
   * @var array
   */
  protected $trails = [];

  /**
   * Cache tags discovered while building each trail, keyed like $trails.
   *
   * @var array
   */
  protected $trailTags = [];

  /**
   * The node currently in scope (set by the JSON-LD computed field).
   *
   * @var \Drupal\node\NodeInterface|null
   */
  protected $contextNode;

  /**
   * Constructs a new TideBreadcrumb service.
   *
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager
   *   The menu link plugin manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack, used to resolve the site currently being viewed.
   */
  public function __construct(MenuLinkManagerInterface $menu_link_manager, ModuleHandlerInterface $module_handler, RequestStack $request_stack) {
    $this->menuLinkManager = $menu_link_manager;
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

    $tags = $node->getCacheTags();
    $menu_name = NULL;
    $trail = [];

    if ($site_term instanceof TermInterface) {
      $tags = Cache::mergeTags($tags, $site_term->getCacheTags());

      // Per-site switch (default off): only build a breadcrumb when the site
      // being viewed has it explicitly enabled. When disabled, emit nothing and
      // skip the alter so other modules cannot re-add a trail for this site.
      if (!$this->siteBreadcrumbsEnabled($site_term)) {
        $this->trails[$cache_key] = [];
        $this->trailTags[$cache_key] = $tags;
        return [];
      }

      // Always start at Home.
      $trail[] = $this->getHomeCrumb();

      // Find the node's ancestors within the site's main menu.
      $menu_name = $this->getSiteMainMenu($site_term);
      if ($menu_name && !$node->isNew()) {
        $tags[] = 'config:system.menu.' . $menu_name;
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
    $this->trailTags[$cache_key] = $tags;
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
   * Looks the node's link up directly in the menu tree storage (an indexed
   * query on the link's route) and walks the parent chain upwards, so the
   * cost scales with the trail depth rather than the size of the menu. URLs
   * are only generated for the ancestors that end up in the trail.
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
    $links = $this->menuLinkManager->loadLinksByRoute('entity.node.canonical', ['node' => $target_nid], $menu_name);

    $link = NULL;
    foreach ($links as $candidate) {
      if ($candidate->isEnabled()) {
        $link = $candidate;
        break;
      }
    }
    if (!$link) {
      return [];
    }

    $ancestors = [];
    $parent_id = $link->getParent();
    while ($parent_id) {
      try {
        $parent = $this->menuLinkManager->createInstance($parent_id);
        // A disabled ancestor hides the whole branch from the rendered menu,
        // so the node has no visible trail.
        if (!$parent->isEnabled()) {
          return [];
        }
        $ancestors[] = [
          'title' => $parent->getTitle(),
          'url' => $parent->getUrlObject()->toString(),
        ];
        $parent_id = $parent->getParent();
      }
      catch (\Exception $e) {
        return [];
      }
    }

    return array_reverse($ancestors);
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
    $site_term = $this->getViewingSite($node);
    $cache_key = $nid . ':' . ($site_term ? $site_term->id() : 'none');
    if (!isset($this->trailTags[$cache_key])) {
      $this->build($node);
    }
    return array_unique($this->trailTags[$cache_key] ?? []);
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
