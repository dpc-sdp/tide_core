<?php

namespace Drupal\tide_jira;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\jira_rest\JiraRestWrapperService;
use JiraRestApi\Issue\IssueField;
use Drupal\Core\Config\ConfigFactory;

/**
 * Tide JIRA Connector class.
 */
class TideJiraConnector {

  /**
   * The JiraRestWrapper Service.
   *
   * @var \Drupal\jira_rest\JiraRestWrapperService
   */
  private $jiraRestWrapperService;

  /**
   * The cache bin.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  private $cache;

  /**
   * The logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  private $logger;

  /**
   * Tide Jira configuration.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  private $config;

  /**
   * Tide Jira Connector constructor.
   *
   * @param Drupal\jira_rest\JiraRestWrapperService $jira_rest_wrapper_service
   *   Jira Rest service.
   * @param Drupal\Core\Cache\CacheBackendInterface $cache
   *   Cache bin.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger.
   * @param Drupal\Core\Config\ConfigFactory $config_factory
   *   Tide Jira config.
   */
  public function __construct(JiraRestWrapperService $jira_rest_wrapper_service, CacheBackendInterface $cache, LoggerChannelFactoryInterface $logger, ConfigFactory $config_factory) {
    $this->logger = $logger->get('tide_jira');
    $this->jiraRestWrapperService = $jira_rest_wrapper_service;
    $this->cache = $cache;
    $this->config = $config_factory->get('tide_jira.settings');
  }

  /**
   * Generate user cid.
   *
   * @param string $email
   *   The email.
   *
   * @return string
   *   The CID.
   */
  public function getUserCid($email) {
    return 'tide_jira:jira_account_id:' . sha1($email);
  }

  /**
   * Lookup user ID in JIRA.
   *
   * @param string $email
   *   The email.
   *
   * @return mixed|null
   *   Account ID.
   *
   * @throws \JiraRestApi\JiraException
   */
  public function getJiraAccountIdByEmail($email) {
    if ($cache = $this->cache->get($this->getUserCid($email))) {
      return $cache->data['account_id'];
    }
    else {
      $us = $this->jiraRestWrapperService->getUserService();

      try {
        $user = $us->findUserByEmail($email);
      }
      catch (\Exception $e) {
        $this->logger->warning('Could not find JIRA account for ' . $email);
        return NULL;
      }

      $cached_data = [
        'account_id' => $user->accountId,
      ];
      $this->cache
        ->set($this->getUserCid($email),
          $cached_data,
          CacheBackendInterface::CACHE_PERMANENT,
          ['tide_jira:jira_account_ids']
            );
      return $user->accountId;
    }
  }

  /**
   * Create ticket in JIRA.
   *
   * @param string $title
   *   Ticket title.
   * @param string $email
   *   User email.
   * @param string $account_id
   *   Account ID from JIRA.
   * @param string $description
   *   Ticket description.
   * @param string $project
   *   The Jira project.
   *
   * @return string
   *   The ID of the created ticket.
   *
   * @throws \JiraRestApi\JiraException
   * @throws \JsonMapper_Exception
   */
  public function createTicket($title, $email, $account_id, $description, $project) {
    $request_type = strtolower($project) . '/' . $this->config->get('customer_request_type_id');
    $issueField = new IssueField();
    $issueField->setProjectKey($project)
      ->setSummary($title)
      ->setIssueType($this->config->get('issue_type'))
      ->addCustomField($this->config->get('customer_request_type_field_id'), $request_type)
      ->setReporterName($email)
      ->setReporterAccountId($account_id)
      ->setDescription($description);
    $link = $this->jiraRestWrapperService->getIssueService()->create($issueField);
    return $link->key;
  }

}
