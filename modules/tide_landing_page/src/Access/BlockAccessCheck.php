<?php

namespace Drupal\tide_landing_page\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Determines access for block layout based on roles.
 */
class BlockAccessCheck implements AccessInterface {

  /**
   * Checks access.
   *
   * @param Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return bool
   *   A \Drupal\Core\Access\AccessInterface constant value.
   */
  public function access(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'administer content types');
  }

}
