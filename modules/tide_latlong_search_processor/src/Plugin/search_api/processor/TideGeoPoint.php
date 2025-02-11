<?php

namespace Drupal\tide_latlong_search_processor\Plugin\search_api\processor;

use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;

/**
 * Custom processor to concatenate latitude and longitude into one field.
 *
 * @SearchApiProcessor(
 *   id = "geopoint",
 *   label = @Translation("Geopoint processor"),
 *   description = @Translation("Concatenates latitude and longitude fields into a single geopoint field for indexing."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class TideGeoPoint extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Geopoint'),
        'description' => $this->t('Concatenates latitude and longitude fields into a single geopoint.'),
        'type' => 'location',
        'processor_id' => $this->getPluginId(),
      ];
      $properties['tide_geopoint'] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $entity = $item->getOriginalObject()->getValue();
    if ($entity->hasField('field_latitude') && $entity->hasField('field_longitude')) {
      $latitude = $entity->get('field_latitude')->getString();
      $longitude = $entity->get('field_longitude')->getString();

      if ($latitude && $longitude) {
        $geopoint = $latitude . ',' . $longitude;
        $fields = $this->getFieldsHelper()
          ->filterForPropertyPath($item->getFields(), NULL, 'tide_geopoint');
        foreach ($fields as $field) {
          $field->addValue($geopoint);
        }
      }
    }
  }

}
