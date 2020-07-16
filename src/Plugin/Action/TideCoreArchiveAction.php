<?php

namespace Drupal\tide_core\Plugin\Action;

/**
 * Class TideCoreArchiveAction.
 *
 * @Action(
 *   id = "tide_core_archive_action",
 *   label = @Translation("Tide Core Archive Action"),
 *   type = "node",
 *   confirm_form_route_name = "tide_core.node.action_confirm"
 * )
 */
class TideCoreArchiveAction extends TideCoreBaseAction {

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $entities) {
    /** @var \Drupal\Core\Entity\EntityInterface[] $entities */
    $selection = [];
    foreach ($entities as $entity) {
      $langcode = $entity->language()->getId();
      $selection[$entity->id()][$langcode] = $langcode;
    }
    $this->tempStore->set($this->currentUser->id() . ':node', ['archive' => $selection]);
  }

}
