<?php

declare(strict_types=1);

namespace Drupal\tide_data_pipeline\Plugin\DatasetTransform;

use Drupal\data_pipelines\DatasetData;
use Drupal\data_pipelines\Transform\TransformPluginBase;

/**
 * Defines a transform that sets a conditional value.
 *
 * @DatasetTransform(
 *   id="conditional_value",
 *   fields=FALSE,
 *   records=TRUE
 * )
 */
class ConditionalValue extends TransformPluginBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return parent::defaultConfiguration() + [
      'regex' => '',
      'field' => '',
      'value' => '',
    ];
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\data_pipelines\Exception\TransformSkipRecordException
   */
  protected function doTransformRecord(DatasetData $record): DatasetData {
    $record = parent::doTransformRecord($record);
    if (!empty($this->configuration['field']) && $record->offsetExists($this->configuration['field'])) {
      $regex = $this->configuration['regex'];
      $field_name = $this->configuration['field'];
      $field_value = $record->offsetGet($field_name);
      $conditional_value = $this->configuration['value'];
      if (preg_match($regex, $field_value)) {
        $record[$field_name] = $conditional_value;
      }
      else {
        $record[$field_name] = $field_value;
      }
    }
    return $record;
  }

}
