<?php

namespace Drupal\tide_ui_restriction\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Check if the current user has the 'access admin_config pages' permission.
 *
 * @package Drupal\tide_ui_restriction\Access
 */
class AdminConfigAccessCheck implements AccessInterface {

  /**
   * Check if the current user has the 'access admin_config pages' permission.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'access admin_config pages');
  }

}
