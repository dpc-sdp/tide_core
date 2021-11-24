<?php

namespace Drupal\jira_rest;

/**
 * Provides the interface for a repository of JIRA Endpoint entities.
 */
interface JiraEndpointRepositoryInterface {

  /**
   * Get JIRA Endpoint entities.
   *
   * @param array $endpoint_ids
   *   (optional) An array of entity IDs, or NULL to load all entities.
   *
   * @return \Drupal\jira_rest\Entity\JiraEndpoint[]
   *   An array of jira endpoint entities, indexed by ID. Returns an empty array if no
   *   matching entities are found.
   */
  public function getEndpoints(array $endpoint_ids = NULL);

  /**
   * Get a specific Jira Endpoint.
   *
   * @param string $endpoint_id
   *   The key ID to use.
   *
   * @return \Drupal\jira_rest\Entity\JiraEndpoint
   *   The JiraEndpoint object with the given id.
   */
  public function getEndpoint($endpoint_id);

  /**
   * Get an array of key names, useful as options in form fields.
   *
   * @return array
   *   An array of key names, indexed by id.
   */
  public function getEndpointNamesAsOptions();

  /**
   * Get default endpoint. Note, this only works if there is one endpoint.
   *
   * @return \Drupal\jira_rest\Entity\JiraEndpoint
   *   The JiraEndpoint object with the given id.
   *
   * @throws \JiraRestApi\JiraException
   */
  public function getDefaultEndpoint();

}
