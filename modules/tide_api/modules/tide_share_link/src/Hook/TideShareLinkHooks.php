<?php

namespace Drupal\tide_share_link\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\tide_share_link\ShareLinkTokenSharedNodeFieldItemList;

/**
 * Hook implementations for the tide share link module.
 */
class TideShareLinkHooks {

  /**
   * Implements hook_entity_extra_field_info().
   *
   * Declare the API Info block in display view of Share Link Token entity.
   */
  #[Hook('entity_extra_field_info')]
  public function entityExtraFieldInfo() {
    $extra = [];
    $extra['share_link_token']['share_link_token']['display']['api_info'] = [
      'label' => t('API Information'),
      'description' => t('API Information'),
      'weight' => 100,
      'visible' => TRUE,
    ];
    return $extra;
  }

  /**
   * Implements hook_entity_bundle_field_info().
   *
   * Declare the computed field shared_node for Share Link Token entity.
   */
  #[Hook('entity_bundle_field_info')]
  public function entityBundleFieldInfo(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
    $fields = [];
    if ($entity_type->id() !== 'share_link_token') {
      return $fields;
    }

    $fields['shared_node'] = BaseFieldDefinition::create('entity_reference_revisions')
      ->setLabel(t('Shared node'))
      ->setDescription(t('The shared node'))
      ->setSetting('target_type', 'node')
      ->setSetting('handler', 'default:node')
      ->setComputed(TRUE)
      ->setReadOnly(FALSE)
      ->setInternal(FALSE)
      ->setTargetEntityTypeId($entity_type->id())
      ->setClass(ShareLinkTokenSharedNodeFieldItemList::class)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_revisions_label_revision_number',
        'weight' => -5,
        'settings' => [
          'link' => TRUE,
          'show_revision_number' => TRUE,
        ],
      ])
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * Implements hook_form_FORM_ID_alter().
   *
   * Add Share links to Node Revision Overview form.
   *
   * @see \Drupal\diff\Form\RevisionOverviewForm::buildForm()
   */
  #[Hook('form_revision_overview_form_alter')]
  public function formRevisionOverviewFormAlter(&$form, FormStateInterface $form_state, $form_id) {
    // Determine if the current user can create share link tokens.
    $account = \Drupal::currentUser();
    $has_administer_token_perm = $account->hasPermission('administer share link token entities');
    $has_add_token_perm = $account->hasPermission('add share link token entities');
    if (!$has_administer_token_perm && !$has_add_token_perm) {
      return;
    }

    if (!isset($form['node_revisions_table'])) {
      return;
    }

    // Load the current node.
    $node = \Drupal::routeMatch()->getParameter('node');
    if ($node && !($node instanceof NodeInterface)) {
      $node = Node::load((int) $node);
    }
    if (!$node) {
      return;
    }

    // Allow 'removing' the create share link button for revisions.
    $access = AccessResult::allowed();
    \Drupal::moduleHandler()->alter('tide_share_link_form_revision_overview_form_access', $access, $node);

    if ($access->isForbidden()) {
      return;
    }

    foreach (Element::children($form['node_revisions_table']) as $delta) {
      // The current revision.
      if (empty($form['node_revisions_table'][$delta]['operations']['#type'])) {
        $form['node_revisions_table'][$delta]['operations'] = [
          '#type' => 'operations',
          '#links' => [
            'share' => [
              'title' => t('Create a share preview link'),
              'url' => Url::fromRoute('share_link_token.share_node', [
                'node' => $node->id(),
              ]),
            ],
          ],
          '#prefix' => '<em>' . t('Current revision') . '</em>',
        ];
        continue;
      }

      // Attempt to retrieve the revision id.
      $vid = $form['node_revisions_table'][$delta]['select_column_one']['#return_value']
        ?? $form['node_revisions_table'][$delta]['select_column_two']['#return_value']
        ?? NULL;
      if ($vid) {
        $form['node_revisions_table'][$delta]['operations']['#links']['share'] = [
          'title' => t('Create a share preview link'),
          'url' => Url::fromRoute('share_link_token.share_node_revision', [
            'node' => $node->id(),
            'node_revision' => $vid,
          ]),
        ];
      }
    }
  }

  /**
   * Implements hook_node_delete().
   *
   * Delete all share link tokens of the deleted node.
   */
  #[Hook('node_delete')]
  public function nodeDelete(EntityInterface $entity) {
    try {
      /** @var \Drupal\node\NodeInterface $entity */
      /** @var \Drupal\tide_share_link\ShareLinkTokenStorageInterface $storage */
      $storage = \Drupal::entityTypeManager()->getStorage('share_link_token');
      $storage->deleteBySharedNodeId($entity->id());
    }
    catch (Exception $exception) {
      tide_core_log_exception('tide_share_link', $exception);
    }
  }

  /**
   * Implements hook_node_revision_delete().
   *
   * Delete all share link tokens of the deleted node revision.
   */
  #[Hook('node_revision_delete')]
  public function nodeRevisionDelete(EntityInterface $entity) {
    try {
      /** @var \Drupal\node\NodeInterface $entity */
      /** @var \Drupal\tide_share_link\ShareLinkTokenStorageInterface $storage */
      $storage = \Drupal::entityTypeManager()->getStorage('share_link_token');
      $storage->deleteBySharedNodeRevisionId($entity->getLoadedRevisionId());
    }
    catch (Exception $exception) {
      tide_core_log_exception('tide_share_link', $exception);
    }
  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme($existing, $type, $theme, $path) {
    return [
      'share_link_token' => [
        'render element' => 'elements',
      ],
    ];
  }

  /**
   * Implements hook_jsonapi_ENTITY_TYPE_filter_access().
   *
   * Deny access to Share Link Token via JSON:API filter.
   */
  #[Hook('jsonapi_share_link_token_filter_access')]
  public function jsonapiShareLinkTokenFilterAccess(EntityTypeInterface $entity_type, AccountInterface $account) {
    return [
      JSONAPI_FILTER_AMONG_ALL => AccessResult::forbidden(),
    ];
  }

  /**
   * Implements hook_cron().
   *
   * Cleanup expired tokens.
   */
  #[Hook('cron')]
  public function cron() {
    $config = \Drupal::config('tide_share_link.settings');
    $cleanup_in_cron = $config->get('cleanup_in_cron');
    if ($cleanup_in_cron) {
      /** @var \Drupal\tide_share_link\ShareLinkTokenStorageInterface $storage */
      $storage = \Drupal::entityTypeManager()->getStorage('share_link_token');
      $storage->deleteExpiredTokens();
    }
  }

  /**
   * Implements hook_admin_audit_trail_handlers().
   *
   * Add support for event log track.
   */
  #[Hook('admin_audit_trail_handlers')]
  public function adminAuditTrailHandlers() {
    return [
      'share_link_token' => ['title' => t('Share Link Token')],
    ];
  }

  /**
   * Implements hook_ENTITY_TYPE_insert().
   *
   * Log an event when a node is shared.
   */
  #[Hook('share_link_token_insert')]
  public function shareLinkTokenInsert(EntityInterface $entity) {
    if (!\Drupal::moduleHandler()->moduleExists('admin_audit_trail')) {
      return;
    }

    /** @var \Drupal\tide_share_link\Entity\ShareLinkTokenInterface $entity */
    $node = $entity->getSharedNode();

    if (\Drupal::moduleHandler()->moduleExists('admin_audit_trail_node')) {
      $log = [
        'type' => 'node',
        'operation' => 'share',
        'description' => t('%type: %title', [
          '%type' => $node ? $node->getType() : NULL,
          '%title' => $node ? $node->getTitle() : NULL,
        ]),
        'ref_numeric' => $node->id(),
        'ref_char' => $node->getTitle(),
      ];
      admin_audit_trail_insert($log);
    }

    $log = [
      'type' => 'share_link_token',
      'operation' => 'create',
      'description' => t('Node %type: %title (%nid - rev. %vid)', [
        '%type' => $node ? $node->getType() : NULL,
        '%title' => $node ? $node->getTitle() : NULL,
        '%nid' => $entity->getSharedNodeId(),
        '%vid' => $entity->getSharedNodeRevisionId() ?: t('current'),
      ]),
      'ref_numeric' => $entity->id(),
      'ref_char' => $entity->getName(),
    ];
    admin_audit_trail_insert($log);
  }

  /**
   * Implements hook_ENTITY_TYPE_update().
   *
   * Log an event when a share link token is updated.
   */
  #[Hook('share_link_token_update')]
  public function shareLinkTokenUpdate(EntityInterface $entity) {
    if (!\Drupal::moduleHandler()->moduleExists('admin_audit_trail')) {
      return;
    }

    /** @var \Drupal\tide_share_link\Entity\ShareLinkTokenInterface $entity */
    $log = [
      'type' => 'share_link_token',
      'operation' => 'update',
      'description' => t('Expiry: %expiry %expired - Status: %status', [
        '%expiry' => \Drupal::service('date.formatter')->format($entity->getExpiry(), 'short'),
        '%expired' => $entity->isExpired() ? t('(expired)') : '',
        '%status' => $entity->isActive() ? t('Active') : t('Revoked'),
      ]),
      'ref_numeric' => $entity->id(),
      'ref_char' => $entity->getName(),
    ];
    admin_audit_trail_insert($log);
  }

  /**
   * Implements hook_ENTITY_TYPE_delete().
   *
   * Log an event when a share link token is deleted.
   */
  #[Hook('share_link_token_delete')]
  public function shareLinkTokenDelete(EntityInterface $entity) {
    if (!\Drupal::moduleHandler()->moduleExists('admin_audit_trail')) {
      return;
    }

    /** @var \Drupal\tide_share_link\Entity\ShareLinkTokenInterface $entity */
    $node = $entity->getSharedNode();
    $log = [
      'type' => 'share_link_token',
      'operation' => 'delete',
      'description' => t('Node %type: %title (%nid - rev. %vid) - Expiry: %expiry%expired - Status: %status', [
        '%type' => $node ? $node->getType() : NULL,
        '%title' => $node ? $node->getTitle() : NULL,
        '%nid' => $entity->getSharedNodeId(),
        '%vid' => $entity->getSharedNodeRevisionId() ?: t('current'),
        '%expiry' => \Drupal::service('date.formatter')->format($entity->getExpiry(), 'short'),
        '%expired' => $entity->isExpired() ? t('(expired)') : '',
        '%status' => $entity->isActive() ? t('Active') : t('Revoked'),
      ]),
      'ref_numeric' => $entity->id(),
      'ref_char' => $entity->getName(),
    ];
    admin_audit_trail_insert($log);
  }

}
