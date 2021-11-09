<?php

namespace Drupal\tide_jira\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\tide_jira\TideJiraConnector;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

abstract class TideJiraProcessorBase extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  protected $tide_jira;
  protected $jira_rest;
  protected $logger;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, TideJiraConnector $tide_jira, LoggerChannelFactoryInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->tide_jira = $tide_jira;
    $this->logger = $logger->get('tide_jira');
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('tide_jira.jira_connector'),
      $container->get('logger.factory'),
    );
  }

  protected function createTicket($ticket) {
    $ticket->setAccountId($this->tide_jira->getJiraAccountIdByEmail($ticket->getEmail()));
    $this->tide_jira->createTicket($ticket->getName(), $ticket->getEmail(), $ticket->getAccountId(), $ticket->getDescription());
  }

  public function processItem($ticket) {
    $this->logger->debug(print_r($ticket, TRUE));
    $this->createTicket($ticket);
  }
}
