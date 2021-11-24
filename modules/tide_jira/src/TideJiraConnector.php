<?php

namespace Drupal\tide_jira;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\jira_rest\JiraRestWrapperService;
use JiraRestApi\Issue\IssueField;
use Drupal\Core\Config\ConfigFactory;

/**
 *
 */
class TideJiraConnector {

  private $jira_rest_wrapper_service;
  private $cache;
  private $logger;
  private $config;

  /**
   *
   */
  public function __construct(JiraRestWrapperService $jira_rest_wrapper_service, CacheBackendInterface $cache, LoggerChannelFactoryInterface $logger, ConfigFactory $config_factory) {
    $this->logger = $logger->get('tide_jira');
    $this->jira_rest_wrapper_service = $jira_rest_wrapper_service;
    $this->cache = $cache;
    $this->config = $config_factory->get('tide_jira.settings');
  }

  /**
   *
   */
  private function getUserCid($email) {
    return 'tide_jira:jira_account_id:' . sha1($email);
  }

  /**
   *
   */
  public function getJiraAccountIdByEmail($email) {
    if ($cache = $this->cache->get($this->getUserCid($email))) {
      return $cache->data['account_id'];
    }
    else {
      $us = $this->jira_rest_wrapper_service->getUserService();

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
   *
   */
  public function createTicket($title, $email, $account_id, $description, $project) {
    // Move mappings to config.
    $request_type = strtolower($project) . '/' . $this->config->get('customer_request_type_id');
    $issueField = new IssueField();
    $issueField->setProjectKey($project)
      ->setSummary($title)
      ->setIssueType($this->config->get('issue_type'))
      ->addCustomField($this->config->get('customer_request_type_field_id'), $request_type)
      ->setReporterName($email)
      ->setReporterAccountId($account_id)
      ->setDescription($description);
    $link = $this->jira_rest_wrapper_service->getIssueService()->create($issueField);
    return $link->key;
  }

}
