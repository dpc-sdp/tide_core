<?php

namespace Drupal\Tests\tide_core\Kernel;

use Drupal\Core\Database\Database;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\system\Entity\Menu;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\tide_core\TideBreadcrumb;

/**
 * Tests the tide_core breadcrumb builder.
 *
 * @coversDefaultClass \Drupal\tide_core\TideBreadcrumb
 * @group tide_core
 */
class TideBreadcrumbTest extends KernelTestBase {

  /**
   * The modules to enable for this test.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'filter',
    'node',
    'taxonomy',
    'link',
    'menu_link_content',
    'path_alias',
  ];

  /**
   * Number of unrelated filler links seeded into the menu.
   *
   * Large enough that an implementation scaling with menu size fails the
   * query budget in ::testBuildCostIsBoundedByTrailDepth by an order of
   * magnitude.
   *
   * @var int
   */
  const FILLER_LINKS = 300;

  /**
   * The site term with breadcrumbs enabled.
   *
   * @var \Drupal\taxonomy\Entity\Term
   */
  protected $siteTerm;

  /**
   * Nodes keyed by role: grandparent, parent, target.
   *
   * @var \Drupal\node\Entity\Node[]
   */
  protected $nodes = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('menu_link_content');
    $this->installEntitySchema('path_alias');
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['node', 'filter']);

    NodeType::create(['type' => 'test', 'name' => 'Test'])->save();
    Vocabulary::create(['vid' => 'sites', 'name' => 'Sites'])->save();
    Menu::create(['id' => 'site-main', 'label' => 'Site main menu'])->save();

    $this->createField('taxonomy_term', 'sites', 'field_enable_breadcrumbs', 'boolean');
    $this->createField('taxonomy_term', 'sites', 'field_site_main_menu', 'entity_reference', ['target_type' => 'menu']);
    $this->createField('node', 'test', 'field_node_primary_site', 'entity_reference', ['target_type' => 'taxonomy_term']);

    $this->siteTerm = Term::create([
      'vid' => 'sites',
      'name' => 'Site A',
      'field_enable_breadcrumbs' => 1,
      'field_site_main_menu' => 'site-main',
    ]);
    $this->siteTerm->save();

    // A three-level chain in the menu: Grandparent > Parent > Target.
    foreach (['grandparent', 'parent', 'target'] as $name) {
      $node = Node::create([
        'type' => 'test',
        'title' => ucfirst($name),
        'status' => 1,
        'field_node_primary_site' => $this->siteTerm->id(),
      ]);
      $node->save();
      $this->nodes[$name] = $node;
    }

    $parent_plugin_id = NULL;
    foreach ($this->nodes as $node) {
      $link = MenuLinkContent::create([
        'title' => $node->label(),
        'link' => ['uri' => 'entity:node/' . $node->id()],
        'menu_name' => 'site-main',
        'parent' => $parent_plugin_id,
        'enabled' => 1,
      ]);
      $link->save();
      $parent_plugin_id = 'menu_link_content:' . $link->uuid();
    }

    // Filler links the builder must not pay for: top-level links to node
    // routes that are not the target.
    $filler_base_nid = $this->nodes['target']->id() + 1000;
    for ($i = 0; $i < static::FILLER_LINKS; $i++) {
      MenuLinkContent::create([
        'title' => 'Filler ' . $i,
        'link' => ['uri' => 'entity:node/' . ($filler_base_nid + $i)],
        'menu_name' => 'site-main',
        'enabled' => 1,
      ])->save();
    }

    $this->container->get('router.builder')->rebuild();
  }

  /**
   * Creates a field storage and instance pair.
   */
  protected function createField(string $entity_type, string $bundle, string $field_name, string $type, array $settings = []): void {
    FieldStorageConfig::create([
      'entity_type' => $entity_type,
      'field_name' => $field_name,
      'type' => $type,
      'settings' => $settings,
    ])->save();
    FieldConfig::create([
      'entity_type' => $entity_type,
      'bundle' => $bundle,
      'field_name' => $field_name,
    ])->save();
  }

  /**
   * Instantiates a fresh builder, bypassing its per-request static cache.
   */
  protected function createBuilder(): TideBreadcrumb {
    return new TideBreadcrumb(
      $this->container->get('plugin.manager.menu.link'),
      $this->container->get('module_handler'),
      $this->container->get('request_stack')
    );
  }

  /**
   * Tests the computed trail: Home plus menu ancestors, own link excluded.
   *
   * @covers ::build
   */
  public function testBuildTrail(): void {
    $trail = $this->createBuilder()->build($this->nodes['target']);

    $this->assertSame(['Home', 'Grandparent', 'Parent'], array_column($trail, 'title'));
    $this->assertSame('/', $trail[0]['url']);
    $this->assertStringContainsString('/node/' . $this->nodes['grandparent']->id(), $trail[1]['url']);
    $this->assertStringContainsString('/node/' . $this->nodes['parent']->id(), $trail[2]['url']);
  }

  /**
   * Tests that a top-level node gets only the Home crumb.
   *
   * @covers ::build
   */
  public function testBuildTrailTopLevelNode(): void {
    $trail = $this->createBuilder()->build($this->nodes['grandparent']);
    $this->assertSame(['Home'], array_column($trail, 'title'));
  }

  /**
   * Tests that no trail is built when the site has breadcrumbs disabled.
   *
   * @covers ::build
   */
  public function testBuildTrailDisabledSite(): void {
    $this->siteTerm->set('field_enable_breadcrumbs', 0)->save();
    $node = $this->container->get('entity_type.manager')->getStorage('node')
      ->loadUnchanged($this->nodes['target']->id());
    $this->assertSame([], $this->createBuilder()->build($node));
  }

  /**
   * Tests that build cost is bounded by trail depth, not menu size.
   *
   * Regression test for a performance issue where the builder generated a URL
   * for every link in the menu while searching for the node: with the
   * FILLER_LINKS unrelated links seeded in setUp(), that implementation issues
   * hundreds of queries (one path alias lookup per link); a depth-bounded
   * lookup needs only a handful.
   *
   * @covers ::build
   */
  public function testBuildCostIsBoundedByTrailDepth(): void {
    Database::startLog('tide_breadcrumb');
    $trail = $this->createBuilder()->build($this->nodes['target']);
    $queries = Database::getLog('tide_breadcrumb');

    $this->assertCount(3, $trail);
    $this->assertLessThan(40, count($queries), sprintf(
      'Building a depth-3 trail in a %d-link menu used %d queries; the cost must not scale with menu size.',
      static::FILLER_LINKS + 3,
      count($queries)
    ));
  }

  /**
   * Tests that cache tags are computed per node, not from the last build.
   *
   * @covers ::getCacheTags
   */
  public function testCacheTagsPerNode(): void {
    $builder = $this->createBuilder();
    $target_tags = $builder->getCacheTags($this->nodes['target']);
    $parent_tags = $builder->getCacheTags($this->nodes['parent']);

    $this->assertContains('node:' . $this->nodes['target']->id(), $target_tags);
    $this->assertContains('config:system.menu.site-main', $target_tags);
    $this->assertContains('node:' . $this->nodes['parent']->id(), $parent_tags);
    $this->assertNotContains('node:' . $this->nodes['target']->id(), $parent_tags);
  }

}
