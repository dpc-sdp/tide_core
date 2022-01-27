<?php

namespace Drupal\tide_jira\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\tide_jira\TideJiraConnector;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\tide_jira\TideJiraTicketModel;

/**
 * Implements a queue worker for Tide Jira.
 */
abstract class TideJiraProcessorBase extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Number of times to retry creating a ticket before giving up.
   */
  const RETRY_LIMIT = 3;
  /**
   * The Jira API connector.
   *
   * @var Drupal\tide_jira\TideJiraConnector
   */
  protected $tideJiraConnector;
  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;
  /**
   * Drupal state interface.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;
  /**
   * Drupal config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $config;

  /**
   * Constructs a new TideJiraProcessorBase.
   *
   * @param array $configuration
   *   Site configuration.
   * @param string $plugin_id
   *   Plugin ID.
   * @param string $plugin_definition
   *   Plugin definition.
   * @param \Drupal\tide_jira\TideJiraConnector $tide_jira
   *   Instance of TideJiraConnector.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   Logger factory.
   * @param \Drupal\Core\State\StateInterface $state
   *   Drupal state interface.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   Drupal config factory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TideJiraConnector $tide_jira, LoggerChannelFactoryInterface $logger, StateInterface $state, ConfigFactory $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->tideJiraConnector = $tide_jira;
    $this->logger = $logger->get('tide_jira');
    $this->state = $state;
    $this->config = $config_factory->get('tide_jira.settings');
  }

  /**
   * Instantiate a new TideJiraProcessorBase via Dependency Injection.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Service container.
   * @param \array $configuration
   *   Site configuration.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Plugin definition.
   *
   * @return static
   *   Dependencies to instantiate a new TideJiraProcessorBase worker.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('tide_jira.jira_connector'),
      $container->get('logger.factory'),
      $container->get('state'),
      $container->get('config.factory'),
    );
  }

  /**
   * Looks up a Jira account.
   *
   * @param \Drupal\tide_jira\TideJiraTicketModel $ticket
   *   TideJiraTicketModel object.
   *
   * @throws \JiraRestApi\JiraException
   *   Raised when the Jira API returns a bad HTTP response.
   */
  protected function lookupAccount(TideJiraTicketModel $ticket) {
    if (!$ticket->getAccountId()) {
      $account_id = $this->tideJiraConnector->getJiraAccountIdByEmail($ticket->getEmail());
      if (!$account_id) {
        $ticket->setEmail($this->config->get('no_account_email'));
        $ticket->setAccountId($this->tideJiraConnector->getJiraAccountIdByEmail($ticket->getEmail()));
      }
      else {
        $ticket->setAccountId($account_id);
      }
    }
  }

  /**
   * Creates a ticket in Jira using the API.
   *
   * @param \Drupal\tide_jira\TideJiraTicketModel $ticket
   *   TideJiraTicketModel object.
   *
   * @throws \JiraRestApi\JiraException
   *   Raised when the Jira API returns a bad HTTP response.
   * @throws \JsonMapper_Exception
   *   Raised when the response from JIRA cannot be processed.
   */
  protected function createTicket(TideJiraTicketModel $ticket) {
    $this->tideJiraConnector->createTicket($ticket->getSummary(), $ticket->getEmail(), $ticket->getAccountId(), $ticket->getDescription(), $ticket->getProject());
  }

  /**
   * {@inheritDoc}
   */
  public function processItem($ticket) {
    $retries = $this->state->get('tide_jira_current_retry_count') ?: 0;
    try {
      $this->lookupAccount($ticket);
      $this->createTicket($ticket);
    }
    catch (\Exception $e) {
      $this->logger->error($e);
      if ($retries < self::RETRY_LIMIT) {
        $this->state->set('tide_jira_current_retry_count', $retries + 1);
        throw new SuspendQueueException();
      }
      else {
        $this->logger->error('Retry limit reached, giving up: ' . $ticket->getTitle());
      }
    }
    $this->state->set('tide_jira_current_retry_count', 0);
  }

}
