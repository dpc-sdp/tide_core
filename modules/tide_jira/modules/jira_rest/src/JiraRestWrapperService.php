<?php

namespace Drupal\jira_rest;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\key\KeyRepositoryInterface;
use JiraRestApi\Configuration\ArrayConfiguration;
use JiraRestApi\Issue\IssueService;
use JiraRestApi\JiraException;

/**
 * Class JiraRestWrapperService.
 *
 * @package Drupal\jira_rest
 */
class JiraRestWrapperService {
  use StringTranslationTrait;

  /**
   * The JIRA Endpoint Config Object.
   *
   * @var \Drupal\jira_rest\JiraEndpointRepositoryInterface
   */
  protected $endpointRepository;

  /**
   * The key repository.
   *
   * @var \Drupal\key\KeyRepositoryInterface
   */
  protected $keyRepository;

  /**
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $loggerRestJira;

  /**
   * JiraRestWrapper constructor.
   *
   * @param \Drupal\jira_rest\JiraEndpointRepositoryInterface $endpoint_repository
   *   JIRA Endpoint Repository service.
   * @param \Drupal\key\KeyRepositoryInterface $key_repository
   *   Key Repository service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   Logger Factory service.
   *
   * @throws \Exception
   */
  public function __construct(JiraEndpointRepositoryInterface $endpoint_repository, KeyRepositoryInterface $key_repository, LoggerChannelFactoryInterface $logger_factory, $endpoint_id = NULL) {
    $this->endpointRepository = $endpoint_repository;
    $this->keyRepository = $key_repository;
    $this->loggerRestJira = $logger_factory->get('jira_rest');
  }

  /**
   * @param \Drupal\jira_rest\JiraEndpointInterface $endpoint
   *
   * @return \JiraRestApi\Configuration\ArrayConfiguration
   *   An array of jira configuration settings.
   *
   * @throws \Exception
   */
  protected function getArrayConfiguration(JiraEndpointInterface $endpoint) {
    return new ArrayConfiguration(
      [
        'jiraHost' => $endpoint->getInstanceUrl(),
        'jiraUser' => $endpoint->getUsername(),
        'jiraPassword' => $endpoint->getPassword(),
      ]
    );
  }

  /**
   * Get the Issue service api.
   *
   * @return \JiraRestApi\Issue\IssueService
   *   Issue Service API.
   * @throws \JiraRestApi\JiraException
   * @throws \JsonMapper_Exception
   * @throws \Exception
   */
  public function getIssueService($endpoint_id = NULL) {
    // Attempt to get a specific endpoint
    if (!empty($endpoint_id) ) {
      $endpoint = $this->endpointRepository->getEndpoint($endpoint_id);
    }
    if (!isset($endpoint)) {
        $endpoint = $this->endpointRepository->getDefaultEndpoint();
    }

    if (empty($endpoint)) {
      throw new JiraException($this->t('No JIRA Endpoints could be found.'));
    }

    // Initialize the JIRA Issue Service
    try {
      $issueService = new IssueService($this->getArrayConfiguration($endpoint));
    } catch (JiraException $e) {
      $this->loggerRestJira->error($e->getMessage());
    }
    return $issueService;
  }
}
