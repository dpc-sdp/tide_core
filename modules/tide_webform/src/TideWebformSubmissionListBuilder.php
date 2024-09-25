<?php

namespace Drupal\tide_webform;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\webform\WebformSubmissionListBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a list controller for webform submission entity.
 *
 * @ingroup webform
 */
class TideWebformSubmissionListBuilder extends WebformSubmissionListBuilder {

  /**
   * Submission state starred.
   */
  const STATE_PROCESSED = 'processed';

  /**
   * Submission state unstarred.
   */
  const STATE_UNPROCESSED = 'unprocessed';

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    /** @var \Drupal\webform\TideWebformSubmissionListBuilder $instance */
    $instance = parent::createInstance($container, $entity_type);

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function getQuery($keys = '', $state = '', $source_entity = ''): QueryInterface {
    $query = parent::getQuery($keys, $state, $source_entity);
    switch ($state) {
      case static::STATE_PROCESSED:
        $query->condition('processed', 1);
        break;

      case static::STATE_UNPROCESSED:
        $query->condition('processed', 0);
        break;

    }
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $columns = $this->columns;
    $columns['processed'] = [
      'title' => $this->t('Export Status'),
    ];
    $columns['processed']['name'] = 'processed';
    $columns['processed']['format'] = 'value';
    $newOrder = ['processed' => $columns['processed']] + $columns;
    $this->columns = $newOrder;
    return parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRowColumn(array $column, EntityInterface $entity) {
    /** @var \Drupal\webform\WebformSubmissionInterface $entity */

    $name = $column['name'];

    switch ($name) {
      case 'processed':
        return $this->getProcessedValue($entity);

      default:
        return parent::buildRowColumn($column, $entity);

    }
  }

  /**
   * Custom function to get value of processed field.
   *
   * @param Drupal\Core\Entity\EntityInterface $entity
   *   The webform submission entity.
   */
  public function getProcessedValue(EntityInterface $entity) {
    /** @var \Drupal\webform\WebformSubmissionInterface $entity */

    $value = $entity->processed->value;

    switch ($value) {
      case '0':
        return 'New';

      case '1':
        return 'Exported';

      default:
        return 'New';

    }
  }

}
