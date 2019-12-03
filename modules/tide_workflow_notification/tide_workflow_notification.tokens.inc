<?php

/**
 * @file
 * Tokens file.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function tide_workflow_notification_token_info() {
  $types['workflow-notification:recipient'] = [
    'name' => t('Workflow Notification Recipient'),
    'description' => t('Tokens related to Workflow Notification recipient.'),
    'type' => 'user',
  ];

  return [
    'types' => $types,
  ];
}

/**
 * Implements hook_tokens().
 */
function tide_workflow_notification_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];
  $token_service = \Drupal::token();
  $recipient = $data['workflow-notification:recipient'] ?? NULL;
  if ($type == 'workflow-notification') {
    $bubbleable_metadata->addCacheContexts(['user']);
    if ($entity_tokens = $token_service->findWithPrefix($tokens, 'recipient')) {
      $replacements += $token_service->generate('user', $entity_tokens, ['user' => $recipient], $options, $bubbleable_metadata);
    }
  }
  return $replacements;
}
