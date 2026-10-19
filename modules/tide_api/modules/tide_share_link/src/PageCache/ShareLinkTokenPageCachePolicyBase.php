<?php

namespace Drupal\tide_share_link\PageCache;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Share Link Token Page Cache Policy base class.
 *
 * @package Drupal\tide_share_link\PageCache
 */
abstract class ShareLinkTokenPageCachePolicyBase implements ShareLinkTokenPageCachePolicyInterface {

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * DisallowShareLinkTokenAuthorizationRequests constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity Type Manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler) {
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function isShareLinkTokenAuthorizationRequest(Request $request) : bool {
    $tokens = &drupal_static(__METHOD__);
    $token_header = trim($request->headers->get('X-Share-Link-Token', '', TRUE));
    if ($token_header) {
      if (isset($tokens[$token_header])) {
        return TRUE;
      }

      // Page cache policies run before Drupal normally loads procedural hook
      // files. Load them before the token storage triggers entity queries.
      // @see https://www.drupal.org/project/drupal/issues/3207813
      $this->moduleHandler->loadAll();

      /** @var \Drupal\tide_share_link\ShareLinkTokenStorageInterface $storage */
      $storage = $this->entityTypeManager->getStorage('share_link_token');
      $token = $storage->loadByToken($token_header);
      $tokens[$token_header] = $token && $token->isActive();
      return $tokens[$token_header];
    }
    return FALSE;
  }

}
