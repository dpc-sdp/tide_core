<?php

namespace Drupal\tide_publication;

use Drupal\Core\Field\BaseFieldDefinition;

/**
 * {@inheritdoc}
 *
 * @see https://www.drupal.org/node/2280639
 */
class TidePublicationFieldStorageDefinition extends BaseFieldDefinition {

  /**
   * {@inheritdoc}
   */
  public function isBaseField() {
    return FALSE;
  }

}
