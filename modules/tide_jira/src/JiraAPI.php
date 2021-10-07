<?php

namespace Drupal\tide_jira;

use Drupal\node\NodeInterface;
use JiraRestApi\Issue\IssueField;
use Drupal\jira_rest\JiraRestWrapperService;

class JiraAPI {

  private $jira_rest_wrapper_service;
  private $user_list;

  public function __construct(JiraRestWrapperService $jira_rest_wrapper_service) {
    $this->jira_rest_wrapper_service = $jira_rest_wrapper_service;

    // This should be queried from the JIRA API
    $this->user_list = [
      'administrator1.test@example.com' => 'qm:65dc928f-4371-4874-a86e-2398b4bdc099:d4f24b8a-ae25-49d4-8a89-f14cd27b2aff',
      'administrator2.test@example.com' => 'qm:65dc928f-4371-4874-a86e-2398b4bdc099:972803c0-31a3-4436-b951-b12a30d2464d',
      'approver1.test@example.com' => 'qm:65dc928f-4371-4874-a86e-2398b4bdc099:097cafeb-f961-4a91-9dbf-85b47f809ccc',
      'approver2.test@example.com' => 'qm:65dc928f-4371-4874-a86e-2398b4bdc099:ec892f92-898a-4086-ba24-dd22f6b666dd',
      'editor1.test@example.com' => 'qm:65dc928f-4371-4874-a86e-2398b4bdc099:c1db908a-0499-4aad-a11f-873c3b4cae57',
      'editor2.test@example.com' => 'qm:65dc928f-4371-4874-a86e-2398b4bdc099:84a5be1e-6396-459c-9fa7-e4970dcd26cd',
    ];
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
    $this->createTicket($summary, $author['email'], $description);
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
      'name' => $node->getRevisionUser()->get('name')->value . ' ' . $node->getRevisionUser()->get('field_last_name')->value,
      'department' => $node->getRevisionUser()->get('field_department_agency')->value,
    ];
  }

  private function createTicket($title, $email, $description) {
    $issueField = new IssueField();
    $issueField->setProjectKey("SFP")
      ->setSummary($title)
      ->setIssueType("Service Request")
      ->setReporterName($email)
      ->setReporterAccountId($this->user_list[$email])
      ->setDescription($description);

    // CAUTION
    // HANDLE JIRA API ERRORS PROPERLY
    $link = $this->jira_rest_wrapper_service->getIssueService()->create($issueField);
    $link = print_r($link, TRUE);
    \Drupal::logger('tide_jira')->info($link);
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