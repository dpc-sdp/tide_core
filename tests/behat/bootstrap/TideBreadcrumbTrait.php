<?php

namespace Tide\Tests\Context;

use Behat\Gherkin\Node\TableNode;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Symfony\Component\HttpFoundation\Request;

/**
 * Steps for testing the tide_core JSON-LD breadcrumb (TideBreadcrumb).
 *
 * The breadcrumb engine lives in tide_core: it builds a menu-based trail for a
 * node, gated per-site by field_enable_breadcrumbs, and exposes it through the
 * node's computed json_ld field (schema.org BreadcrumbList). These steps set up
 * menu links pointing to nodes and assert against the computed json_ld value.
 *
 * Relies on MenuTrait (loadMenuByLabel/loadMenuLinkByTitle and the $menuLinks
 * cleanup) being present on the same context class.
 */
trait TideBreadcrumbTrait {

  /**
   * Creates menu links that point to nodes, resolved by node title.
   *
   * Unlike the generic ":menu_name menu_links:" step, the target node is
   * resolved by title, so scenarios never need to know node IDs. Hierarchy is
   * expressed by referencing a previously-created link title in "parent".
   *
   * | title        | node           | parent       |
   * | Parent crumb | BC Parent page |              |
   * | Child crumb  | BC Child page  | Parent crumb |
   *
   * @Given :menu_name menu_links for nodes:
   */
  public function tideBreadcrumbMenuLinksForNodes($menu_name, TableNode $table) {
    $menu = $this->loadMenuByLabel($menu_name);
    foreach ($table->getHash() as $row) {
      $node = $this->tideBreadcrumbGetNodeByTitle($row['node']);
      $values = [
        'title' => $row['title'],
        'menu_name' => $menu->id(),
        'link' => ['uri' => 'entity:node/' . $node->id()],
        'enabled' => 1,
      ];
      if (!empty($row['parent'])) {
        $parent = $this->loadMenuLinkByTitle($row['parent'], $menu_name);
        $values['parent'] = 'menu_link_content:' . $parent->uuid();
      }
      $link = MenuLinkContent::create($values);
      $link->save();
      // Tracked by MenuTrait::menuCleanAll() for automatic cleanup.
      $this->menuLinks[] = $link;
    }
  }

  /**
   * Assigns a menu as the main menu of a Sites term.
   *
   * Set explicitly (not via a field column) because field_site_main_menu
   * references a menu config entity, which the generic term-creation step
   * does not resolve by label.
   *
   * @Given the main menu of site :site_name is :menu_label
   */
  public function tideBreadcrumbSetSiteMainMenu($site_name, $menu_label) {
    $menu = $this->loadMenuByLabel($menu_label);
    $term = $this->tideBreadcrumbGetSiteTermByName($site_name);
    $term->set('field_site_main_menu', $menu->id());
    $term->save();
  }

  /**
   * Asserts a node's computed json_ld contains a string.
   *
   * @Then the JSON-LD of :type :title should contain :text
   */
  public function tideBreadcrumbJsonLdContains($type, $title, $text) {
    $json = $this->tideBreadcrumbGetJsonLd($type, $title);
    if (strpos($json, $text) === FALSE) {
      throw new \Exception(sprintf("The JSON-LD of %s '%s' does not contain '%s'.\nActual:\n%s", $type, $title, $text, $json));
    }
  }

  /**
   * Asserts a node's computed json_ld does not contain a string.
   *
   * @Then the JSON-LD of :type :title should not contain :text
   */
  public function tideBreadcrumbJsonLdNotContains($type, $title, $text) {
    $json = $this->tideBreadcrumbGetJsonLd($type, $title);
    if (strpos($json, $text) !== FALSE) {
      throw new \Exception(sprintf("The JSON-LD of %s '%s' unexpectedly contains '%s'.\nActual:\n%s", $type, $title, $text, $json));
    }
  }

  /**
   * Asserts a node's json_ld, as viewed on a given site, contains a string.
   *
   * Simulates the headless front-end requesting the node with ?site=<tid>, so
   * the multisite-aware breadcrumb is computed for that site.
   *
   * @Then with site :site_name the JSON-LD of :type :title should contain :text
   */
  public function tideBreadcrumbJsonLdWithSiteContains($site_name, $type, $title, $text) {
    $json = $this->tideBreadcrumbGetJsonLdForSite($type, $title, $site_name);
    if (strpos($json, $text) === FALSE) {
      throw new \Exception(sprintf("With site '%s', the JSON-LD of %s '%s' does not contain '%s'.\nActual:\n%s", $site_name, $type, $title, $text, $json));
    }
  }

  /**
   * Asserts a node's json_ld, as viewed on a given site, lacks a string.
   *
   * @Then with site :site_name the JSON-LD of :type :title should not contain :text
   */
  public function tideBreadcrumbJsonLdWithSiteNotContains($site_name, $type, $title, $text) {
    $json = $this->tideBreadcrumbGetJsonLdForSite($type, $title, $site_name);
    if (strpos($json, $text) !== FALSE) {
      throw new \Exception(sprintf("With site '%s', the JSON-LD of %s '%s' unexpectedly contains '%s'.\nActual:\n%s", $site_name, $type, $title, $text, $json));
    }
  }

  /**
   * Computes a node's json_ld as if viewed on a given site (?site=<tid>).
   */
  protected function tideBreadcrumbGetJsonLdForSite($type, $title, $site_name) {
    $site = $this->tideBreadcrumbGetSiteTermByName($site_name);
    $node = $this->tideBreadcrumbGetNodeByTitle($title, $type);
    $request_stack = \Drupal::requestStack();
    // Push a request carrying the site parameter the front-end would send.
    $request_stack->push(Request::create('/', 'GET', ['site' => $site->id()]));
    try {
      \Drupal::entityTypeManager()->getStorage('node')->resetCache([$node->id()]);
      $node = $this->tideBreadcrumbGetNodeByTitle($title, $type);
      return (string) ($node->get('json_ld')->value ?? '');
    }
    finally {
      $request_stack->pop();
    }
  }

  /**
   * Returns the freshly computed json_ld value of a node.
   */
  protected function tideBreadcrumbGetJsonLd($type, $title) {
    $node = $this->tideBreadcrumbGetNodeByTitle($title, $type);
    // Recompute against the current menu/site state.
    \Drupal::entityTypeManager()->getStorage('node')->resetCache([$node->id()]);
    $node = $this->tideBreadcrumbGetNodeByTitle($title, $type);
    return (string) ($node->get('json_ld')->value ?? '');
  }

  /**
   * Loads a node by title (and optional bundle).
   *
   * @return \Drupal\node\NodeInterface
   *   The node.
   */
  protected function tideBreadcrumbGetNodeByTitle($title, $type = NULL) {
    $properties = ['title' => $title];
    if ($type) {
      $properties['type'] = $type;
    }
    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties($properties);
    if (empty($nodes)) {
      throw new \Exception(sprintf("Could not find a node with title '%s'.", $title));
    }
    return reset($nodes);
  }

  /**
   * Loads a Sites vocabulary term by name.
   *
   * @return \Drupal\taxonomy\TermInterface
   *   The site term.
   */
  protected function tideBreadcrumbGetSiteTermByName($name) {
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties([
      'vid' => 'sites',
      'name' => $name,
    ]);
    if (empty($terms)) {
      throw new \Exception(sprintf("Could not find a Sites term named '%s'.", $name));
    }
    return reset($terms);
  }

}
