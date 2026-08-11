<?php

namespace Drupal\tide_workflow_notification\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Hook implementations for the tide workflow notification module.
 */
class TideWorkflowNotificationHooks {

  /**
   * Implements hook_form_FORM_ID_alter().
   */
  #[Hook('form_workflow_edit_form_alter')]
  public function formWorkflowEditFormAlter(array &$form, FormStateInterface $form_state, $form_id) {
    /** @var \Drupal\workflows\Form\WorkflowEditForm $form_object */
    $form_object = $form_state->getFormObject();
    $workflow = $form_object->getEntity();
    if ($workflow->id() != 'editorial') {
      return;
    }

    $form['notifications'] = [
      '#type' => 'fieldset',
      '#title' => t('Workflow Notifications'),
      '#open' => TRUE,
      '#collasible' => TRUE,
    ];

    $form['notifications']['tabs'] = [
      '#type' => 'vertical_tabs',
      '#open' => TRUE,
    ];

    $transitions = [
      'draft_needs_review' => t('Draft to Needs Review'),
      'draft_published' => t('Draft to Published'),
      'needs_review_draft' => t('Needs Review to Draft'),
      'needs_review_published' => t('Needs Review to Published'),
      'published_archived' => t('Published to Archived'),
      'draft_archive_pending' => t('Draft to Archive Pending'),
      'needs_review_archive_pending' => t('Needs Review to Archive Pending'),
      'published_archive_pending' => t('Published to Archive Pending'),
      'archive_pending_archived' => t('Archive Pending to Archived'),
    ];

    $settings = \Drupal::config('tide_workflow_notification.settings');
    $notification_values = $form_state->getValue('notifications', []);
    foreach ($transitions as $transition => $label) {
      $transition_settings = $settings->get('notifications.' . $transition) ?? [];
      $form['notifications'][$transition] = [
        '#type' => 'details',
        '#title' => $label,
        '#group' => 'tabs',
        '#parents' => ['notifications', $transition],
        '#tree' => TRUE,
      ];

      $form['notifications'][$transition]['enabled'] = [
        '#type' => 'checkbox',
        '#title' => t('Send notifications when a node is moved from @transition', [
          '@transition' => $label,
        ]),
        '#default_value' => $notification_values[$transition]['enabled'] ?? $transition_settings['enabled'] ?? FALSE,
      ];

      $form['notifications'][$transition]['subject'] = [
        '#type' => 'textfield',
        '#title' => t('Subject'),
        '#default_value' => $notification_values[$transition]['subject'] ?? $transition_settings['subject'] ?? '',
      ];

      $form['notifications'][$transition]['message'] = [
        '#type' => 'textarea',
        '#title' => t('Message'),
        '#rows' => 10,
        '#default_value' => $notification_values[$transition]['message'] ?? $transition_settings['message'] ?? '',
      ];

      $form['notifications'][$transition]['token_tree'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => ['node', 'user', 'workflow-notification:recipient'],
        '#show_restricted' => TRUE,
        '#weight' => 90,
      ];
    }

    $form['actions']['submit']['#submit'][] = 'tide_workflow_notification_form_workflow_edit_form_submit';
  }

  /**
   * Implements hook_ENTITY_TYPE_presave().
   */
  #[Hook('node_presave')]
  public function nodePresave(NodeInterface $node) {
    try {
      if ($node->isNew() || !$node->hasField('moderation_state') || empty($node->getLoadedRevisionId())) {
        return;
      }

      $settings = \Drupal::config('tide_workflow_notification.settings');
      $moderation_state = $node->get('moderation_state')->getString();

      // Load the last revision as $node->original is the published one.
      /** @var \Drupal\node\NodeInterface $last_revision */
      $last_revision = \Drupal::entityTypeManager()
        ->getStorage('node')
        ->loadRevision($node->getLoadedRevisionId());
      $last_moderation_state = $last_revision->get('moderation_state')
        ->getString();

      $transition = $last_moderation_state . '_' . $moderation_state;
      $transition_settings = $settings->get('notifications.' . $transition) ?? [];
      // Bail out early if the transition is not enabled for notification.
      if (empty($transition_settings['enabled']) || empty($transition_settings['subject']) || empty($transition_settings['message'])) {
        return;
      }

      $last_author = $last_revision->getRevisionUser();
      $recipients = [];
      switch ($transition) {
        // Only notify the last author.
        case 'needs_review_draft':
          $recipients[$last_author->id()] = $last_author;
          break;

        // Notify all Approvers.
        case 'draft_needs_review':
        case 'draft_archive_pending':
        case 'needs_review_archive_pending':
          $recipients = _tide_workflow_notification_get_node_approvers($node, $transition);
          break;

        // Notify all Approvers and the last author.
        case 'draft_published':
        case 'needs_review_published':
        case 'published_archived':
        case 'published_archive_pending':
        case 'archive_pending_archived':
          $recipients = _tide_workflow_notification_get_node_approvers($node, $transition);
          $recipients[$last_author->id()] = $last_author;
          break;
      }

      /** @var \Drupal\Core\Mail\MailManagerInterface $mailer */
      $mailer = \Drupal::service('plugin.manager.mail');
      $token = \Drupal::token();
      $token_data = [
        'node' => $last_revision,
        'user' => $node->getRevisionUser(),
      ];
      // Send a notification to all legit recipients.
      foreach ($recipients as $recipient) {
        // The recipient must have the update permission.
        if ($last_revision->access('update', $recipient)) {
          $token_data['workflow-notification:recipient'] = $recipient;
          $langcode = \Drupal::currentUser()->getPreferredLangcode();
          $params = [
            'user' => $node->getRevisionUser(),
            'node' => $node,
            'revision' => $last_revision,
            'subject' => $transition_settings['subject'],
            'message' => $transition_settings['message'],
          ];
          $params['subject'] = $token->replace($params['subject'], $token_data);
          $params['message'] = $token->replace($params['message'], $token_data);
          $mailer->mail('tide_workflow_notification', $transition, $recipient->getEmail(), $langcode, $params, NULL, TRUE);
        }
      }
    }
    catch (\Exception $exception) {
      tide_core_log_exception('tide_workflow_notification', $exception);
    }
  }

  /**
   * Implements hook_mail().
   */
  #[Hook('mail')]
  public function mail($key, &$message, $params) {
    $message['from'] = \Drupal::config('system.site')->get('mail');
    $message['subject'] = $params['subject'];
    $message['body'][] = $params['message'];
  }

  /**
   * Implements hook_form_BASE_FORM_ID_alter().
   */
  #[Hook('form_node_form_alter')]
  public function formNodeFormAlter(&$form, FormStateInterface $form_state, $form_id) {
    /** @var \Drupal\node\Entity\Node $node */
    $node = $form_state->getFormObject()->getEntity();
    if ($node->isNew() && $node->hasField('revision_log')) {
      $form['revision_log']['#access'] = FALSE;
    }
  }

  /**
   * Implements hook_token_info().
   */
  #[Hook('token_info')]
  public function tokenInfo() {
    $types = [];
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
  #[Hook('tokens')]
  public function tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
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

}
