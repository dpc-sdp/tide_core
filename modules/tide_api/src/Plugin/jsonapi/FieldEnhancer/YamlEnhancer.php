<?php

namespace Drupal\tide_api\Plugin\jsonapi\FieldEnhancer;

use Drupal\Component\Utility\Html;
use Drupal\Core\Serialization\Yaml;
use Drupal\jsonapi_extras\Plugin\ResourceFieldEnhancerBase;
use Shaper\Util\Context;

/**
 * Decode YAML content.
 *
 * @ResourceFieldEnhancer(
 *   id = "yaml",
 *   label = @Translation("YAML"),
 *   description = @Translation("Decode YAML content.")
 * )
 */
class YamlEnhancer extends ResourceFieldEnhancerBase {

  /**
   * {@inheritdoc}
   */
  protected function doUndoTransform($data, Context $context) {
    $data = Yaml::decode($data);

    // Include the token service to process any tokens in the YAML data.
    $token_service = \Drupal::service('token');
    if (!empty($data['processed_text']['#text'])) {
      $data['processed_text']['#text'] = $this->processText($data['processed_text']['#text']);
    }

    if (!empty($data['markup']['#markup'])) {
      $data['markup']['#markup'] = $this->processText($data['markup']['#markup']);
    }
    // Process any other fields that may contain token replacements.
    foreach ($data as $key => &$value) {
      if ($value['#type'] === 'webform_wizard_page') {
        foreach ($value as $page_key => &$page) {
          if (is_array($page)) {
            $this->replaceTokensInDefaultValue($page, $token_service);
          }
        }
      }

      $this->replaceTokensInDefaultValue($value, $token_service);
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  protected function doTransform($data, Context $context) {
    return Yaml::encode($data);
  }

  /**
   * {@inheritdoc}
   */
  public function getOutputJsonSchema() {
    return [
      'anyOf' => [
        ['type' => 'array'],
        ['type' => 'boolean'],
        ['type' => 'null'],
        ['type' => 'number'],
        ['type' => 'object'],
        ['type' => 'string'],
      ],
    ];
  }

  /**
   * Helper function to convert node urls to path alias.
   *
   * @param string $text
   *   The string value of the field.
   *
   * @return string
   *   The processed text.
   */
  public function processText($text) {
    $result = $text;
    if (strpos($text, 'data-entity-type') !== FALSE && strpos($text, 'data-entity-uuid') !== FALSE) {
      $dom = Html::load($text);
      $xpath = new \DOMXPath($dom);
      foreach ($xpath->query('//a[@data-entity-type and @data-entity-uuid]') as $element) {
        /** @var \DOMElement $element */
        try {
          $entity_type = $element->getAttribute('data-entity-type');
          if ($entity_type == 'node') {
            $href = $element->getAttribute('href');
            $aliasByPath = \Drupal::service('path_alias.manager')->getAliasByPath($href);
            $alias_helper = \Drupal::service('tide_site.alias_storage_helper');
            $url = $alias_helper->getPathAliasWithoutSitePrefix(['alias' => $aliasByPath]);
            $element->setAttribute('href', $url);
          }
        }
        catch (\Exception $e) {
          watchdog_exception('YamlEnhancer_processText', $e);
        }
      }
      $result = Html::serialize($dom);
    }
    return $result;
  }

  /**
   * Replaces tokens in the #default_value field of an element.
   *
   * @param array &$element
   *   The element array to process.
   * @param \Drupal\Core\Utility\Token $token_service
   *   The token service.
   */
  private function replaceTokensInDefaultValue(array &$element, $token_service) {
    if (!empty($element['#default_value'])) {
      $element['#default_value'] = $token_service->replace($element['#default_value']);
    }
  }

}
