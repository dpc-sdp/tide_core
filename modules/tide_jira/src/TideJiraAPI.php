<?php

namespace Drupal\tide_jira;

use Drupal\node\NodeInterface;
use Drupal\jira_rest\JiraRestWrapperService;
use JiraRestApi\Issue\IssueField;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueInterface;
use Drupal\Core\Messenger\Messenger;

class TideJiraAPI {

  const QUEUE_NAME = 'TIDE_JIRA';
  private $jira_rest_wrapper_service;
  private $queue_backend;
  private $messenger;

  public function __construct(JiraRestWrapperService $jira_rest_wrapper_service, QueueFactory  $queue_backend, Messenger $messenger) {
    $this->jira_rest_wrapper_service = $jira_rest_wrapper_service;
    $this->queue_backend = $queue_backend;
    $this->messenger = $messenger;
  }

  public function createTicketFromNodeParameters(NodeInterface $node) {
    $revision = $this->getRevisionInfo($node);
    if ($revision['moderation_state'] == 'needs_review') {
      $summary = 'Review of web content required: ' . $revision['title'];
    } else if ($revision['moderation_state'] == 'archive_pending') {
      $summary = 'Archive of web content required: ' . $revision['title'];
    } else {
      return;
    }
    $author = $this->getAuthorInfo($node);
    $description = $this->templateDescription($author['name'], $author['email'], $author['department'], $revision['title'], $revision['id'], $revision['moderation_state'], $revision['bundle'], $revision['is_new'], $revision['updated_date']);
    $ticket = $this->createTicket($summary, $author['email'], $author['account_id'], $description);
    $this->messenger->addMessage(t('A content support request has been generated for you. Ref: ' . $ticket));
  }

  private function getUserCid($email) {
    return 'tide_jira:jira_account_id:' . sha1($email);
  }

  private function getJiraAccountIdByEmail($email) {
    if($cache = \Drupal::cache('data')->get($this->getUserCid($email))) {
      \Drupal::messenger()->addMessage(t('Cache HIT cid '. $this->getUserCid($email)));
      return $cache->data['account_id'];
    } else {
      \Drupal::messenger()->addMessage(t('Cache MISS cid '. $this->getUserCid($email)));
      $us = $this->jira_rest_wrapper_service->getUserService();
      $user = $us->findUserByEmail($email);

      $cached_data = [
        'account_id' => $user->accountId,
      ];
      \Drupal::cache('data')
        ->set($this->getUserCid($email),
          $cached_data,
          CacheBackendInterface::CACHE_PERMANENT,
          ['tide_jira:jira_account_ids']
        );
      return $user->accountId;
    }
  }

  private function getRevisionInfo(NodeInterface $node) {
    return [
      'id' => $node->id(),
      'title' => $node->getTitle(),
      'bundle' => $node->getType(),
      'moderation_state' => $node->get('moderation_state')->value,
      'updated_date' => $node->get('changed')->value,
      'is_new' => $node->isNew() ? 'New page' : 'Content update',
    ];
  }

  private function getAuthorInfo (NodeInterface $node) {
    return [
      'email' => $node->getRevisionUser()->getEmail(),
      'account_id' => $this->getJiraAccountIdByEmail($node->getRevisionUser()->getEmail()),
      'name' => $node->getRevisionUser()->get('name')->value . ' ' . $node->getRevisionUser()->get('field_last_name')->value,
      'department' => $node->getRevisionUser()->get('field_department_agency')->value,
    ];
  }

  private function createTicket($title, $email, $account_id, $description) {
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

  private function templateDescription($name, $email, $department, $title, $id, $moderation_state, $bundle, $is_new, $updated_date){
    return <<<EOT
Hi Support,

This page is ready for review.

Editor information (requester of the ticket)

Editor name:   $name

Editor email:   $email

Department:   $department

Page information

Page name:     $title

CMS URL:         https://content.vic.gov.au/node/$id

Status:             $moderation_state

Live URL:         [domain name on website if live]

Template:        $bundle

Revision:         $is_new

Date & time:   $updated_date

Notes: [Revision notes]

EOT;
  }
}
