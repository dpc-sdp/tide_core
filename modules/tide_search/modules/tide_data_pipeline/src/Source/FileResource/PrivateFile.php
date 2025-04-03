<?php

declare(strict_types=1);

namespace Drupal\tide_data_pipeline\Source\FileResource;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\data_pipelines\Entity\DatasetInterface;
use Drupal\data_pipelines\Source\Resource\SourceResourceBase;
use Drupal\file\FileInterface;

/**
 * A class for the file resource.
 */
final class PrivateFile extends SourceResourceBase {

  /**
   * {@inheritdoc}
   */
  public function getResource(DatasetInterface $dataset, string $field_name): mixed {
    if (!empty($file = self::getFieldValue($dataset, $field_name, 'entity'))) {
      assert($file instanceof FileInterface);
      return fopen($file->getFileUri(), 'r');
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getResourceBaseFieldDefinition(array $source_plugin_definition): BaseFieldDefinition {
    return BaseFieldDefinition::create('file')
      ->setLabel(new TranslatableMarkup('File'))
      ->setDescription(new TranslatableMarkup('The file for the dataset.'))
      ->setSetting('file_extensions', $source_plugin_definition['id'])
      ->setRequired(TRUE)
      ->setSetting('uri_scheme', 'private')
      ->setDisplayOptions('form', [
        'type' => 'file_generic',
      ]);
  }

}
