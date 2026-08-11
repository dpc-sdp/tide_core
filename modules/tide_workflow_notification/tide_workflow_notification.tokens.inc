<?php

/**
 * @file
 * Tokens file.
 */

use Drupal\tide_workflow_notification\Hook\TideWorkflowNotificationHooks;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
#[\Drupal\Core\Hook\Attribute\LegacyHook]
function tide_workflow_notification_token_info() {
  return \Drupal::service(TideWorkflowNotificationHooks::class)->tokenInfo();
}

/**
 * Implements hook_tokens().
 */
#[\Drupal\Core\Hook\Attribute\LegacyHook]
function tide_workflow_notification_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  return \Drupal::service(TideWorkflowNotificationHooks::class)->tokens($type, $tokens, $data, $options, $bubbleable_metadata);
}
