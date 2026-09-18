<?php

namespace Drupal\tide_external_site_link\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;
use Drupal\workflows\Entity\Workflow;

/**
 * Hook implementations for the tide external site link module.
 */
class TideExternalSiteLinkHooks {

  /**
   * Implements hook_entity_bundle_create().
   */
  #[Hook('entity_bundle_create')]
  public function entityBundleCreate($entity_type_id, $bundle): void {
    if ($entity_type_id == 'node' && $bundle == 'external_site_link') {
      // Enable Editorial workflow for external site link.
      $moduleHandler = \Drupal::service('module_handler');
      if ($moduleHandler->moduleExists('workflows')) {
        $editorial_workflow = Workflow::load('editorial');
        if ($editorial_workflow) {
          $editorial_workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'external_site_link');
          $editorial_workflow->save();
        }
      }
    }
  }

  /**
   * Implements hook_tide_site_preview_links_block_access_alter().
   *
   * Removes the preview links block from the external site link node type.
   */
  #[Hook('tide_site_preview_links_block_access_alter')]
  public function tideSitePreviewLinksBlockAccessAlter(AccessResultInterface &$access, NodeInterface $node): void {
    if ($node->bundle() === 'external_site_link') {
      $access = AccessResult::forbidden();
    }
  }

  /**
   * Implements hook_tide_share_link_form_revision_overview_form_access_alter().
   *
   * Removes the share link button from external site link node type revisions.
   */
  #[Hook('tide_share_link_form_revision_overview_form_access_alter')]
  public function tideShareLinkFormRevisionOverviewFormAccessAlter(AccessResultInterface &$access, NodeInterface $node): void {
    if ($node->bundle() === 'external_site_link') {
      $access = AccessResult::forbidden();
    }
  }

  /**
   * Implements hook_form_BASE_FORM_ID_alter().
   *
   * Remove fields from external site link node form that are irrelevant.
   */
  #[Hook('form_node_form_alter')]
  public function formNodeFormAlter(&$form, FormStateInterface $form_state, $form_id): void {
    if ($form_id === 'node_external_site_link_form' || $form_id === 'node_external_site_link_edit_form') {
      // Promotion to front page.
      unset($form['promote']);
      // Sticky in lists.
      unset($form['sticky']);
      // Menu settings.
      unset($form['menu']);
      // URL alias.
      unset($form['path']);
      // URL redirects.
      unset($form['url_redirects']);
      // Simple XML Sitemap.
      unset($form['simple_sitemap']);
      // Preview button.
      unset($form['actions']['preview']);

      $form['actions']['submit']['#submit'][] = 'tide_external_site_link_node_form_submit';
    }
  }

}
