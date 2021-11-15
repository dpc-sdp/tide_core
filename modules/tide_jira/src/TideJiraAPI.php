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
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

class TideJiraAPI {

  const QUEUE_NAME = 'TIDE_JIRA';
  private $queue_backend;
  private $logger;
  private $entity_manager;
  public function __construct(QueueFactory  $queue_backend, EntityTypeManagerInterface $entity_manager, LoggerChannelFactoryInterface $logger) {
    $this->queue_backend = $queue_backend->get(self::QUEUE_NAME);
    $this->entity_manager = $entity_manager;
    $this->logger = $logger->get('tide_jira');
  }

  public function generateJiraRequest(NodeInterface $node) {
    $revision = $this->getRevisionInfo($node);
    $author = $this->getAuthorInfo($node);
    $summary = $this->getSummary($revision);
    $description = $this->templateDescription($author['name'], $author['email'], $author['department'], $revision['title'], $revision['id'], $revision['moderation_state'], $revision['bundle'], $revision['is_new'], $revision['updated_date']);
    $request = new TideJiraTicketModel($author['name'], $author['email'], $author['department'], $revision['title'], $summary, $revision['id'], $revision['moderation_state'], $revision['bundle'], $revision['is_new'], $revision['updated_date'], $author['account_id'], $description, $author['project']);
    $this->queue_backend->createItem($request);
    $this->logger->debug('Queued support request for user ' . $author['email'] . ' for page ' . $revision['title']);
  }

  private function getProjectInfo($tid) {
    $dept = $this->entity_manager->getStorage('taxonomy_term')->load($tid);
    return $dept->get('field_jira_project')->getValue()[0]['value'];
  }

  private function getSummary($revision) {
    $moderation_state = $revision['moderation_state'];
    if ($moderation_state == 'needs_review') {
      return 'Review of web content required: ' . $revision['title'];
    } else {
      return 'Archive of web content required: ' . $revision['title'];
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
      'account_id' => '',
      'name' => $node->getRevisionUser()->get('name')->value . ' ' . $node->getRevisionUser()->get('field_last_name')->value,
      'department' => $node->getRevisionUser()->get('field_department_agency')->value ?: '',
      'project' => $this->getProjectInfo($node->getRevisionUser()->get('field_department_agency')->first()->getValue()['target_id']),
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
