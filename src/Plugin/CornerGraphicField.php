<?php

namespace Drupal\tide_core\Plugin;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Term;

/**
 * Corner Graphic field implementation.
 */
class CornerGraphicField extends FieldItemList {

  use ComputedItemListTrait;

  /**
   * Computes the field value.
   */
  protected function computeValue() {
    $node = $this->getEntity();
    if (!$node instanceof NodeInterface) {
      return;
    }
    if (!\Drupal::moduleHandler()->moduleExists('tide_site')) {
      return;
    }
    $site_id = \Drupal::request()->get('site');
    /** @var \Drupal\tide_site\TideSiteHelper $siteHelper */
    $siteHelper = \Drupal::service('tide_site.helper');
    // Return can be null or term entity.
    $primary_site_term_entity = $siteHelper->getEntityPrimarySite($node);
    $file_url_generator = \Drupal::service('file_url_generator');

    $sites = $siteHelper->getEntitySites($node);
    $valid = $siteHelper->isEntityBelongToSite($node, $site_id);

    if (!$valid || empty($sites['sections'][$site_id])) {
      return;
    }

    $section_id = $sites['sections'][$site_id];
    $details = [];
    // Try to get corner graphics from the node.
    if ($this->hasCornerGraphicsInNode($node)) {
      $this->addNodeCornerGraphics($node, $details, $file_url_generator);
    }
    // Fall back to section term corner graphics.
    else {
      $term = Term::load($section_id);
      if ($term && $this->hasCornerGraphicsInTerm($term)) {
        $this->addTermCornerGraphics($term, $details, $file_url_generator);
      }
      // Level 3: Fall back to primary site corner graphics.
      elseif ($primary_site_term_entity && $this->hasCornerGraphicsInTerm($primary_site_term_entity)) {
        $this->addTermCornerGraphics($primary_site_term_entity, $details, $file_url_generator);
      }
    }
    $item = $this->createItem(0, $details);
    $cacheability = (new CacheableMetadata())
      ->setCacheTags([
        'taxonomy_term:' . $section_id,
        'taxonomy_term:' . $primary_site_term_entity->id(),
        'node:' . $node->id(),
      ]);
    $item->get('top_corner_graphic')->addCacheableDependency($cacheability);
    $item->get('bottom_corner_graphic')->addCacheableDependency($cacheability);
    $this->list[0] = $item;
  }

  /**
   * Checks if the node has any corner graphic fields with values.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity.
   *
   * @return bool
   *   TRUE if the node has corner graphics, FALSE otherwise.
   */
  protected function hasCornerGraphicsInNode(NodeInterface $node) {
    return ($node->hasField('field_graphical_image') && !$node->field_graphical_image->isEmpty()) ||
      ($node->hasField('field_bottom_graphical_image') && !$node->field_bottom_graphical_image->isEmpty());
  }

  /**
   * Adds node corner graphics to the details array.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity.
   * @param array $details
   *   The details array to be modified.
   * @param object $file_url_generator
   *   The file URL generator service.
   */
  protected function addNodeCornerGraphics(NodeInterface $node, array &$details, $file_url_generator) {
    if ($node->hasField('field_graphical_image') &&
      !$node->field_graphical_image->isEmpty() &&
      $node->field_graphical_image->entity &&
      !$node->field_graphical_image->entity->field_media_image->isEmpty()) {

      $top_corner_graphic_file = $node->field_graphical_image->entity->field_media_image->entity;
      $details['top_corner_graphic'] = $file_url_generator->generateString($top_corner_graphic_file->getFileUri());
    }

    if ($node->hasField('field_bottom_graphical_image') &&
      !$node->field_bottom_graphical_image->isEmpty() &&
      $node->field_bottom_graphical_image->entity &&
      !$node->field_bottom_graphical_image->entity->field_media_image->isEmpty()) {

      $bottom_corner_graphic_file = $node->field_bottom_graphical_image->entity->field_media_image->entity;
      $details['bottom_corner_graphic'] = $file_url_generator->generateString($bottom_corner_graphic_file->getFileUri());
    }
  }

  /**
   * Checks if the term has any corner graphic fields with values.
   *
   * @param \Drupal\taxonomy\Entity\Term $term
   *   The taxonomy term entity.
   *
   * @return bool
   *   TRUE if the term has corner graphics, FALSE otherwise.
   */
  protected function hasCornerGraphicsInTerm($term) {
    return ($term->hasField('field_top_corner_graphic') && !$term->field_top_corner_graphic->isEmpty()) ||
      ($term->hasField('field_bottom_corner_graphic') && !$term->field_bottom_corner_graphic->isEmpty());
  }

  /**
   * Adds term corner graphics to the details array.
   *
   * @param \Drupal\taxonomy\Entity\Term $term
   *   The taxonomy term entity.
   * @param array $details
   *   The details array to be modified.
   * @param object $file_url_generator
   *   The file URL generator service.
   */
  protected function addTermCornerGraphics($term, array &$details, $file_url_generator) {
    if ($term->hasField('field_top_corner_graphic') &&
      !$term->field_top_corner_graphic->isEmpty() &&
      $term->field_top_corner_graphic->entity) {

      $top_corner_graphic_file = $term->field_top_corner_graphic->entity;
      $details['top_corner_graphic'] = $file_url_generator->generateString($top_corner_graphic_file->getFileUri());
    }

    if ($term->hasField('field_bottom_corner_graphic') &&
      !$term->field_bottom_corner_graphic->isEmpty() &&
      $term->field_bottom_corner_graphic->entity) {

      $bottom_corner_graphic_file = $term->field_bottom_corner_graphic->entity;
      $details['bottom_corner_graphic'] = $file_url_generator->generateString($bottom_corner_graphic_file->getFileUri());
    }
  }

}
