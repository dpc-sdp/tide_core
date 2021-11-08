<?php

namespace Drupal\jira_rest\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\key\KeyRepositoryInterface;
use Drupal\key\Plugin\KeyType\AuthenticationKeyType;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class JiraRestConfigForm.
 *
 * @package Drupal\jira_rest\Form
 */
class JiraRestConfigForm extends ConfigFormBase {

  /**
   * The key repository.
   *
   * @var \Drupal\key\KeyRepositoryInterface
   */
  protected $keyRepository;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * JiraServiceConfiguration constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\key\KeyRepositoryInterface $key_repository
   * @param \Drupal\Core\Messenger\Messenger $messenger
   * @param \GuzzleHttp\Client $http_client
   */
  public function __construct(ConfigFactoryInterface $config_factory, KeyRepositoryInterface $key_repository, Messenger $messenger, Client $http_client) {
    $this->keyRepository = $key_repository;
    $this->messenger = $messenger;
    $this->httpClient = $http_client;
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('key.repository'),
      $container->get('messenger'),
      $container->get('http_client')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'jira_rest_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Pretty sure this isn't needed...
    //$form = parent::buildForm($form, $form_state);

    $config = $this->config('jira_rest.settings');

    $form['instanceurl'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL of the JIRA instance'),
      '#default_value' => $config->get('instanceurl'),
      '#description' => $this->t("Enter the URL of your JIRA instance (e.g. https://yourjira.com:8443)"),
      '#required' => TRUE,
    ];

    $form['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username of the default user to connect to JIRA'),
      '#default_value' => $config->get('username'),
      '#description' => $this->t("Enter the username used as default to connect to you JIRA instance (e.g. admin)"),
    ];

    $form['password'] = [
      '#type' => 'key_select',
      '#title' => $this->t('Password Key of the default user to connect to JIRA'),
      '#default_value' => $config->get('password'),
      '#required' => TRUE,
    ];

    $form['close_issue_transition_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('the default transition ID to close an issue'),
      '#default_value' => $config->get('close_issue_transition_id'),
      '#size' => 4,
      '#description' => $this->t("Enter the default transition ID to close an issue with jira_rest_closeissuefixed()"),
      '#required' => TRUE,
    ];

    $form['resolve_issue_transition_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('default transition ID to resolve an issue'),
      '#default_value' => $config->get('resolve_issue_transition_id'),
      '#size' => 4,
      '#description' => $this->t("Enter the default transition ID to resolve an issue with jira_rest_resolveissuefixed()"),
      '#required' => TRUE,
    ];

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    );

    return $form;
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
      $this->messenger->addError($e->getMessage());
    }
    $this->messenger->addStatus($this->t('Successfully retrieved API information.'));

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
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $values = $form_state->getValues();
    $this->config('jira_rest.settings')
      ->set('instanceurl', $values['instanceurl'])
      ->set('username', $values['username'])
      ->set('password', $values['password'])
      ->set('close_issue_transition_id', $values['close_issue_transition_id'])
      ->set('resolve_issue_transition_id', $values['resolve_issue_transition_id'])
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'jira_rest.settings',
    ];
  }

}
