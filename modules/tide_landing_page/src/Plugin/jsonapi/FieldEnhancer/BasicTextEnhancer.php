<?php

namespace Drupal\tide_landing_page\Plugin\jsonapi\FieldEnhancer;

use Drupal\jsonapi_extras\Plugin\ResourceFieldEnhancerBase;
use Shaper\Util\Context;

/**
 * Adds necessary fields from internal link.
 *
 * @ResourceFieldEnhancer(
 *   id = "basic_text_enhancer",
 *   label = @Translation("Basic Text Enhancer"),
 *   description = @Translation("Clean up the text from Wysiwyg.")
 * )
 */
class BasicTextEnhancer extends ResourceFieldEnhancerBase {

  /**
   * {@inheritdoc}
   */
  protected function doUndoTransform($data, Context $context) {
    if ($data) {
      $data = $this->replaceUnicodeWhitespace($data);
      $data = $this->addTableStylesToProcessed($data);
    }
    return $data;
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
        ['type' => 'null'],
      ],
    ];
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
  public function replaceUnicodeWhitespace(array $data) {
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

    if ($data['value'] && $data['processed']) {
      $data['value'] = preg_replace($codepoints, " ", $data['value']);
      $data['processed'] = preg_replace($codepoints, " ", $data['processed']);
    }
    return $data;
  }

  /**
   * Helper function to add table styles to processed field.
   */
  public function addTableStylesToProcessed(&$data) {
    // Check if 'value' and 'processed' keys exist in $data.
    if (isset($data['value']) && isset($data['processed'])) {
      // Load 'value' and 'processed' HTML as DOM objects for manipulation.
      $valueDom = new \DOMDocument();
      $processedDom = new \DOMDocument();

      // Suppress warnings for malformed HTML in $value and $processed.
      libxml_use_internal_errors(TRUE);
      $valueDom->loadHTML($data['value']);
      $processedDom->loadHTML($data['processed']);
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
      $data['processed'] = $processedBodyContent;
    }
    return $data;
  }

}
