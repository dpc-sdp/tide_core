<?php

namespace Drupal\tide_jira\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\tide_jira\TideJiraConnector;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\Core\State\StateInterface;
use \Exception;

abstract class TideJiraProcessorBase extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  const RETRY_LIMIT = 3;
  protected $tide_jira;
  protected $jira_rest;
  protected $logger;
  protected $state;
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TideJiraConnector $tide_jira, LoggerChannelFactoryInterface $logger, StateInterface $state) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->tide_jira = $tide_jira;
    $this->logger = $logger->get('tide_jira');
    $this->state = $state;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('tide_jira.jira_connector'),
      $container->get('logger.factory'),
      $container->get('state'),
    );
  }

  protected function lookupAccount($ticket) {
    if (!$ticket->getAccountId()) {
      $account_id = $this->tide_jira->getJiraAccountIdByEmail($ticket->getEmail());
      if (!$account_id) {
        $ticket->setEmail('nojiraaccount@dpc.vic.gov.au');
        $ticket->setAccountId($this->tide_jira->getJiraAccountIdByEmail($ticket->getEmail()));
      } else {
        $ticket->setAccountId($account_id);
      }
    }
  }

  protected function createTicket($ticket) {
    $this->tide_jira->createTicket($ticket->getName(), $ticket->getEmail(), $ticket->getAccountId(), $ticket->getDescription(), $ticket->getProject());
  }

  public function processItem($ticket) {
    $retries = $this->state->get('tide_jira_current_retry_count') ?: 0;
    try {
      $this->lookupAccount($ticket);
      $this->createTicket($ticket);
    } catch (Exception $e) {
      $this->logger->error($e);
      if ($retries < self::RETRY_LIMIT) {
        $this->state->set('tide_jira_current_retry_count', $retries + 1);
        throw new SuspendQueueException();
      } else {
        $this->logger->error('Retry limit reached, giving up: ' . $ticket->getTitle());
      }
    }
    $this->state->set('tide_jira_current_retry_count', 0);
  }
}
