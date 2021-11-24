<?php

namespace Drupal\tide_jira;

use Drupal\Core\Block\BlockManager;
use Drupal\node\NodeInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\tide_site\TideSiteHelper;

/**
 *
 */
class TideJiraAPI {

  const QUEUE_NAME = 'TIDE_JIRA';
  private $block_plugin_manager;
  private $queue_backend;
  private $site_helper;
  private $logger;
  private $entity_manager;
  private $date_formatter;
  private $preview_builder;
  private $preview_generator;

  /**
   *
   */
  public function __construct(BlockManager $block_plugin_manager, TideSiteHelper $site_helper, QueueFactory $queue_backend, EntityTypeManagerInterface $entity_manager, DateFormatter $date_formatter, LoggerChannelFactoryInterface $logger) {
    $this->block_plugin_manager = $block_plugin_manager;
    $this->preview_builder = $this->block_plugin_manager->createInstance('tide_site_preview_links_block');
    $this->preview_generator = new \ReflectionMethod($this->preview_builder, 'buildFrontendPreviewLink');
    $this->preview_generator->setAccessible(TRUE);
    $this->site_helper = $site_helper;
    $this->queue_backend = $queue_backend->get(self::QUEUE_NAME);
    $this->entity_manager = $entity_manager;
    $this->date_formatter = $date_formatter;
    $this->logger = $logger->get('tide_jira');
  }

  /**
   *
   */
  public function generateJiraRequest(NodeInterface $node) {
    $author = $this->getAuthorInfo($node);
    if (!empty($author)) {
      $revision = $this->getRevisionInfo($node);
      $summary = $this->getSummary($revision);
      $description = $this->templateDescription($author['name'], $author['email'], $author['department'], $revision['title'], $revision['id'], $revision['moderation_state'], $revision['bundle'], $revision['is_new'], $revision['updated_date'], $revision['notes'], $revision['preview_links']);
      $request = new TideJiraTicketModel($author['name'], $author['email'], $author['department'], $revision['title'], $summary, $revision['id'], $revision['moderation_state'], $revision['bundle'], $revision['is_new'], $revision['updated_date'], $author['account_id'], $description, $author['project'], $revision['preview_links']);
      $this->queue_backend->createItem($request);
      $this->logger->debug('Queued support request for user ' . $author['email'] . ' for page ' . $revision['title']);
    }
    else {
      $this->logger->notice('User ' . $node->getRevisionUser()->getEmail() . ' has no department/project set.');
    }
  }

  /**
   *
   */
  private function getPreviewLinks(NodeInterface $node, $stringify = FALSE) {
    $results = [];
    $sites = $this->site_helper->getEntitySites($node);
    $sites = $sites['ids'];

    foreach ($sites as $site_id) {
      $term = $this->entity_manager->getStorage('taxonomy_term')->load($site_id);
      $result = $this->preview_generator->invokeArgs($this->preview_builder, [
        $node,
        $term,
      ]);
      array_push($results, $result['url']->getUri());
    }

    if ($stringify) {
      $temp = '';
      foreach ($results as $key => $result) {
        if (!($key === array_key_last($results))) {
          $temp .= $result . ', ';
        }
        else {
          $temp .= $result;
        }
      }
      $results = $temp;
    }
    return $results;
  }

  /**
   *
   */
  private function getProjectInfo($tid) {
    $dept = $this->entity_manager->getStorage('taxonomy_term')->load($tid);
    return $dept->get('field_jira_project')->getValue()[0]['value'];
  }

  /**
   *
   */
  private function getSummary($revision) {
    $moderation_state = $revision['moderation_state'];
    if ($moderation_state == 'needs_review') {
      return 'Review of web content required: ' . $revision['title'];
    }
    else {
      return 'Archive of web content required: ' . $revision['title'];
    }
  }

  /**
   *
   */
  private function getRevisionInfo(NodeInterface $node) {
    return [
      'id' => $node->id(),
      'title' => $node->getTitle(),
      'bundle' => $node->getType(),
      'moderation_state' => $node->get('moderation_state')->value,
      'updated_date' => $this->date_formatter->format($node->get('changed')->value),
      'is_new' => $node->isNew() ? 'New page' : 'Content update',
      'notes' => $node->getRevisionLogMessage(),
      'preview_links' => $this->getPreviewLinks($node, TRUE),
    ];
  }

  /**
   *
   */
  private function getAuthorInfo(NodeInterface $node) {
    $result = [];
    if ($node->getRevisionUser()->get('field_department_agency')->first()) {
      $result = [
        'email' => $node->getRevisionUser()->getEmail(),
        'account_id' => '',
        'name' => $node->getRevisionUser()->get('name')->value . ' ' . $node->getRevisionUser()->get('field_last_name')->value,
        'department' => $this->entity_manager->getStorage('taxonomy_term')->load($node->getRevisionUser()->get('field_department_agency')->first()->getValue()['target_id'])->getName(),
        'project' => $this->getProjectInfo($node->getRevisionUser()->get('field_department_agency')->first() ? $node->getRevisionUser()->get('field_department_agency')->first()->getValue()['target_id'] : NULL),
      ];
    }
    return $result;
  }

  /**
   *
   */
  private function templateDescription($name, $email, $department, $title, $id, $moderation_state, $bundle, $is_new, $updated_date, $notes, $preview_links) {
    return <<<EOT
Hi Support,

This page is ready for review.

Editor information

Editor name:   $name

Editor email:   $email

Department:   $department

Page information

Page name:     $title

CMS URL:         https://content.vic.gov.au/node/$id

Status:             $moderation_state

Live URL:         $preview_links

Template:        $bundle

Revision:         $is_new

Date & time:   $updated_date

Notes: $notes

EOT;
  }

}
