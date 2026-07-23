<?php

namespace Drupal\tide_core;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\node\NodeInterface;
use Drupal\schema_metatag\SchemaMetatagManager;

/**
 * Computed field exposing the assembled JSON-LD string for a node.
 *
 * Generic: works for any node bundle. The JSON-LD is assembled from the
 * 'metatag' computed field provided by the Metatag module, which runs the
 * metatag pipeline once per entity object and is memoised on it. Sharing that
 * single pass keeps this field cheap: JSON:API serializes both fields on
 * every node response, and running the pipeline a second time here doubled
 * the cost of every node serialization.
 */
class JsonLdComputedField extends FieldItemList {

  use ComputedItemListTrait;

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    $entity = $this->getEntity();
    if (!$entity instanceof NodeInterface || $entity->isNew() || !$entity->hasField('metatag')) {
      return;
    }

    // Reuse the raw elements computed by the 'metatag' field instead of
    // re-running the metatag pipeline. The breadcrumb context node is scoped
    // inside that single pipeline run by tide_core_metatags_alter(), and the
    // pipeline itself executes in the metatag field's own render context.
    $elements = [];
    foreach ($entity->get('metatag') as $item) {
      $value = $item->getValue();
      if (!empty($value['attributes']['schema_metatag'])) {
        // parseJsonld() expects html_head-style entries: [element, key].
        $elements[] = [['#attributes' => $value['attributes']]];
      }
    }

    $items = SchemaMetatagManager::parseJsonld($elements);
    if (empty($items)) {
      return;
    }

    // parseJsonld() only skips groups whose sole key is @type, so a group
    // with an empty value (e.g. a WebPage whose breadcrumb is empty because
    // the site has breadcrumbs disabled) still renders as "breadcrumb": [].
    // Trim empty values from each @graph entry and drop entries that end up
    // empty or @type-only.
    if (!empty($items['@graph'])) {
      $graph = [];
      foreach ($items['@graph'] as $entry) {
        $trimmed = SchemaMetatagManager::arrayTrim($entry);
        if (!empty($trimmed)) {
          $graph[] = $trimmed;
        }
      }
      if (empty($graph)) {
        return;
      }
      $items['@graph'] = $graph;
    }

    $jsonld = SchemaMetatagManager::encodeJsonld($items) ?: '';
    if ($jsonld !== '') {
      $this->list[0] = $this->createItem(0, $this->rewriteUrls($jsonld, $this->getFrontEndBaseUrl($entity)));
    }
  }

  /**
   * Returns the absolute https front-end base URL for a node's primary site.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   *
   * @return string
   *   The base URL (e.g. "https://www.example.vic.gov.au"), without a trailing
   *   slash, or an empty string when it cannot be resolved.
   */
  protected function getFrontEndBaseUrl(NodeInterface $node): string {
    if (!\Drupal::moduleHandler()->moduleExists('tide_site')) {
      return '';
    }
    /** @var \Drupal\tide_site\TideSiteHelper $helper */
    $helper = \Drupal::service('tide_site.helper');
    // Use the site currently being viewed so breadcrumb URLs are on the same
    // domain the content is served from (multisite-aware), not always primary.
    $site = \Drupal::service('tide_core.breadcrumb')->getViewingSite($node);
    // Force https regardless of the (possibly http) backend request scheme.
    return $site ? $helper->getSiteBaseUrl($site, 'https') : '';
  }

  /**
   * Rewrites every URL in the JSON-LD to an absolute front-end URL.
   *
   * Ensures all URLs are absolute and on the node's front-end domain with an
   * https scheme: strips the internal /site-XXXX prefix, collapses accidental
   * double schemes, repoints backend/request-host URLs at the front-end base,
   * and absolutises any remaining root-relative URLs.
   *
   * @param string $json
   *   The assembled JSON-LD string.
   * @param string $fe_base
   *   The front-end base URL to use; falls back to the request host when empty.
   *
   * @return string
   *   The rewritten JSON-LD string.
   */
  protected function rewriteUrls(string $json, string $fe_base): string {
    // Strip the internal /site-XXXX prefix.
    $json = preg_replace('#/site-\d+(?=/)#', '', $json);
    $json = preg_replace('#"/site-\d+"#', '"/"', $json);

    // Collapse any accidental double scheme, e.g. http://https://host → https.
    $json = preg_replace('#"https?://(https?://[^"]*)"#', '"$1"', $json);

    $base = $fe_base !== '' ? $fe_base : \Drupal::request()->getSchemeAndHttpHost();
    if ($base === '') {
      return $json;
    }

    // Repoint any backend/request-host URLs at the front-end base.
    $request_host = \Drupal::request()->getSchemeAndHttpHost();
    if ($request_host !== '' && $request_host !== $base) {
      $json = str_replace('"' . $request_host, '"' . $base, $json);
    }

    // Force the front-end host onto https.
    if (preg_match('#^https://(.+)$#', $base, $host)) {
      $json = str_replace('"http://' . $host[1], '"https://' . $host[1], $json);
    }

    // Absolutise any remaining root-relative URLs against the front-end base.
    return preg_replace_callback('#"(/[^"]*)"#', function ($match) use ($base) {
      // The bare site root maps to the base URL without a trailing slash.
      $path = $match[1] === '/' ? '' : $match[1];
      return '"' . $base . $path . '"';
    }, $json);
  }

}
