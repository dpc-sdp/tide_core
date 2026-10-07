<?php

namespace Drupal\Tests\tide_core\Unit;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Menu\MenuLinkInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\tide_core\TideBreadcrumb;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests the indexed Tide breadcrumb lookup and request cache isolation.
 *
 * @coversDefaultClass \Drupal\tide_core\TideBreadcrumb
 * @group tide_core
 */
class TideBreadcrumbTest extends UnitTestCase {

  /**
   * Tests that only the target's ancestors have URLs generated.
   *
   * @covers ::getMenuAncestors
   */
  public function testIndexedLookupOnlyGeneratesAncestorUrls(): void {
    $manager = $this->createMock(MenuLinkManagerInterface::class);
    $target = $this->createLink('parent-2', 'Target', '', TRUE, 'main', 0);
    $parent_2 = $this->createLink('parent-1', 'Parent 2', '/parent-2', TRUE, 'main', 1);
    $parent_1 = $this->createLink('', 'Parent 1', '/parent-1', TRUE, 'main', 1);

    $manager->expects($this->once())
      ->method('loadLinksByRoute')
      ->with('entity.node.canonical', ['node' => '42'], 'main')
      ->willReturn(['target' => $target]);
    $manager->expects($this->exactly(2))
      ->method('createInstance')
      ->willReturnCallback(static fn(string $id) => match ($id) {
        'parent-2' => $parent_2,
        'parent-1' => $parent_1,
      });

    $breadcrumb = $this->createBreadcrumb($manager);
    $this->assertSame([
      ['title' => 'Parent 1', 'url' => '/parent-1'],
      ['title' => 'Parent 2', 'url' => '/parent-2'],
    ], $breadcrumb->getMenuAncestorsForTest('main', '42'));
  }

  /**
   * Tests that a disabled branch does not mask another valid node link.
   *
   * @covers ::getMenuAncestors
   */
  public function testDisabledBranchFallsThroughToNextCandidate(): void {
    $manager = $this->createMock(MenuLinkManagerInterface::class);
    $invalid_target = $this->createLink('disabled-parent', 'Target A', '', TRUE, 'main', 0);
    $valid_target = $this->createLink('enabled-parent', 'Target B', '', TRUE, 'main', 0);
    $disabled_parent = $this->createLink('', 'Disabled', '/disabled', FALSE, 'main', 0);
    $enabled_parent = $this->createLink('', 'Enabled', '/enabled', TRUE, 'main', 1);

    $manager->method('loadLinksByRoute')
      ->willReturn([
        'invalid-target' => $invalid_target,
        'valid-target' => $valid_target,
      ]);
    $manager->expects($this->exactly(2))
      ->method('createInstance')
      ->willReturnCallback(static fn(string $id) => match ($id) {
        'disabled-parent' => $disabled_parent,
        'enabled-parent' => $enabled_parent,
      });

    $breadcrumb = $this->createBreadcrumb($manager);
    $this->assertSame([
      ['title' => 'Enabled', 'url' => '/enabled'],
    ], $breadcrumb->getMenuAncestorsForTest('main', '42'));
  }

  /**
   * Tests that a top-level target produces no ancestor URL generation.
   *
   * @covers ::getMenuAncestors
   */
  public function testTopLevelTargetHasNoAncestors(): void {
    $manager = $this->createMock(MenuLinkManagerInterface::class);
    $target = $this->createLink('', 'Target', '', TRUE, 'main', 0);
    $manager->method('loadLinksByRoute')->willReturn(['target' => $target]);
    $manager->expects($this->never())->method('createInstance');

    $breadcrumb = $this->createBreadcrumb($manager);
    $this->assertSame([], $breadcrumb->getMenuAncestorsForTest('main', '42'));
  }

  /**
   * Tests that cache tags do not bleed between nodes.
   *
   * @covers ::build
   * @covers ::getCacheKey
   * @covers ::getCacheTags
   */
  public function testCacheTagsAreStoredPerNodeAndSite(): void {
    $manager = $this->createMock(MenuLinkManagerInterface::class);
    $site = $this->createMock(TermInterface::class);
    $site->method('id')->willReturn(100);
    $site->method('getCacheTags')->willReturn(['taxonomy_term:100']);

    $node_a = $this->createNode('uuid-a', 1, ['node:1']);
    $node_b = $this->createNode('uuid-b', 2, ['node:2']);
    $breadcrumb = $this->createBreadcrumb($manager, $site);

    $breadcrumb->build($node_a);
    $breadcrumb->build($node_b);

    $this->assertSame(
      ['node:1', 'taxonomy_term:100'],
      $breadcrumb->getCacheTags($node_a),
    );
    $this->assertSame(
      ['node:2', 'taxonomy_term:100'],
      $breadcrumb->getCacheTags($node_b),
    );
  }

  /**
   * Creates a menu link mock.
   */
  private function createLink(string $parent, string $title, string $path, bool $enabled, string $menu, int $url_calls): MenuLinkInterface {
    $link = $this->createMock(MenuLinkInterface::class);
    $link->method('getParent')->willReturn($parent);
    $link->method('getTitle')->willReturn($title);
    $link->method('isEnabled')->willReturn($enabled);
    $link->method('getMenuName')->willReturn($menu);

    $url = $this->createMock(Url::class);
    $url->expects($this->exactly($url_calls))->method('toString')->willReturn($path);
    $link->expects($this->exactly($url_calls))->method('getUrlObject')->willReturn($url);
    return $link;
  }

  /**
   * Creates a node mock with stable cache-key inputs.
   */
  private function createNode(string $uuid, int $nid, array $cache_tags): NodeInterface {
    $language = $this->createMock(LanguageInterface::class);
    $language->method('getId')->willReturn('en');

    $node = $this->createMock(NodeInterface::class);
    $node->method('uuid')->willReturn($uuid);
    $node->method('id')->willReturn($nid);
    $node->method('getRevisionId')->willReturn(1);
    $node->method('language')->willReturn($language);
    $node->method('getCacheTags')->willReturn($cache_tags);
    return $node;
  }

  /**
   * Creates the testable breadcrumb service.
   */
  private function createBreadcrumb(MenuLinkManagerInterface $manager, ?TermInterface $site = NULL): TestableTideBreadcrumb {
    return new TestableTideBreadcrumb(
      $manager,
      $this->createMock(ModuleHandlerInterface::class),
      new RequestStack(),
      $site,
    );
  }

}

/**
 * Exposes the lookup seam and supplies a deterministic site for unit tests.
 */
class TestableTideBreadcrumb extends TideBreadcrumb {

  /**
   * The effective site used by the test.
   *
   * @var \Drupal\taxonomy\TermInterface|null
   */
  private $testSite;

  /**
   * Constructs a testable breadcrumb service.
   */
  public function __construct(MenuLinkManagerInterface $manager, ModuleHandlerInterface $module_handler, RequestStack $request_stack, ?TermInterface $site = NULL) {
    parent::__construct($manager, $module_handler, $request_stack);
    $this->testSite = $site;
  }

  /**
   * Exposes the protected indexed lookup for focused unit testing.
   */
  public function getMenuAncestorsForTest(string $menu_name, string $target_nid): array {
    return $this->getMenuAncestors($menu_name, $target_nid);
  }

  /**
   * {@inheritdoc}
   */
  public function getViewingSite(NodeInterface $node): ?TermInterface {
    return $this->testSite;
  }

  /**
   * Keeps cache tests on the early-return path without field mocks.
   */
  protected function siteBreadcrumbsEnabled(TermInterface $site_term): bool {
    return FALSE;
  }

}
