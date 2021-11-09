<?php

namespace Drupal\tide_jira;

use Drupal\Core\Cache\Cache;
use Drupal\node\NodeInterface;
use Drupal\jira_rest\JiraRestWrapperService;
use JiraRestApi\Issue\IssueField;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

class TideJiraAPI {

  const QUEUE_NAME = 'TIDE_JIRA';
  private $jira_rest_wrapper_service;
  private $queue_backend;
  private $messenger;
  private $cache;
  private $logger;

  public function __construct(JiraRestWrapperService $jira_rest_wrapper_service, QueueFactory  $queue_backend, Messenger $messenger, CacheBackendInterface $cache, LoggerChannelFactoryInterface $logger) {
    $this->jira_rest_wrapper_service = $jira_rest_wrapper_service;
    $this->queue_backend = $queue_backend->get(self::QUEUE_NAME);
    $this->messenger = $messenger;
    $this->cache = $cache;
    $this->logger = $logger->get('tide_jira');
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
    $request = new TideJiraTicketModel($author['name'], $author['email'], $author['department'], $revision['title'], $summary, $revision['id'], $revision['moderation_state'], $revision['bundle'], $revision['is_new'], $revision['updated_date'], $author['account_id'], $description);
    $this->queue_backend->createItem($request);
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
      'account_id' => '',
      'name' => $node->getRevisionUser()->get('name')->value . ' ' . $node->getRevisionUser()->get('field_last_name')->value,
      'department' => $node->getRevisionUser()->get('field_department_agency')->value ?: '',
    ];
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
