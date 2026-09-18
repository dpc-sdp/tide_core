<?php

namespace Drupal\tide_jira\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\node\NodeInterface;

/**
 * Hook implementations for the tide jira module.
 */
class TideJiraHooks {

  /**
   * Implements hook_ENTITY_TYPE_insert().
   */
  #[Hook('node_insert')]
  public function nodeInsert(NodeInterface $node) {
    tide_jira_handle_save($node);
  }

  /**
   * Implements hook_ENTITY_TYPE_update().
   */
  #[Hook('node_update')]
  public function nodeUpdate(NodeInterface $node) {
    tide_jira_handle_save($node);
  }

}
