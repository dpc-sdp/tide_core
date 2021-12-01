<?php

namespace Drupal\tide_core;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Class EntityAutocomplete.
 *
 * @package Drupal\tide_core
 */
class TideEntityAutocomplete {

  const MODERATION_STATE_RANKS = [
    'published' => 0,
    'editorial' => 1,
    'feedback'  => 2,
    'draft'     => 3,
    'archived'  => 4,
  ];

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
    if (!$a->hasField('moderation_state') || !$b->hasField('moderation_state')) {
      return 0;
    }
    $rank_a = self::MODERATION_STATE_RANKS[$a->get('moderation_state')->value];
    $rank_b = self::MODERATION_STATE_RANKS[$b->get('moderation_state')->value];
    return $rank_a > $rank_b;
  }

}
