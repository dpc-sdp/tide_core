<?php

namespace Drupal\tide_core\Command;

use Drush\Commands\DrushCommands;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Database;
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
   * Constructs a AuditLogCleanupCommand object.
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
   *
   * @option force-cleanup Skip confirmation and run the cleanup immediately.
   */
  public function cleanupLogs($options = ['force-cleanup' => FALSE]) {
    // Check if the user passed the --force-cleanup option.
    if (!$options['force-cleanup']) {
      // If the force-cleanup flag isn't passed, ask for confirmation.
      $confirmation = $this->confirmCleanup();
      if (!$confirmation) {
        $this->output()->writeln('<comment>Cleanup operation cancelled.</comment>');
        return;
      }
    }
    $config = $this->configFactory->get('tide_core.settings');
    define('DEFAULT_LOG_RETENTION_DAYS', 30);
    $log_retention_days = $config->get('log_retention_days') ?: DEFAULT_LOG_RETENTION_DAYS;
    // Get current date and time.
    $current_time = new DateTime();
    $current_time->sub(new DateInterval("P{$log_retention_days}D"));
    $threshold_timestamp = $current_time->getTimestamp();
    // Connect to the database.
    $database = Database::getConnection();
    $deleted = $database->delete('admin_audit_trail')
      ->condition('created', $threshold_timestamp, '<')
      ->execute();

    // Output the result.
    $this->output()->writeln("Deleted $deleted log entries older than $log_retention_days days.");
    // Run a database optimization command to recover space.
    $this->optimizeDatabase();
  }

  /**
   * Ask for confirmation before proceeding with the cleanup.
   *
   * @return bool
   *   TRUE if the user confirms, FALSE if the user cancels.
   */
  private function confirmCleanup() {
    $question = 'Are you sure you want to delete log entries older than the configured retention period? (y/n): ';
    $confirmation = $this->io()->ask($question, 'n');
    $confirmation = strtolower($confirmation);
    // Return TRUE if the user answers 'y' or 'yes'.
    return in_array($confirmation, ['y', 'yes']);
  }

  /**
   * Run database optimization (optional).
   *
   * @return void
   *  TRUE write the message.
   */
  private function optimizeDatabase() {
    $database = Database::getConnection();
    $database->query('OPTIMIZE TABLE {admin_audit_trail}');
    $this->output()->writeln("Database optimized to recover space.");
  }
}
