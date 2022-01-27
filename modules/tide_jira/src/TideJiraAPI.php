<?php

namespace Drupal\tide_jira;

use Drupal\Core\Config\ConfigFactory;
use Drupal\node\NodeInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\tide_site_preview\TideSitePreviewHelper;
use Drupal\tide_site\TideSiteHelper;

/**
 * Tide Jira helper functions.
 */
class TideJiraAPI {

  /**
   * Name of the worker queue.
   */
  const QUEUE_NAME = 'TIDE_JIRA';
  /**
   * Block plugin manager.
   *
   * @var \Drupal\Core\Block\BlockManager
   */
  private $blockPluginManager;
  /**
   * Drupal queueing system.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  private $queueBackend;
  /**
   * Tide Site Helper.
   *
   * @var \Drupal\tide_site\TideSiteHelper
   */
  private $tideSiteHelper;
  /**
   * Drupal Logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  private $logger;
  /**
   * Drupal Entity Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;
  /**
   * Drupal DateTime Formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  private $dateFormatter;
  /**
   * Injected Tide Site Preview Links Plugin.
   *
   * @var \Drupal\tide_site_preview\TideSitePreviewHelper
   */
  private $tideSitePreviewHelper;
  /**
   * Drupal config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $config;

  /**
   * Instantiates a new TideJiraAPI.
   *
   * @param \Drupal\tide_site_preview\TideSitePreviewHelper $site_preview_helper
   *   Tide Site Preview Helper.
   * @param \Drupal\tide_site\TideSiteHelper $site_helper
   *   Tide Site Helper.
   * @param \Drupal\Core\Queue\QueueFactory $queue_backend
   *   Drupal Queue Factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   Drupal Entity Type Manager.
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *   Drupal Date Formatter.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   Drupal Logger Factory.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   Drupal config factory.
   */
  public function __construct(TideSitePreviewHelper $site_preview_helper, TideSiteHelper $site_helper, QueueFactory $queue_backend, EntityTypeManagerInterface $entity_manager, DateFormatter $date_formatter, LoggerChannelFactoryInterface $logger, ConfigFactory $config_factory) {
    $this->tideSitePreviewHelper = $site_preview_helper;
    $this->tideSiteHelper = $site_helper;
    $this->queueBackend = $queue_backend->get(self::QUEUE_NAME);
    $this->entityTypeManager = $entity_manager;
    $this->dateFormatter = $date_formatter;
    $this->logger = $logger->get('tide_jira');
    $this->config = $config_factory->get('tide_jira.settings');
  }

  /**
   * Extracts relevant metadata from a Node and queues a Jira ticket.
   *
   * @param \Drupal\node\NodeInterface $node
   *   A node entity.
   */
  public function generateJiraRequest(NodeInterface $node) {
    if (empty($node)) {
      throw new TideJiraException('Invalid node provided.');
    }

    $author = $this->getAuthorInfo($node);
    if (!empty($author)) {
      $revision = $this->getRevisionInfo($node);
      $summary = $this->getSummary($revision);
      $description = $this->templateDescription($author['name'], $author['email'], $author['department'], $revision['title'], $revision['id'], $revision['moderation_state'], $revision['bundle'], $revision['is_new'], $revision['updated_date'], $revision['notes'], $revision['preview_links']);
      $request = new TideJiraTicketModel($author['name'], $author['email'], $author['department'], $revision['title'], $summary, $revision['id'], $revision['moderation_state'], $revision['bundle'], $revision['is_new'], $revision['updated_date'], $author['account_id'], $description, $author['project'], $revision['preview_links']);
      $this->queueBackend->createItem($request);
      $this->logger->debug('Queued support request for user ' . $node->getRevisionUser()->getDisplayName() . ' for page ' . $revision['title']);
    }
    else {
      $this->logger->notice('User ' . $node->getRevisionUser()->getDisplayName() . ' has no project or department set.');
    }
  }

  /**
   * Generates frontend links for a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   * @param bool $stringify
   *   Whether to return an array or concatenated string.
   *
   * @return array|string
   *   Preview links.
   */
  private function getPreviewLinks(NodeInterface $node, $stringify = FALSE) {
    $results = [];
    $sites = $this->tideSiteHelper->getEntitySites($node);
    $sites = $sites['ids'];

    foreach ($sites as $site_id) {
      $term = $this->entityTypeManager->getStorage('taxonomy_term')->load($site_id);
      $result = $this->tideSitePreviewHelper->buildFrontendPreviewLink($node, $term);
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
   * Returns field_jira_project for a taxonomy.
   *
   * @param int $tid
   *   Taxonomy term ID.
   *
   * @return string
   *   Returns value of field_jira_project.
   */
  private function getProjectInfo($tid) {
    $dept = $this->entityTypeManager->getStorage('taxonomy_term')->load($tid);
    $project = $dept->get('field_jira_project')->getValue();
    $result = NULL;
    if ($project) {
      $result = $project[0]['value'];
    }
    return $result;
  }

  /**
   * Generates a ticket summary based on moderation state.
   *
   * @param string $revision
   *   The moderation state.
   *
   * @return string
   *   Ticket summary.
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
   * Generates an array with relevant page metadata.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   *
   * @return array
   *   Metadata for ticket creation.
   */
  private function getRevisionInfo(NodeInterface $node) {
    return [
      'id' => $node->id(),
      'title' => $node->getTitle(),
      'bundle' => $node->getType(),
      'moderation_state' => $node->get('moderation_state')->value,
      'updated_date' => $this->dateFormatter->format($node->get('changed')->value),
      'is_new' => $node->isNew() ? 'New page' : 'Content update',
      'notes' => $node->getRevisionLogMessage(),
      'preview_links' => $this->getPreviewLinks($node, TRUE),
    ];
  }

  /**
   * Generates an array with relevant author metadata.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   *
   * @return array
   *   Metadata for ticket creation.
   */
  private function getAuthorInfo(NodeInterface $node) {
    $result = [];
    if ($node->getRevisionUser()->get('field_department_agency')->first()) {
      $project = $node->getRevisionUser()->get('field_department_agency')->first() ? $this->getProjectInfo($node->getRevisionUser()->get('field_department_agency')->first()->getValue()['target_id']) : NULL;
      $email = $node->getRevisionUser()->getEmail() ? $node->getRevisionUser()->getEmail() : $this->config->get('no_account_email');
      if ($project) {
        $result = [
          'email' => $email,
          'account_id' => '',
          'name' => $node->getRevisionUser()->get('name')->value . ' ' . $node->getRevisionUser()->get('field_last_name')->value,
          'department' => $this->entityTypeManager->getStorage('taxonomy_term')->load($node->getRevisionUser()->get('field_department_agency')->first()->getValue()['target_id'])->getName(),
          'project' => $project,
        ];
      }
    }
    return $result;
  }

  /**
   * Templates the ticket body.
   *
   * @param string $name
   *   User's name.
   * @param string $email
   *   User's email.
   * @param string $department
   *   User's department.
   * @param string $title
   *   Ticket title.
   * @param string $id
   *   Revision ID.
   * @param string $moderation_state
   *   Moderation state.
   * @param string $bundle
   *   Content type.
   * @param string $is_new
   *   Whether the page is new.
   * @param string $updated_date
   *   Updated date.
   * @param string $notes
   *   Revision log notes.
   * @param string $preview_links
   *   Frontend preview links.
   *
   * @return string
   *   Templated ticket body as a Heredoc.
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
