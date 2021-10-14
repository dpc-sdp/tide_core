<?php

namespace Drupal\tide_block_inactive_users\Commands;

use Drupal\block_inactive_users\InactiveUsersHandler;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Queue\QueueFactory;
use Drupal\user\Entity\User;
use Drush\Commands\DrushCommands;

/**
 * Defines the drush commands for TideInactiveUsersManagementCommands.
 *
 * @package Drupal\tide_block_inactive_users\Commands
 */
class TideInactiveUsersManagementCommands extends DrushCommands {

  /**
   * Block inactive users.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $blockInactiveUsers;

  /**
   * Idle time.
   *
   * @var int
   */
  protected $idleTime;

  /**
   * Block user service.
   *
   * @var \Drupal\block_inactive_users\InactiveUsersHandler
   */
  protected $blockUserhandler;

  /**
   * Include users who have never logged in.
   *
   * @var bool
   */
  protected $includeNeverAccessed;

  /**
   * Exclude users with roles.
   *
   * @var array
   */
  protected $excludeUserRoles;

  /**
   * Logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannel|\Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Config service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * QueueInterface.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $queue;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $configFactory, InactiveUsersHandler $handler, LoggerChannelFactory $logger, QueueFactory $queueFactory) {
    parent::__construct();
    $this->config = $configFactory;
    $this->blockUserhandler = $handler;
    $this->logger = $logger->get(InactiveUsersHandler::LOGGER_CHANNEL);
    $this->blockInactiveUsers = $this->config->get(InactiveUsersHandler::FORM_SETTINGS_CONFIG_OBJ_NAME);
    $this->idleTime = $this->blockInactiveUsers->get('block_inactive_users_idle_time');
    $this->includeNeverAccessed = $this->blockInactiveUsers->get('block_inactive_users_include_never_accessed');
    $this->excludeUserRoles = $this->blockInactiveUsers->get('block_inactive_users_exclude_roles');
    $this->queue = $queueFactory->get('tide_block_inactive_users_queue');
  }

  /**
   * Notifies users.
   *
   * @command tide_block_inactive_users:notify
   * @aliases inactive-notify
   */
  public function notify() {
    $users = $this->getUsers();
    if ($users) {
      foreach ($users as $user) {
        $last_access = $user->getLastLoginTime();
        $current_time = time();
        if ($last_access != 0 && !$user->hasRole('administrator')) {
          if ($this->blockUserhandler->timestampdiff($last_access, $current_time) >= $this->idleTime) {
            // Ensure the email only send once.
            if (!\Drupal::keyValue('tide_inactive_users_management')
              ->get($user->id())) {
              $item = new \stdClass();
              $item->uid = $user->id();
              $this->queue->createItem($item);
            }
          }
        }
        if ($this->includeNeverAccessed == 1 && $last_access == 0) {
          if ($this->blockUserhandler->timestampdiff($user->getCreatedTime(), $current_time) >= $this->idleTime) {
            if (!\Drupal::keyValue('tide_inactive_users_management')
              ->get($user->id())) {
              $item = new \stdClass();
              $item->uid = $user->id();
              $this->queue->createItem($item);
            }
          }
        }
      }
    }
  }

  /**
   * Block users.
   *
   * @command tide_block_inactive_users:block
   * @aliases inactive-block
   */
  public function block() {
    $tide_inactive_users_management_results = \Drupal::keyValue('tide_inactive_users_management');
    if ($times = $tide_inactive_users_management_results->getAll()) {
      foreach ($times as $uid => $time) {
        $user = User::load($uid);
        if ($user && time() > $time) {
          $user->block();
          $user->save();
          \Drupal::keyValue('tide_inactive_users_management')
            ->delete($user->id());
        }
      }
    }
  }

  /**
   * Gets users.
   */
  public function getUsers() {
    $query = \Drupal::entityQuery('user')->condition('status', 1);
    if (!empty($this->excludeUserRoles)) {
      $query->condition('roles.target_id', $this->excludeUserRoles, 'NOT IN');
    }
    $user_ids = $query->execute();
    if ($user_ids) {
      return User::loadMultiple($user_ids);
    }
    return [];
  }

}
