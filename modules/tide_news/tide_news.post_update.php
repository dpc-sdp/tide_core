<?php

/**
 * @file
 * Post update functions for tide_news module.
 */

use Drupal\field\Entity\FieldConfig;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Item\Field;

/**
 * Install field_show_feature_image field.
 */
function tide_news_post_update_field_show_feature_image(&$sandbox) {
  \Drupal::moduleHandler()->loadInclude('tide_core', 'inc', 'includes/helpers');
  $config_location = [\Drupal::service('extension.list.module')->getPath('tide_news') . '/config/install'];
  $config_read = _tide_read_config('field.field.node.news.field_show_feature_image', $config_location, TRUE);
  $storage = \Drupal::entityTypeManager()->getStorage('field_config');
  $id = $storage->getIDFromConfigName('field.field.node.news.field_show_feature_image', $storage->getEntityType()->getConfigPrefix());
  if ($storage->load($id) === NULL) {
    $config_entity = $storage->createFromStorageRecord($config_read);
    $config_entity->save();
  }

  $config = FieldConfig::load('node.news.field_featured_image');
  $config->setDescription(
    '<p>The feature image displays by default below the date on published news pages. You have the option to turn off the feature image. The feature image displays in promotion or navigation cards linking to this page when Card display style is set as Thumbnail or Profile. JPEG is the preferred format.</p><p>See the SDP guide on <a href="https://digital-vic.atlassian.net/wiki/spaces/FPSDP/pages/2304212993/Image+ratios+sizes+and+component+use">image ratios, sizes and component use</a>.</p>',
  );
  $config->save();
  $field_show_feature_image = [
    'type' => 'boolean_checkbox',
    'weight' => 100,
    'region' => 'content',
    'settings' => [
      'display_label' => TRUE,
    ],
    'third_party_settings' => [],
  ];
  $group_feature_image = [
    'children' => [
      'field_featured_image',
      'field_show_feature_image',
    ],
    'label' => 'Feature image',
    'region' => 'content',
    'parent_name' => '',
    'weight' => 2,
    'format_type' => 'tab',
    'format_settings' => [
      'classes' => '',
      'show_empty_fields' => FALSE,
      'id' => '',
      'label_as_html' => FALSE,
      'formatter' => 'closed',
      'description' => '',
      'required_fields' => TRUE,
    ],
  ];

  /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $entity_form_display */
  $entity_form_display = Drupal::entityTypeManager()->getStorage('entity_form_display')->load('node.news.default');
  $field_group = $entity_form_display->getThirdPartySettings('field_group');
  if (is_array($field_group) && isset($field_group['group_section_content'])) {
    $field_group['group_feature_image'] = $group_feature_image;
    $field_featured_image_settings = $entity_form_display->getComponent('field_featured_image');
    $field_featured_image_settings['settings']['open'] = TRUE;
    $field_group['group_feature_image']['weight'] = $field_featured_image_settings['weight'];
    $field_featured_image_settings['weight'] = 99;
    $entity_form_display->setThirdPartySetting('field_group', 'group_feature_image', $field_group['group_feature_image']);
    $entity_form_display->setComponent('field_featured_image', $field_featured_image_settings);
    $entity_form_display->setComponent('field_show_feature_image', $field_show_feature_image);
    $entity_form_display->save();
  }

  $index = Index::load('node');
  $field = new Field($index, 'field_show_feature_image');
  $field->setType('boolean');
  $field->setDatasourceId('entity:node');
  $field->setPropertyPath('field_show_feature_image');
  $field->setLabel('Display the feature image in the body of your news page');
  $index->addField($field);
  $index->save();
}
