<?php

declare(strict_types=1);

namespace Drupal\Tests\tide_ckeditor\Unit\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Tests\UnitTestCase;
use Drupal\tide_ckeditor\Plugin\Filter\FilterIframePermissions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the iframe permissions text filter.
 */
#[CoversClass(FilterIframePermissions::class)]
#[Group('tide_ckeditor')]
final class FilterIframePermissionsTest extends UnitTestCase {

  /**
   * The expected iframe permissions.
   */
  private const IFRAME_ALLOW = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';

  /**
   * The filter under test.
   */
  private FilterIframePermissions $filter;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    if (!class_exists(FilterIframePermissions::class)) {
      require_once dirname(__DIR__, 5) . '/src/Plugin/Filter/FilterIframePermissions.php';
    }

    $this->filter = new FilterIframePermissions(
      [
        'settings' => [],
        'status' => TRUE,
        'weight' => 101,
      ],
      'tide_ckeditor_iframe_permissions',
      ['provider' => 'tide_ckeditor'],
    );
  }

  /**
   * Tests that text without an iframe is not parsed or changed.
   */
  public function testProcessLeavesTextWithoutIframeUntouched(): void {
    $text = '<p>Text without an iframe.</p>';

    $this->assertSame($text, $this->filter->process($text, 'en')->getProcessedText());
  }

  /**
   * Tests that every iframe receives the standard allow attribute.
   */
  public function testProcessUpdatesEveryIframe(): void {
    $text = '<p>Before</p>'
      . '<iframe src="https://example.com/one" title="One"></iframe>'
      . '<iframe src="https://example.com/two" allow="camera" title="Two"></iframe>';

    $processed = $this->filter->process($text, 'en')->getProcessedText();
    $dom = Html::load($processed);
    $iframes = (new \DOMXPath($dom))->query('//iframe');

    $this->assertCount(2, $iframes);
    foreach ($iframes as $iframe) {
      $this->assertInstanceOf(\DOMElement::class, $iframe);
      $this->assertSame(self::IFRAME_ALLOW, $iframe->getAttribute('allow'));
    }
  }

}
