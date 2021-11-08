<?php

namespace Drupal\jira_rest;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining an Jira Endpoint config entity.
 */
interface JiraEndpointInterface extends ConfigEntityInterface {
  /**
   * Gets the instance url of the endpoint.
   *
   * @return string
   *   The instance URL.
   */
  public function getInstanceUrl();

  /**
   * Returns the username for the JIRA instance.
   *
   * @return string
   *   The username.
   */
  public function getUsername();

  /**
   * Returns the password from the key entity.
   *
   * @return string
   *   The password.
   */
  public function getPassword();

  /**
   * Close issue transition ID
   *
   * @return integer
   *   The transition id to close an issue.
   */
  public function getCloseTransitionId();

  /**
   * Resolve issue transition ID
   *
   * @return integer
   *   The transition id to resolve an issue.
   */
  public function getResolveTransitionId();
}


