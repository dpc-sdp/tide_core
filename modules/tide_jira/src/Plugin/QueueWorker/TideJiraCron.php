<?php

namespace Drupal\tide_jira\Plugin\QueueWorker;

/**
 * Process the Federated Publishing ticket queue.
 *
 * @QueueWorker(
 *   id = "tide_jira",
 *   title = @Translation("Tide Federated Publishing"),
 *   cron = {"time" = 15}
 * )
 */
class TideJiraCron extends TideJiraProcessorBase {}
