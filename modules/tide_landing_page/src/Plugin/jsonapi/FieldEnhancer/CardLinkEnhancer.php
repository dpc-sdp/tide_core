<?php

namespace Drupal\tide_landing_page\Plugin\jsonapi\FieldEnhancer;

use Drupal\Core\Entity\EntityInterface;
use Drupal\file\Entity\File;
use Drupal\jsonapi_extras\Plugin\ResourceFieldEnhancerBase;
use Drupal\media\Entity\Media;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\taxonomy\Entity\Term;
use Drupal\tide_landing_page\Helper\TideLandingPageHelper;
use Drupal\tide_media\Plugin\jsonapi\FieldEnhancer\ImageEnhancer;
use Shaper\Util\Context;

/**
 * Adds necessary fields from internal link.
 *
 * @ResourceFieldEnhancer(
 *   id = "card_link_enhancer",
 *   label = @Translation("Card Link Enhancer(Adds necessary fields from internal link)"),
 *   description = @Translation("Adds necessary fields from internal link.")
 * )
 */
class CardLinkEnhancer extends ResourceFieldEnhancerBase {

  /**
   * {@inheritdoc}
   */
  protected function doUndoTransform($data, Context $context) {
    if (!empty($data) && (strpos($data['uri'], 'entity:node/') !== FALSE)) {
      $nid = str_replace("entity:node/", "", $data['uri']);
      $node = !empty($nid) ? \Drupal::entityTypeManager()->getStorage('node')->load($nid) : '';
      if ($node) {
        if (!empty($node->toArray()['path'][0])) {
          $data['pid'] = !empty($node->toArray()['path'][0]['pid']) ? $node->toArray()['path'][0]['pid'] : '';
        }
        $data['alias'] = $this->getAlias($nid);
        $data['url'] = $this->getAlias($nid);
        $data['image'] = $this->getImage($node);
        $data['internal_node_fields'] = $this->getCardFields($node);
      }
    }

    \Drupal::moduleHandler()->alter('tide_card_link_enhancer_undo_transform', $data, $context);
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  protected function doTransform($data, Context $context) {
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getOutputJsonSchema() {
    return [
      'anyOf' => [
        ['type' => 'object'],
      ],
    ];
  }

  /**
   * Helper function to get alias for entity node.
   *
   * @param string $nid
   *   The node id.
   *
   * @return string
   *   The alias for node.
   */
  public function getAlias($nid) {
    $url = '';
    if (!empty($nid)) {
      $url = \Drupal::service('path_alias.manager')->getAliasByPath('/node/' . $nid);
    }
    return $url;
  }

  /**
   * Helper function to get all the necessary node fields.
   *
   * @param \Drupal\Core\Entity\EntityInterface $node
   *   The node entity.
   *
   * @return array
   *   The array of fields value.
   */
  public function getCardFields(EntityInterface $node) {
    $card_fields = [];
    // Add title from the node.
    $node_title = $node->get('title')->getValue();
    $card_fields['title'] = $node_title ? $node_title[0]['value'] : '';
    // Add content type label from the node.
    $node_type = $node->type->entity->label();
    $card_fields['node_type'] = $node_type ? $node_type : '';
    // Add topic from the node.
    if ($node->hasField('field_topic') && !$node->field_topic->isEmpty()) {
      $topic = Term::load($node->get('field_topic')->getValue()[0]['target_id']);
      if ($topic) {
        $card_fields['topic'] = $topic->get('name')->value;
      }
    }
    // Add tags from the node.
    if ($node->hasField('field_tags') && !$node->field_tags->isEmpty()) {
      $tag_ids = $node->get('field_tags')->getValue();
      foreach ($tag_ids as $id) {
        $tag = !empty($id['target_id']) ? Term::load($id['target_id']) : '';
        if ($tag) {
          $card_fields['tags'][] = $tag->get('name')->value;
        }
      }
    }
    // Add summary from the node.
    if ($node->hasField('field_landing_page_summary') && !$node->field_landing_page_summary->isEmpty()) {
      $summary = $node->get('field_landing_page_summary')->getValue();
      $card_fields['summary'] = $summary ? $summary[0]['value'] : '';
    }
    $module_handler = \Drupal::moduleHandler();
    if ($module_handler->moduleExists('tide_news')) {
      // Add the date field for news.
      if ($node->hasField('field_news_date') && !$node->field_news_date->isEmpty()) {
        $start_date = $node->get('field_news_date')->getValue()[0]['value'] ?? NULL;
        $end_date = $node->get('field_news_date')->getValue()[0]['end_value'] ?? NULL;
        $news_dates = [
          'value' => TideLandingPageHelper::localDateAndTimeFormatter($start_date),
          'end_value' => TideLandingPageHelper::localDateAndTimeFormatter($end_date),
        ];
        $card_fields['date'] = $news_dates ? $news_dates : '';
      }
    }
    if ($module_handler->moduleExists('tide_event')) {
      // Add event fields.
      if ($node->hasField('field_event_details') && !$node->field_event_details->isEmpty()) {
        $paragraph_id = $node->get('field_event_details')->getValue()[0]['target_id'];
        $paragraph = $paragraph_id ? Paragraph::load($paragraph_id) : '';
        if ($paragraph && $paragraph->field_paragraph_date_range->getValue()) {
          $event_date = $paragraph->field_paragraph_date_range->getValue()[0];
          // Parse date with GMT timezone.
          if ($event_date != NULL) {
            $start_date = $event_date['value'] ?? NULL;
            $end_date = $event_date['end_value'] ?? NULL;
            $event_dates = [
              'value' => TideLandingPageHelper::localDateAndTimeFormatter($start_date),
              'end_value' => TideLandingPageHelper::localDateAndTimeFormatter($end_date),
            ];
            $card_fields['date'] = $event_dates ? $event_dates : '';
          }
        }
      }
    }
    if ($module_handler->moduleExists('tide_grant')) {
      // Add the date field for grants.
      if ($node->hasField('field_node_dates') && !$node->field_node_dates->isEmpty()) {
        $start_date = $node->get('field_node_dates')->getValue()[0]['value'] ?? NULL;
        $end_date = $node->get('field_node_dates')->getValue()[0]['end_value'] ?? NULL;
        $grants_dates = [
          'value' => TideLandingPageHelper::localDateAndTimeFormatter($start_date),
          'end_value' => TideLandingPageHelper::localDateAndTimeFormatter($end_date),
        ];
        $card_fields['date'] = $grants_dates ? $grants_dates : '';
      }
      // Add the ongoing field for grants.
      if ($node->hasField('field_node_on_going') && !$node->field_node_on_going->isEmpty()) {
        $card_fields['ongoing'] = $node->get('field_node_on_going')->getValue()[0]['value'];
      }
    }
    if ($module_handler->moduleExists('tide_publication')) {
      // Add the authors field for publication.
      if ($node->hasField('field_publication_authors') && !$node->field_publication_authors->isEmpty()) {
        $author_ids = $node->get('field_publication_authors')->getValue();
        if ($author_ids) {
          foreach ($author_ids as $id) {
            $author = !empty($id['target_id']) ? Term::load($id['target_id']) : '';
            if ($author) {
              $card_fields['publication_authors'][] = $author->get('name')->value;
            }
          }
        }
      }
      // Add the date field for publication.
      if ($node->hasField('field_publication_date') && !$node->field_publication_date->isEmpty()) {
        $card_fields['date'] = TideLandingPageHelper::localDateAndTimeFormatter($node->get('field_publication_date')->getValue()[0]['value']);
      }
    }
    if ($module_handler->moduleExists('tide_profile')) {
      // Add the induction year field for profile.
      if ($node->hasField('field_year') && !$node->field_year->isEmpty()) {
        $card_fields['induction_year'] = $node->get('field_year')->getValue()[0]['value'];
      }
    }
    return $card_fields;
  }

  /**
   * Helper function to add image field details.
   *
   * @param \Drupal\Core\Entity\EntityInterface $node
   *   The node entity.
   *
   * @return array
   *   The image data with focal point values.
   */
  public function getImage(EntityInterface $node) {
    $image = [];
    if ($node->hasField('field_featured_image') && !$node->field_featured_image->isEmpty()) {
      $feature_image = $node->get('field_featured_image')->getValue();
      if (!empty($feature_image)) {
        $image_id = $feature_image[0]['target_id'];
        $media = $image_id ? Media::load($image_id) : '';
        // Get the ID of the media object image field.
        $media_field = $media ? $media->get('field_media_image')->getValue() : '';
        if ($media_field) {
          // Image Details.
          $image['data'] = $media_field;
          $file = $media_field[0]['target_id'] ? File::load($media_field[0]['target_id']) : '';
          if ($file) {
            // Get URL of the image file.
            $image['url'] = $file->createFileUrl();
            // Get image crop values.
            $crop = !empty($file) ? ImageEnhancer::getCropEntity($file, 'focal_point') : '';
            $focal_point = !empty($crop) ? $crop->position() : '';
            $image['meta']['focal_point'] = $focal_point;
          }
        }
      }
    }
    return $image;
  }

  /**
   * Extract the original alias without site prefix.
   *
   * @param array $path
   *   The path.
   * @param string $site_base_url
   *   The site base URL if the path alias is an absolute URL.
   *
   * @return string
   *   The raw internal alias without site prefix.
   */
  public function getPathAliasWithoutSitePrefix(array $path, $site_base_url = '') {
    $pattern = '/^\/site\-(\d+)\//';
    if ($site_base_url) {
      $pattern = '/' . preg_quote($site_base_url, '/') . '\/site\-(\d+)\//';
    }
    return preg_replace($pattern, $site_base_url . '/', $path['alias']);
  }

}
