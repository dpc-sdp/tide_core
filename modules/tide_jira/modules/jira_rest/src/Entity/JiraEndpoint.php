<?php

namespace Drupal\jira_rest\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\jira_rest\JiraEndpointInterface;
use Drupal\key\Exception\KeyValueNotSetException;
use Drupal\key\KeyInterface;
use Drupal\key\Plugin\KeyPluginCollection;
use Drupal\key\Plugin\KeyProviderSettableValueInterface;

/**
 * Defines the Key entity.
 *
 * @ConfigEntityType(
 *   id = "jira_endpoint",
 *   label = @Translation("Jira Endpoint"),
 *   module = "jira_rest",
 *   handlers = {
 *     "list_builder" = "Drupal\jira_rest\Controller\JiraEndpointListBuilder",
 *     "form" = {
 *       "add" = "Drupal\jira_rest\Form\JiraEndpointAddForm",
 *       "edit" = "Drupal\jira_rest\Form\JiraEndpointEditForm",
 *       "delete" = "Drupal\jira_rest\Form\JiraEndpointDeleteForm"
 *     },
 *   },
 *   config_prefix = "jira_endpoint",
 *   admin_permission = "administer jira_rest",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "add-form" = "/admin/config/services/jira_rest/add",
 *     "edit-form" = "/admin/config/services/jira_rest/manage/{jira_endpoint}",
 *     "delete-form" = "/admin/config/services/jira_rest/manage/{jira_endpoint}/delete",
 *     "collection" = "/admin/config/services/jira_rest"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "instanceurl",
 *     "username",
 *     "password",
 *     "clone_issue_transititon_id",
 *     "resolve_issue_transition_id",
 *   }
 * )
 */
class JiraEndpoint extends ConfigEntityBase implements JiraEndpointInterface {

  /**
   * The Drupal JIRA Endpoint ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Endpoint label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Instance URL
   * @var string
   */
   protected $instanceurl;

   /**
    * JIRA Instance Username
    * @var string
    */
   protected $username;

   /**
    * JIRA Instance Password Key
    * @var \Drupal\key\KeyInterface
    */
   protected $password;

   /**
    * Close issue transition ID
    * @var integer
    */
   protected $close_issue_transition_id = 2;

   /**
    * Resolve Issue Transition ID
    * @var integer
    */
   protected $resolve_issue_transition_id = 5;

  /**
   * @inheritDoc
   */
  public function getInstanceUrl() {
    return $this->instanceurl;
  }

  /**
   * @inheritDoc
   */
  public function getUsername() {
    return $this->username;
  }

  /**
   * @inheritDoc
   */
  public function getPassword($key_entity_id = FALSE) {
    // For config forms, just pass back the key entity ID.
    if ($key_entity_id === TRUE) {
      return $this->password;
    }

    /** @var \Drupal\key\KeyRepositoryInterface $keyRepository */
    $keyRepository = \Drupal::service('key.repository');

    $key_entity = $keyRepository->getKey($this->password);
    if(!$key_entity) {
      throw new \Exception(
        $this->t('Entity Key not found with name: @key_id', ['@key_id' => $this->password])
      );
    }
    $key_values = $key_entity->getKeyValues();
    return array_shift($key_values);
  }

  /**
   * @inheritDoc
   */
  public function getCloseTransitionId() {
    return $this->close_issue_transition_id;
  }

  /**
   * @inheritDoc
   */
  public function getResolveTransitionId() {
    return $this->resolve_issue_transition_id;
  }
}
