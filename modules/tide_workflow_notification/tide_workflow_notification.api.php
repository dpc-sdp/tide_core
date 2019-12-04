<?php

/**
 * @file
 * API.
 */

use Drupal\node\NodeInterface;

/**
 * Alter the recipients of a node.
 *
 * @param \Drupal\user\UserInterface[] $users
 *   List of recipients.
 * @param \Drupal\node\NodeInterface $node
 *   The node.
 * @param string $transition
 *   The transition.
 */
function hook_tide_workflow_notification_get_recipients_alter(array &$users, NodeInterface $node, string $transition) {
  $current_user = \Drupal::currentUser();
  $users[$current_user->id()] = $current_user;
}
