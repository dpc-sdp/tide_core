<?php

namespace Drupal\tide_core\Command;

use Drush\Commands\DrushCommands;
use Drupal\Core\Database\Database;
use DateInterval;
use DateTime;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Custom AuditLog Clean Drush command.
 */
class AuditLogCleanupCommand extends DrushCommands {

/**
   * The configuration service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a LogCleanupCommands object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration service.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * Removes log entries older than the configured retention period.
   *
   * @command tide_core:auditlog-cleanup
   * @aliases tcl
   * @description Cleans up audittrail logs older than the configured retention period.
   */
  public function cleanupLogs() {
    $config = $this->configFactory->get('tide_core.settings');
    $log_retention_days = $config->get('log_retention_days') ?: 30;

    // Get current date and time
    $current_time = new DateTime();
    $current_time->sub(new DateInterval("P{$log_retention_days}D"));
    $threshold_timestamp = $current_time->getTimestamp();
    // Connect to the database
    $database = Database::getConnection();
    $deleted = $database->delete('admin_audit_trail')
      ->condition('created', $threshold_timestamp, '<')
      ->execute();

    // Output the result
    $this->output()->writeln("Deleted $deleted log entries older than $log_retention_days days.");
    
    // Run a database optimization command to recover space
    $this->optimizeDatabase();
  }

  /**
   * Run database optimization (optional).
   *
   * @return void
   */
  private function optimizeDatabase() {
    $database = Database::getConnection();
    $database->query('OPTIMIZE TABLE {admin_audit_trail}');
    $this->output()->writeln("Database optimized to recover space.");
  }
}

