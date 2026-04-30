<?php

namespace Drupal\tide_breadcrumbs;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\node\NodeInterface;
use Drupal\schema_metatag\SchemaMetatagManager;

/**
 * Computed field exposing the assembled JSON-LD string for a node.
 */
class JsonLdComputedField extends FieldItemList {

  use ComputedItemListTrait;

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    $entity = $this->getEntity();
    if (!$entity instanceof NodeInterface || $entity->isNew()) {
      return;
    }

    $renderer = \Drupal::service('renderer');
    $metatag_manager = \Drupal::service('metatag.manager');
    $module_handler = \Drupal::service('module_handler');

    // The metatag pipeline generates URLs which leak BubbleableMetadata into
    // the active render context. Wrap in a context so we don't trigger
    // LeakedMetadata exceptions when this field is computed outside HTML
    // rendering (e.g. via JSON:API).
    $jsonld = $renderer->executeInRenderContext(new RenderContext(), function () use ($entity, $metatag_manager, $module_handler) {
      $metatags = [];
      foreach ($metatag_manager->tagsFromEntityWithDefaults($entity) as $tag => $data) {
        $metatags[$tag] = $data;
      }
      $context = ['entity' => $entity];
      $module_handler->alter('metatags', $metatags, $context);
      $elements = $metatag_manager->generateElements($metatags, $entity);
      $elements = $elements['#attached']['html_head'] ?? $elements;

      $items = SchemaMetatagManager::parseJsonld($elements);
      if (empty($items)) {
        return '';
      }
      return SchemaMetatagManager::encodeJsonld($items) ?: '';
    });

    if ($jsonld !== '') {
      $this->list[0] = $this->createItem(0, $this->rewriteUrls($jsonld));
    }
  }

  /**
   * Rewrites URLs in the assembled JSON-LD string.
   */
  protected function rewriteUrls(string $json): string {
    $json = preg_replace('#/site-\d+(?=/)#', '', $json);
    $json = preg_replace('#"/site-\d+"#', '"/"', $json);
    $base = \Drupal::request()->getSchemeAndHttpHost();
    return preg_replace_callback('#"(/[^"]*)"#', function ($m) use ($base) {
      return '"' . $base . $m[1] . '"';
    }, $json);
  }

}
