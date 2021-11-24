<?php

namespace Drupal\jira_rest;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use JiraRestApi\JiraException;

/**
 * Provides a repository for Key configuration entities.
 */
class JiraEndpointRepository implements JiraEndpointRepositoryInterface {
  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new KeyRepository.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getEndpoints(array $endpoint_ids = NULL) {
    return $this->entityTypeManager->getStorage('jira_endpoint')->loadMultiple($endpoint_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function getEndpoint($endpoint_id) {
    return $this->entityTypeManager->getStorage('jira_endpoint')->load($endpoint_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getEndpointNamesAsOptions() {
    $options = [];
    $endpoints = $this->getEndpoints();

    foreach ($endpoints as $endpoint) {
      $endpoint_id = $endpoint->id();
      $endpoint_title = $endpoint->label();
      $options[$endpoint_id] = (string) $endpoint_title;
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultEndpoint() {
    /** @var integer $endpoint_total */
    $endpoint_total = \Drupal::entityQuery('jira_endpoint')->count()->execute();
    if ($endpoint_total == 1) {
      $endpoint_id = \Drupal::entityQuery('jira_endpoint')->execute();
      return $this->getEndpoint(array_shift($endpoint_id));
    }
    elseif ($endpoint_total == 0) {
      return FALSE;
    }
    else {
      throw new JiraException($this->t("Multiple Endpoints were found. No Default Exists."));
    }
  }

}
