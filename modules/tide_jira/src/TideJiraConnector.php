<?php

namespace Drupal\tide_jira;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\jira_rest\JiraRestWrapperService;
use JiraRestApi\Issue\IssueField;

class TideJiraConnector {

  private $jira_rest_wrapper_service;
  private $cache;
  private $logger;
  public function __construct(JiraRestWrapperService $jira_rest_wrapper_service, CacheBackendInterface $cache, LoggerChannelFactoryInterface $logger) {
    $this->logger = $logger->get('tide_jira');
    $this->jira_rest_wrapper_service = $jira_rest_wrapper_service;
    $this->cache = $cache;
  }

  private function getUserCid($email) {
    return 'tide_jira:jira_account_id:' . sha1($email);
  }

  public function getJiraAccountIdByEmail($email) {
    if($cache = \Drupal::cache('data')->get($this->getUserCid($email))) {
      return $cache->data['account_id'];
    } else {
      $us = $this->jira_rest_wrapper_service->getUserService();
      $user = $us->findUserByEmail($email);

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

  public function createTicket($title, $email, $account_id, $description) {
    $issueField = new IssueField();
    $issueField->setProjectKey("SFP")
      ->setSummary($title)
      ->setIssueType("Service Request")
      ->setReporterName($email)
      ->setReporterAccountId($account_id)
      ->setDescription($description);

    // CAUTION
    // HANDLE JIRA API ERRORS PROPERLY
    $link = $this->jira_rest_wrapper_service->getIssueService()->create($issueField);
    return $link->key;
  }
}
