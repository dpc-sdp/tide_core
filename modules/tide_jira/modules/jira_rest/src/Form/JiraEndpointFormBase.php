<?php

namespace Drupal\jira_rest\Form;

use DMore\ChromeDriver\HttpClient;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\jira_rest\Entity\JiraEndpoint;
use Drupal\key\KeyRepositoryInterface;
use Drupal\key\Plugin\KeyType\AuthenticationKeyType;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base form for Jira Endpoint add and edit forms.
 */
abstract class JiraEndpointFormBase extends EntityForm {

  /**
   * The key storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The original endpoint.
   *
   * @var \Drupal\jira_rest\Entity\JiraEndpoint|null
   *   The original entity or NULL if this is a new endpoint.
   */
  protected $originalJiraEndpoint = NULL;

  /**
   * Constructs a new jira endpoint form base.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $storage
   *   The Jira Endpoint storage.
   * @param \Drupal\key\KeyRepositoryInterface $key_repository
   *   The Key repository for passwords.
   * @param \GuzzleHttp\Client $http_client
   *   The Guzzle HTTP client for REST Requests.
   */
  public function __construct(ConfigEntityStorageInterface $storage, KeyRepositoryInterface $key_repository, Client $http_client) {
    $this->storage = $storage;
    $this->keyRepository = $key_repository;
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('jira_endpoint'),
      $container->get('key.repository'),
      $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /* @var $jira_endpoint \Drupal\jira_rest\Entity\JiraEndpoint */
    $jira_endpoint = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Instance Label'),
      '#maxlength' => 255,
      '#default_value' => $jira_endpoint->label(),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $jira_endpoint->id(),
      '#machine_name' => [
        'exists' => [$this->storage, 'load'],
      ],
      '#disabled' => !$jira_endpoint->isNew(),
    ];

    $form['instanceurl'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL of the JIRA instance'),
      '#default_value' => $jira_endpoint->getInstanceUrl(),
      '#description' => $this->t("Enter the URL of your JIRA instance (e.g. https://yourjira.com:8443)"),
      '#required' => TRUE,
    ];

    $form['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username of the default user to connect to JIRA'),
      '#default_value' => $jira_endpoint->getUsername(),
      '#description' => $this->t("Enter the username used as default to connect to you JIRA instance (e.g. admin)"),
    ];

    $form['password'] = [
      '#type' => 'key_select',
      '#title' => $this->t('Password Key of the default user to connect to JIRA'),
      '#default_value' => $jira_endpoint->getPassword(TRUE),
      '#required' => TRUE,
    ];

    $form['close_issue_transition_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('the default transition ID to close an issue'),
      '#default_value' => $jira_endpoint->getCloseTransitionId(),
      '#size' => 4,
      '#description' => $this->t("Enter the default transition ID to close an issue with jira_rest_closeissuefixed()"),
      '#required' => TRUE,
    ];

    $form['resolve_issue_transition_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('default transition ID to resolve an issue'),
      '#default_value' => $jira_endpoint->getResolveTransitionId(),
      '#size' => 4,
      '#description' => $this->t("Enter the default transition ID to resolve an issue with jira_rest_resolveissuefixed()"),
      '#required' => TRUE,
    ];

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $user_password_key = $form_state->getValue('password');
    /** @var \Drupal\key\KeyInterface $key_entity */
    $key_entity = $this->keyRepository->getKey($user_password_key);
    if (!$key_entity->getKeyType() instanceof AuthenticationKeyType) {
      $form_state->setErrorByName('password', $this->t('The key should be of Authentication type.'));
      return;
    }

    $key_values = $key_entity->getKeyValues();
    try {
      $this->httpClient->get($form_state->getValue('instanceurl') . '/rest/api/2/myself', [
        'auth' => [$form_state->getValue('username'),array_shift($key_values)]
      ]);
    }
    catch (RequestException $e) {
      $this->messenger()->addError($e->getMessage());
    }
    $this->messenger()->addStatus($this->t('Successfully retrieved API information.'));

    $formValues = $form_state->getValues();

    $jira_url = $formValues['instanceurl'];
    if ((strpos(strrev($jira_url), strrev('/')) === 0)) {
      $form_state->setErrorByName('instanceurl', $this->t('URL must not end with "/"'));
    }

    if (!is_numeric($formValues['close_issue_transition_id'])) {
      $form_state->setErrorByName('close_issue_transition_id', $this->t('Transition id must be a numeric value'));
    }

    if (!is_numeric($formValues['resolve_issue_transition_id'])) {
      $form_state->setErrorByName('resolve_issue_transition_id', $this->t('Transition id must be a numeric value'));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // Allow exceptions to percolate, per EntityFormInterface.
    $status = parent::save($form, $form_state);

    $t_args = ['%name' => $this->entity->label()];
    if ($status == SAVED_UPDATED) {
      $this->messenger()->addStatus($this->t('The JIRA Endpoint %name has been updated.', $t_args));
    }
    elseif ($status == SAVED_NEW) {
      $this->messenger()->addStatus($this->t('The JIRA Endpoint %name has been added.', $t_args));
    }
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    return $status;
  }

  /**
   * Returns the original JIRA endpoint entity.
   *
   * @return \Drupal\jira_rest\Entity\JiraEndpoint
   *   The original JIRA Endpoint entity.
   */
  public function getOriginalJiraEndpoint() {
    return $this->originalJiraEndpoint;
  }
}
