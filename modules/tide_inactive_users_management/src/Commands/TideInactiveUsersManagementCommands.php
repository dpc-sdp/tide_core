<?php

namespace Drupal\tide_inactive_users_management\Commands;

use Drupal\block_inactive_users\InactiveUsersHandler;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\user\Entity\User;
use Drush\Commands\DrushCommands;

class TideInactiveUsersManagementCommands extends DrushCommands {

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $block_inactive_users;

  /**
   * Idle time.
   *
   * @var int
   */
  protected $idle_time;

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
  protected $include_never_accessed;

  /**
   * Exclude users with roles.
   *
   * @var array
   */
  protected $exclude_user_roles;

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
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $configFactory, InactiveUsersHandler $handler, LoggerChannelFactory $logger) {
    parent::__construct();
    $this->config = $configFactory;
    $this->blockUserhandler = $handler;
    $this->logger = $logger->get(InactiveUsersHandler::LOGGER_CHANNEL);
    $this->block_inactive_users = $this->config->get(InactiveUsersHandler::FORM_SETTINGS_CONFIG_OBJ_NAME);
    $this->idle_time = $this->block_inactive_users->get('block_inactive_users_idle_time');
    $this->include_never_accessed = $this->block_inactive_users->get('block_inactive_users_include_never_accessed');
    $this->exclude_user_roles = $this->block_inactive_users->get('block_inactive_users_exclude_roles');
  }

  /**
   * Notifies users.
   *
   * @command tide_inactive_users_management:notify
   * @aliases inactive-notify
   */
  public function notify() {
    $users = $this->getUsers();
    foreach ($users as $user) {
      $last_access = $user->getLastLoginTime();
      $current_time = time();
      if ($last_access != 0 && !$user->hasRole('administrator')) {
        if ($this->blockUserhandler->timestampdiff($last_access, $current_time) >= $this->idle_time) {
          $this->sendingEmail($user);
        }
      }
      if ($this->include_never_accessed == 1 && $last_access == 0) {
        if ($this->blockUserhandler->timestampdiff($user->getCreatedTime(), $current_time) >= $this->idle_time) {
          $this->blockUserhandler->sendingEmail($user);
        }
      }
    }
  }

  /**
   * Block users.
   *
   * @command tide_inactive_users_management:block
   * @aliases inactive-block
   */
  public function block() {
    $users = $this->getUsers();
    foreach ($users as $user) {
      $last_access = $user->getLastLoginTime();
      $current_time = time();
      if ($last_access != 0 && !$user->hasRole('administrator')) {
        if ($this->blockUserhandler->timestampdiff($last_access, $current_time) >= $this->idle_time + 1) {
          $user->block();
          $user->save();
        }
      }
      if ($this->include_never_accessed == 1 && $last_access == 0) {
        if ($this->blockUserhandler->timestampdiff($user->getCreatedTime(), $current_time) >= $this->idle_time + 1) {
          $user->block();
          $user->save();
        }
      }
    }
  }

  /**
   * Gets users.
   */
  public function getUsers() {
    $query = \Drupal::entityQuery('user')->condition('status', 1);
    if (!empty($this->exclude_user_roles)) {
      $query->condition('roles.target_id', $this->exclude_user_roles, 'NOT IN');
    }
    $user_ids = $query->execute();
    return User::loadMultiple($user_ids);
  }

  /**
   * Helper function to send emails.
   */
  public function sendingEmail(User $user, $sendmail = TRUE) {
    $this->logger->info($user->getAccountName() . ' has been notified.');
    $url = \Drupal::request()->getHost();
    if ($sendmail) {
      $this->blockUserhandler->mailUser($this->block_inactive_users
        ->get('block_inactive_users_from_email'),
        $user->getEmail(), $user->getAccountName(),
        $this->block_inactive_users
          ->get('block_inactive_users_email_subject'),
        $this->block_inactive_users
          ->get('block_inactive_users_email_content'),
        $url);
    }
    return $user;
  }

}
