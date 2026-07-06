<?php

declare(strict_types=1);

namespace Drupal\tide_search\Plugin\DatasetTransform;

use Drupal\data_pipelines\Attribute\DatasetTransform;
use Drupal\data_pipelines\DatasetData;
use Drupal\data_pipelines\Transform\TransformPluginBase;

/**
 * Defines a transform that flattens JSON field values into separate fields.
 */
#[DatasetTransform(
  id: 'flatten',
  records: TRUE,
)]
class Flatten extends TransformPluginBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'fields' => [],
      'separator' => '_',
      'remove_source' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function doTransformRecord(DatasetData $record): DatasetData {
    $record = parent::doTransformRecord($record);
    if (!$this->configuration['fields']) {
      return $record;
    }
    $separator = $this->configuration['separator'];
    $remove_source = $this->configuration['remove_source'];
    foreach ($this->configuration['fields'] as $field_name) {
      if (!$record->offsetExists($field_name)) {
        continue;
      }
      $value = $record[$field_name];
      if (!is_array($value)) {
        continue;
      }
      foreach ($value as $key => $nested_value) {
        $record["{$field_name}{$separator}{$key}"] = $nested_value;
      }
      if ($remove_source) {
        unset($record[$field_name]);
      }
    }
    return $record;
  }

}
