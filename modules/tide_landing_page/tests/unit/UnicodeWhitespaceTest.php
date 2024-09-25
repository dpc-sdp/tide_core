<?php

namespace Drupal\Tests\tide_landing_page\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\tide_landing_page\Plugin\jsonapi\FieldEnhancer\BasicTextEnhancer;

/**
 * Tests the unicode replacepemt process from basic text enhancer.
 *
 * @group tide_landing_page
 */
class UnicodeWhitespaceTest extends UnitTestCase {

  /**
   * Test Unicode Whitespace Replacement.
   */
  public function testUnicodeWhitespaceReplacement() {
    $textEnhancer = new BasicTextEnhancer([], 'basic_text_enhancer', 'Basic Text Enhancer');
    /*
     * See the non-printable characters at any online tool
     * to check the difference between unicoded text and expected clean text.
     * e.g - https://www.soscisurvey.de/tools/view-chars.php
     */
    $unicoded_text = [
      'value' => 'The Victorian Government has made several investments',
      'processed' => 'The Victorian Government has made several investments',
    ];
    $expected_clean_text = [
      'value' => 'The Victorian Government has made several investments',
      'processed' => 'The Victorian Government has made several investments',
    ];

    // Checking the unicoded text and expected text should not be equal.
    $this->assertNotEquals($unicoded_text['value'], $expected_clean_text['value']);
    $this->assertNotEquals($unicoded_text['value'], $expected_clean_text['value']);

    // Clean the unicoded text and compare with the expected clean text.
    $clean_text = $textEnhancer->replaceUnicodeWhitespace($unicoded_text);
    $this->assertEquals($clean_text['value'], $expected_clean_text['value']);
    $this->assertEquals($clean_text['processed'], $expected_clean_text['processed']);
  }

}
