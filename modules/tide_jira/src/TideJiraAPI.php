<?php

namespace Drupal\tide_jira;

use Drupal\node\NodeInterface;
use Drupal\jira_rest\JiraRestWrapperService;
use JiraRestApi\Issue\IssueField;

class TideJiraAPI {

  private $jira_rest_wrapper_service;

  public function __construct(JiraRestWrapperService $jira_rest_wrapper_service) {
    $this->jira_rest_wrapper_service = $jira_rest_wrapper_service;
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
    $this->createTicket($summary, $author['email'], $author['account_id'], $description);
  }

  private function getJiraAccountIdByEmail($email) {
    $us = $this->jira_rest_wrapper_service->getUserService();
    $user = $us->findUserByEmail($email);
    return $user->accountId;
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
    $link = print_r($link, TRUE);
    \Drupal::logger('vicgovau')->info($link);
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