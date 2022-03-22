<?php

namespace Drupal\tide_core\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\tide_core\ContentEntitySortingTrait;
use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Plugin\EntityReferenceSelection\NodeSelection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides labels for the node entity type.
 *
 * @EntityReferenceSelection(
 *   id = "tide:node",
 *   label = @Translation("Tide node selection"),
 *   entity_types = {"node"},
 *   group = "tide",
 *   weight = 1,
 *   base_plugin_label = "Tide"
 * )
 */
class TideNodeSelection extends NodeSelection {

  use ContentEntitySortingTrait;

  /**
   * {@inheritdoc}
   *
   * @see DefaultSelection::getReferenceableEntities()
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0): array {
    $target_type = $this->getConfiguration()['target_type'];

    $query = $this->buildEntityQuery($match, $match_operator);
    if ($limit > 0) {
      $query->range(0, $limit);
    }

    $result = $query->execute();
    if (empty($result)) {
      return [];
    }

    $options = [];
    $entities = $this->entityTypeManager->getStorage($target_type)->loadMultiple($result);

    // Sort entities by moderation state. We cannot do this in the
    // buildEntityQuery() method by adding a sort because the moderation_state
    // field is not yet sortable.
    uasort($entities, [$this, 'sortEntitiesByModerationState']);

    /** @var \Drupal\node\Entity\Node $entity */
    foreach ($entities as $entity_id => $entity) {
      $bundle = $entity->bundle();

      // We only set the bundle string if there is more than 1 target bundle
      // or if we don't have our custom flag set.
      // @see EntityAutocompleteMatcher::getMatches()
      $bundle_str = '';
      if (empty($this->singleTargetBundle) || !$this->singleTargetBundle) {
        $bundle_str = '[' . strtoupper($bundle) . '] ';
      }

      $site_names = $this->getSitesName($entity);
      $status = $this->getEntityStatus($entity);

      if (!empty($site_names)) {
        $append_str = ' (' . strtoupper($status) . ' - ' . strtoupper($site_names) . ')';
      }
      else {
        $append_str = ' (' . strtoupper($status) . ')';
      }

      $options[$bundle][$entity_id] = $bundle_str . Html::escape($this->entityRepository->getTranslationFromContext($entity)->label()) . $append_str;
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);
    $query->sort('status', 'DESC');
    return $query;
  }

  /**
   * Get the status for an entity.
   *
   * Use Moderation State if that's being used for the entity, otherwise use
   * the core default status field.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity object.
   *
   * @return string
   *   The status.
   */
  protected function getEntityStatus(ContentEntityInterface $entity): string {
    $status = 'published';
    if ($entity->hasField('moderation_state') && !$entity->get('moderation_state')->isEmpty()) {
      $status = $entity->get('moderation_state')->value;
    }
    elseif ($entity->hasField('status')) {
      if ($entity->get('status')->value == '0') {
        $status = 'draft';
      }
    }

    return $status;
  }

  /**
   * Get all sites name for an entity.
   *
   * Trim down the sub domain in the site for site name
   * if the site name has domain name like covid.vic.gov.au.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity object.
   *
   * @return string
   *   The sites name.
   */
  protected function getSitesName(ContentEntityInterface $entity): string {
    $site_names = [];
    if ($entity->hasField('field_node_site')) {
      foreach ($entity->field_node_site as $reference) {
        if (strpos($reference->entity->name->value, '.')) {
          $site_names[] = substr($reference->entity->name->value, 0, strpos($reference->entity->name->value, '.'));
        }
        else {
          $site_names[] = $reference->entity->name->value;
        }
      }
    }
    if ($site_names) {
      return implode(', ', $site_names);
    }

    return '';
  }

}
