<?php

declare(strict_types=1);

namespace Drupal\Tests\tide_core\Kernel;

use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Tide Landing Page post-update hooks.
 */
#[CoversFunction('tide_landing_page_post_update_repair_featured_card_display')]
#[Group('tide_core')]
final class TideLandingPagePostUpdateTest extends KernelTestBase {

  /**
   * The featured-card display configuration name.
   */
  private const DISPLAY_ID = 'core.entity_view_display.node.landing_page.featured_card';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'field',
    'node',
    'user',
    'filter',
    'text',
    'token',
    'smart_trim',
    'content_moderation',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['system', 'field']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');

    require_once dirname(__DIR__, 3) . '/modules/tide_landing_page/tide_landing_page.post_update.php';

    NodeType::create(['type' => 'landing_page', 'name' => 'Landing page'])->save();
    EntityViewMode::create([
      'id' => 'node.featured_card',
      'targetEntityType' => 'node',
      'label' => 'Featured card',
    ])->save();
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_landing_page_summary',
      'entity_type' => 'node',
      'type' => 'text_long',
    ]);
    $field_storage->save();
    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'landing_page',
      'label' => 'Summary',
    ])->save();
  }

  /**
   * Tests that the malformed display is repaired and remains an existing entity.
   */
  public function testRepairsMalformedDisplay(): void {
    $uuid = 'f1953b79-1fb7-48d5-a173-c7fa6d141bc3';
    $this->seedDisplay([
      'field_landing_page_summary' => ['type' => 'string_textarea'],
      'field_featured_image' => ['type' => 'entity_browser_entity_reference'],
      'field_form_only' => ['type' => 'text_default'],
    ], $uuid);

    tide_landing_page_post_update_repair_featured_card_display();

    $display = \Drupal::config(self::DISPLAY_ID)->getRawData();
    $this->assertSame('node.landing_page.featured_card', $display['id']);
    $this->assertSame($uuid, $display['uuid']);
    $this->assertTrue($display['status']);
    $this->assertSame('smart_trim', $display['content']['field_landing_page_summary']['type']);
    $this->assertSame('hidden', $display['content']['field_landing_page_summary']['label']);
    $this->assertSame(200, $display['content']['field_landing_page_summary']['settings']['trim_length']);
    $this->assertArrayNotHasKey('field_featured_image', $display['content']);
    $this->assertSame([
      'field_existing_hidden' => TRUE,
      'field_featured_image' => TRUE,
      'field_form_only' => TRUE,
      'links' => TRUE,
    ], $display['hidden']);
    $this->assertArrayNotHasKey('third_party_settings', $display);
    $module_dependencies = $display['dependencies']['module'] ?? [];
    $this->assertContains('smart_trim', $module_dependencies);
    $this->assertNotContains('entity_browser', $module_dependencies);
    $this->assertNotContains('field_group', $module_dependencies);
  }

  /**
   * Tests that a valid customized display is left untouched.
   */
  public function testLeavesCustomizedDisplayUntouched(): void {
    $content = [
      'field_landing_page_summary' => ['type' => 'custom_summary_formatter'],
      'field_featured_image' => ['type' => 'custom_image_formatter'],
    ];
    $this->seedDisplay($content);
    $before = \Drupal::config(self::DISPLAY_ID)->getRawData();

    tide_landing_page_post_update_repair_featured_card_display();

    $this->assertSame($before, \Drupal::config(self::DISPLAY_ID)->getRawData());
  }

  /**
   * Seeds a featured-card display with the given content components.
   */
  private function seedDisplay(array $content, string $uuid = '35f1a8ae-c78c-4f6d-98ef-9830e5b534db'): void {
    \Drupal::configFactory()
      ->getEditable(self::DISPLAY_ID)
      ->setData([
        'uuid' => $uuid,
        'langcode' => 'en',
        'status' => TRUE,
        'dependencies' => [
          'module' => ['entity_browser', 'field_group'],
        ],
        'id' => 'node.landing_page.featured_card',
        'targetEntityType' => 'node',
        'bundle' => 'landing_page',
        'mode' => 'featured_card',
        'content' => $content,
        'hidden' => [
          'links' => FALSE,
          'field_existing_hidden' => TRUE,
        ],
        'third_party_settings' => [
          'field_group' => ['group_form_only' => ['children' => ['field_form_only']]],
        ],
      ])
      ->save();
  }

}
