<?php

namespace Drupal\tide_core\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Class TideCoreArchiveAction.
 *
 * @Action(
 *   id = "tide_core_archive_action",
 *   label = @Translation("Tide Core Archive Action"),
 *   type = "node",
 * )
 */
class TideCoreArchiveAction extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    if ($object->getEntityType() === 'node') {
      $access = $object->access('update', $account, TRUE)->andIf($object->status->access('edit', $account, TRUE));
      return $return_as_object ? $access : $access->isAllowed();
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $entity->set('moderation_state', 'archived');
    $entity->setPublished(FALSE);
    $entity->save();
  }

}
