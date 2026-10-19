<?php

declare(strict_types=1);

namespace Drupal\tide_core\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\filter\Attribute\Filter;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\Plugin\FilterInterface;

/**
 * Adds the standard allow attribute to every iframe in formatted text.
 */
#[Filter(
  id: 'tide_ckeditor_iframe_permissions',
  title: new TranslatableMarkup('Add standard permissions to iframes'),
  description: new TranslatableMarkup('Adds the standard allow attribute to every iframe.'),
  type: FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
  weight: 101,
)]
final class FilterIframePermissions extends FilterBase {

  /**
   * The permissions applied to every iframe.
   */
  private const IFRAME_ALLOW = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode): FilterProcessResult {
    $result = new FilterProcessResult($text);

    if (stripos($text, '<iframe') === FALSE) {
      return $result;
    }

    $dom = Html::load($text);
    $xpath = new \DOMXPath($dom);
    foreach ($xpath->query('//iframe') as $iframe) {
      assert($iframe instanceof \DOMElement);
      $iframe->setAttribute('allow', self::IFRAME_ALLOW);
    }

    return $result->setProcessedText(Html::serialize($dom));
  }

}
