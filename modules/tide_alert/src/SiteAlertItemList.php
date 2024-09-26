<?php

namespace Drupal\tide_alert;

use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\node\Entity\Node;

/**
 * Class site alert item list.
 */
class SiteAlertItemList extends EntityReferenceFieldItemList {
  use ComputedItemListTrait;

  /**
   * The Entity Query.
   *
   * @var \Drupal\Core\Entity\Query\QueryInterface
   */
  protected $entityQuery;

  /**
   * The Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Returns the currently active global container.
   *
   * @return \Symfony\Component\DependencyInjection\ContainerInterface|null
   *   The container.
   *
   * @throws \Drupal\Core\DependencyInjection\ContainerNotInitializedException
   */
  public static function getContainer() {
    return \Drupal::getContainer();
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(DataDefinitionInterface $definition, $name = NULL, TypedDataInterface $parent = NULL) {
    parent::__construct($definition, $name, $parent);

    $container = static::getContainer();
    $this->entityQuery = \Drupal::entityQuery('node');
    $this->entityTypeManager = $container->get('entity_type.manager');
  }

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    $entity = $this->getEntity();
    if ($entity->getEntityTypeId() !== 'taxonomy_term' && $entity->bundle() != 'sites') {
      return;
    }

    try {
      $access_handler = $this->entityTypeManager->getAccessControlHandler('node');

      // Loads all published alerts associated with this Site.
      $query = $this->entityQuery
        ->condition('type', 'alert')
        ->condition('status', Node::PUBLISHED)
        ->condition('field_node_site', $entity->id())
        ->accessCheck(FALSE)
        ->sort('changed', 'DESC');

      $results = $query->execute();
      if (!empty($results)) {
        $weight = 0;
        foreach ($results as $nid) {
          /** @var \Drupal\node\NodeInterface $node */
          $node = $this->entityTypeManager->getStorage('node')->load($nid);
          if ($node) {
            if ($access_handler->access($node, 'view')) {
              $this->list[] = $this->createItem($weight++, ['target_id' => $nid]);
            }
          }
        }
      }
    }
    catch (\Exception $exception) {
      watchdog_exception('tide_alert', $exception);
    }

  }

}
