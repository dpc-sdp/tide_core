<?php

namespace Drupal\jira_rest\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a listing of JIRA Endpoints.
 *
 * @see \Drupal\jira_rest\Entity\JiraEndpoint
 */
class JiraEndpointListBuilder extends ConfigEntityListBuilder {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeInterface $entity_type, EntityTypeManagerInterface $entity_type_manager, EntityStorageInterface $storage) {
    parent::__construct($entity_type, $storage);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager'),
      $container->get('entity_type.manager')->getStorage($entity_type->id())
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('JIRA Endpoint');
    $header['instanceurl'] = [
      'data' => $this->t('Instance URL'),
      'class' => [RESPONSIVE_PRIORITY_MEDIUM],
    ];
    $header['username'] = [
      'data' => $this->t('Username'),
      'class' => [RESPONSIVE_PRIORITY_MEDIUM],
    ];

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $jira_endpoint \Drupal\jira_rest\Entity\JiraEndpoint */
    $jira_endpoint = $entity;

    $row['label'] = $jira_endpoint->label();
    $row['instanceurl'] = $jira_endpoint->getInstanceUrl();
    $row['username'] = $jira_endpoint->getUsername();

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    /* @var $jira_endpoint \Drupal\jira_rest\Entity\JiraEndpoint */
    $jira_endpoint = $entity;

    $operations = parent::getOperations($jira_endpoint);
    uasort($operations, '\Drupal\Component\Utility\SortArray::sortByWeightElement');

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['table']['#empty'] = $this->t('No JIRA Endpoints are available. <a href=":link">Create an endpoint</a>.', [':link' => Url::fromRoute('entity.jira_endpoint.add_form')->toString()]);
    return $build;
  }
}
