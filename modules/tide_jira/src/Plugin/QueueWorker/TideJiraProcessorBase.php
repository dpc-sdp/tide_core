<?php

namespace Drupal\tide_jira\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\tide_jira\TideJiraAPI;
use Drupal\jira_rest\JiraRestWrapperService;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

abstract class TideJiraProcessorBase extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  protected $tide_jira;
  protected $jira_rest;
  protected $logger;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, $tide_jira, $jira_rest, $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->tide_jira = $tide_jira;
    $this->jira_rest = $jira_rest;
    $this->logger = $logger->get('tide_jira');
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('tide_jira.jira_api'),
      $container->get('jira_rest_wrapper_service'),
      $container->get('logger.factory'),
    );
  }

  protected function createTicket($ticket) {

  }

  public function processItem($ticket) {
    $this->logger->debug(print_r($ticket, TRUE));
  }
}
