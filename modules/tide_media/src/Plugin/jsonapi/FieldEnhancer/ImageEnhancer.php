<?php

namespace Drupal\tide_media\Plugin\jsonapi\FieldEnhancer;

use Drupal\crop\Entity\Crop;
use Drupal\file\FileInterface;
use Drupal\jsonapi_extras\Plugin\ResourceFieldEnhancerBase;
use Shaper\Util\Context;

/**
 * Adds image focal point values.
 *
 * @ResourceFieldEnhancer(
 *   id = "image_enhancer",
 *   label = @Translation("Image Enhancer(Adds focal point values)"),
 *   description = @Translation("Adds focal point values.")
 * )
 */
class ImageEnhancer extends ResourceFieldEnhancerBase {

  /**
   * {@inheritdoc}
   */
  protected function doUndoTransform($data, Context $context) {
    if (!empty($data)) {
      $processed_data = $this->getFocalPoint($data);
      $data = $processed_data;
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
   * Helper function to add focal point X, Y values.
   *
   * @param object $value
   *   The data for image field.
   *
   * @return object
   *   The image data with focal point values.
   */
  public function getFocalPoint($value) {
    if (!empty($value['id'])) {
      $file = \Drupal::service('entity.repository')->loadEntityByUuid('file', $value['id']);
      $crop = !empty($file) ? $this->getCropEntity($file, 'focal_point') : '';
      $focal_point = !empty($crop) ? $crop->position() : '';
      $value['meta']['focal_point'] = $focal_point;
    }
    return $value;
  }

  /**
   * Helper function to get crop values.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file entity.
   * @param string $crop_type
   *   The type of the crop entity.
   *
   * @return object
   *   The crop data of the entity.
   */
  public static function getCropEntity(FileInterface $file, $crop_type) {
    if (Crop::cropExists($file->getFileUri(), $crop_type)) {
      /** @var \Drupal\crop\CropInterface $crop */
      $crop = Crop::findCrop($file->getFileUri(), $crop_type);
    }
    else {
      $values = [
        'type' => $crop_type,
        'entity_id' => $file->id(),
        'entity_type' => 'file',
        'uri' => $file->getFileUri(),
      ];

      $crop = \Drupal::entityTypeManager()->getStorage('crop')
        ->create($values);
    }

    return $crop;
  }

}
