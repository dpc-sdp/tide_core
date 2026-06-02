<?php

namespace Drupal\tide_data_pipeline\Plugin\jsonapi\FieldEnhancer;

use Drupal\data_pipelines\Entity\Dataset;
use Drupal\jsonapi_extras\Plugin\ResourceFieldEnhancerBase;
use Shaper\Util\Context;

/**
 * Provides a resource field enhancer for dataset field.
 *
 * @ResourceFieldEnhancer(
 * id = "dataset_field_enhancer",
 * label = @Translation("Dataset Field Enhancer"),
 * description = @Translation("Transforms dataset reference into a flattened object with source, name, machine_name, and pipeline.")
 * )
 */
class DatasetFieldEnhancer extends ResourceFieldEnhancerBase {

  /**
   * {@inheritdoc}
   */
  protected function doUndoTransform($data, Context $context) {
    if (empty($data['meta']['drupal_internal__target_id']) || empty($data['type'])) {
      return $data;
    }
    $target_id = $data['meta']['drupal_internal__target_id'];
    $entity = Dataset::load($target_id);
    $data['meta'] = $data['meta'] + [
      'index_name' => $entity->machine_name->value,
      'pipeline' => $entity->pipeline->value,
    ];
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
      ],
    ];
  }

}
