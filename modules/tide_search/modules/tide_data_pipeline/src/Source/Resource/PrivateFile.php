<?php

declare(strict_types=1);

namespace Drupal\tide_data_pipeline\Source\Resource;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\data_pipelines\Entity\DatasetInterface;
use Drupal\data_pipelines\Source\Resource\SourceResourceBase;
use Drupal\file\FileInterface;

/**
 * A class for the Privatefile.
 */
final class PrivateFile extends SourceResourceBase {

  /**
   * {@inheritDoc}
   */
  public function getResource(DatasetInterface $dataset, string $field_name) {
    if (!empty($file = self::getFieldValue($dataset, $field_name, 'entity'))) {
      assert($file instanceof FileInterface);

      // Check if the file is stored in the private file system.
      if ($file->getFileUri()) {
        // Ensure the file is part of the private files folder.
        $file_uri = $file->getFileUri();
        if (strpos($file_uri, 'private://') === 0) {
          return fopen($file_uri, 'r');
        }
      }
    }
    return NULL;
  }

  /**
   * {@inheritDoc}
   */
  public function getResourceBaseFieldDefinition(array $source_plugin_definition): BaseFieldDefinition {
    // Create the field definition for the file.
    $field_definition = BaseFieldDefinition::create('file')
      ->setLabel(new TranslatableMarkup('Private File'))
      ->setDescription(new TranslatableMarkup('The file for the dataset.'))
      ->setSetting('file_extensions', $source_plugin_definition['id'])
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'file_generic',
      ]);

    // Set the upload location to the private directory.
    $field_definition->setSetting('file_directory', 'private://');

    return $field_definition;
  }

}
