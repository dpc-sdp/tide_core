<?php

namespace Drupal\tide_core;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Class EntityAutocomplete.
 *
 * @package Drupal\tide_core
 */
trait ContentEntitySortingTrait {

  /**
   * Sort function to be used in usort or uasort.
   *
   * Since the moderation_state field is not yet sortable when using Content
   * Moderation we still need a way to sort entities by state. This is one way
   * to do it.
   *
   * @see \usort()
   * @see \Drupal\tide_core\Plugin\EntityReferenceSelection\TideNodeSelection::getReferenceableEntities()
   */
  public function sortEntitiesByModerationState(ContentEntityInterface $a, ContentEntityInterface $b) {
    $sortModerationStateRanks = [
      'published' => 0,
      'editorial' => 1,
      'feedback'  => 2,
      'draft'     => 3,
      'archived'  => 4,
    ];

    if (!$a->hasField('moderation_state') || !$b->hasField('moderation_state')) {
      return 0;
    }
    $rank_a = $sortModerationStateRanks[$a->get('moderation_state')->value];
    $rank_b = $sortModerationStateRanks[$b->get('moderation_state')->value];
    return $rank_a > $rank_b;
  }

}
