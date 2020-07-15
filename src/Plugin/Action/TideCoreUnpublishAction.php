<?php

namespace Drupal\tide_core\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Class TideCoreUnpublishAction.
 *
 * @Action(
 *   id = "tide_core_unpublish_action",
 *   label = @Translation("Tide Core unpublish Action"),
 *   type = "node",
 * )
 */
class TideCoreUnpublishAction extends ActionBase {

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
    $entity->set('moderation_state', 'draft');
    $entity->setPublished(FALSE);
    $entity->save();
  }

}
