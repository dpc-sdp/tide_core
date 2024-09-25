<?php

namespace Drupal\tide_alert;

use Drupal\Core\Field\BaseFieldDefinition;

/**
 * {@inheritdoc}
 *
 * @see https://www.drupal.org/node/2280639
 */
class TideAlertFieldStorageDefinition extends BaseFieldDefinition {

  /**
   * {@inheritdoc}
   */
  public function isBaseField() {
    return FALSE;
  }

}
