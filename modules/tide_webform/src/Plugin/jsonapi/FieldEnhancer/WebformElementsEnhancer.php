<?php

namespace Drupal\tide_webform\Plugin\jsonapi\FieldEnhancer;

use Drupal\jsonapi_extras\Plugin\ResourceFieldEnhancerBase;
use Shaper\Util\Context;
use Symfony\Component\Yaml\Yaml;

/**
 * Adds necessary fields from internal link.
 *
 * @ResourceFieldEnhancer(
 *   id = "webform_elements_enhancer",
 *   label = @Translation("Webform elements Enhancer"),
 *   description = @Translation("Transforms and formats webform elements.")
 * )
 */
class WebformElementsEnhancer extends ResourceFieldEnhancerBase {

  /**
   * {@inheritdoc}
   */
  protected function doUndoTransform($data, Context $context) {
    if (empty($data)) {
      return $data;
    }

    $parsed = Yaml::parse($data);
    if (empty($parsed)) {
      return $data;
    }

    foreach ($parsed as $key => &$element) {
      if (isset($element['#type']) && $element['#type'] === 'processed_text') {
        $element = $this->transformProcessedText($element);
      }
    }

    return $parsed;
  }

  /**
   * {@inheritdoc}
   */
  protected function doTransform($data, Context $context) {
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getOutputJsonSchema() {
    return [
      'anyOf' => [
        ['type' => 'object'],
        ['type' => 'array'],
        ['type' => 'string'],
        ['type' => 'null'],
      ],
    ];
  }

  /**
   * Transforms a processed_text element.
   */
  private function transformProcessedText(array $element): array {
    $value = $element['#text'] ?? '';
    $format = $element['#format'] ?? 'plain_text';

    // Run through Drupal filters.
    $processed = check_markup($value, $format);
    $element['#processed'] = $processed;

    // Apply custom cleanups.
    $element = $this->replaceUnicodeWhitespace($element);
    $element = $this->addTableStylesToProcessed($element);

    // Replace raw #text with processed value.
    $element['#text'] = $element['#processed'];
    unset($element['#processed']);

    return $element;
  }

  /**
   * Helper function to replace unicode white spaces.
   *
   * @param array $data
   *   The data.
   *
   * @return array
   *   The array of fields value.
   */
  public function replaceUnicodeWhitespace($data) {
    $codepoints = [
      '/\x{00A0}/u',
      '/\x{2000}/u',
      '/\x{2001}/u',
      '/\x{2002}/u',
      '/\x{2003}/u',
      '/\x{2004}/u',
      '/\x{2005}/u',
      '/\x{2006}/u',
      '/\x{2007}/u',
      '/\x{2008}/u',
      '/\x{2009}/u',
      '/\x{200A}/u',
      '/\x{200B}/u',
      '/\x{202F}/u',
      '/\x{205F}/u',
      '/\x{FEFF}/u',
    ];

    if ($data['#text'] && $data['#processed']) {
      $data['#text'] = preg_replace($codepoints, " ", $data['#text']);
      $data['#processed'] = preg_replace($codepoints, " ", $data['#processed']);
    }
    return $data;
  }

  /**
   * Helper function to add table styles to processed field.
   */
  public function addTableStylesToProcessed(&$data) {
    // Check if 'text' and 'processed' keys exist in $data.
    if (isset($data['#text']) && isset($data['#processed'])) {
      // Create DOM objects with proper UTF-8 handling.
      $valueDom = new \DOMDocument('1.0', 'UTF-8');
      $processedDom = new \DOMDocument('1.0', 'UTF-8');
      // Suppress warnings.
      libxml_use_internal_errors(TRUE);
      // Add UTF-8 meta tag and load content.
      $valueHtml = mb_convert_encoding($data['#text'], 'HTML-ENTITIES', 'UTF-8');
      $processedHtml = mb_convert_encoding($data['#processed'], 'HTML-ENTITIES', 'UTF-8');
      $valueDom->loadHTML($valueHtml);
      $processedDom->loadHTML($processedHtml);
      libxml_clear_errors();

      // Get <table> elements from both value and processed DOMs.
      $valueTables = $valueDom->getElementsByTagName('table');
      $processedTables = $processedDom->getElementsByTagName('table');

      // Apply data attributes and styles if there are tables in both DOMs.
      if ($valueTables->length > 0 && $processedTables->length > 0) {
        // Loop through all <table> elements in both DOMs.
        $tableCount = min($valueTables->length, $processedTables->length);
        for ($i = 0; $i < $tableCount; $i++) {
          $valueTable = $valueTables->item($i);
          $processedTable = $processedTables->item($i);

          // Process <col> elements in both DOMs.
          $valueCols = $valueTable->getElementsByTagName('col');
          $processedCols = $processedTable->getElementsByTagName('col');

          for ($j = 0; $j < min($valueCols->length, $processedCols->length); $j++) {
            $valueCol = $valueCols->item($j);
            $processedCol = $processedCols->item($j);

            if ($valueCol->hasAttribute('style')) {
              // Parse the style attribute.
              $styleValue = $valueCol->getAttribute('style');
              $styles = explode(';', $styleValue);

              foreach ($styles as $style) {
                $style = trim($style);
                if (!empty($style)) {
                  [$property, $value] = explode(':', $style, 2);
                  $property = trim($property);
                  $value = trim($value);

                  // Sanitize the property name for a valid data attribute.
                  $dataAttribute = 'data-' . str_replace([' ', '_'], '-', strtolower($property));
                  $processedCol->setAttribute($dataAttribute, $value);
                }
              }
            }
          }
        }
      }

      // Extract only the inner HTML of the <body> tag, avoiding extra tags.
      $processedBodyContent = '';
      foreach ($processedDom->getElementsByTagName('body')->item(0)->childNodes as $node) {
        $processedBodyContent .= $processedDom->saveHTML($node);
      }

      // Update the 'processed' field with modified HTML.
      $data['#processed'] = $processedBodyContent;
    }
    return $data;
  }

}
