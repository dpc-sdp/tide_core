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

}
