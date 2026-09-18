<?php

namespace Drupal\tide_news\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\workflows\Entity\Workflow;

/**
 * Hook implementations for the tide news module.
 */
class TideNewsHooks {

  /**
   * Implements hook_entity_bundle_create().
   */
  #[Hook('entity_bundle_create')]
  public function entityBundleCreate($entity_type_id, $bundle) {
    // Enable News in Editorial workflow if workflow module is enabled.
    if ($entity_type_id === 'node' && $bundle === 'news') {
      /** @var \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler */
      $moduleHandler = \Drupal::service('module_handler');
      if ($moduleHandler->moduleExists('workflows')) {
        $editorial_workflow = Workflow::load('editorial');
        if ($editorial_workflow) {
          $editorial_workflow->getTypePlugin()
            ->addEntityTypeAndBundle('node', 'news');
          $editorial_workflow->save();
        }
      }
    }
  }

  /**
   * Implements hook_form_BASE_FORM_ID_alter().
   */
  #[Hook('form_node_form_alter')]
  public function formNodeFormAlter(&$form, FormStateInterface $form_state, $form_id) {
    $field_config = 'node.news.field_custom_filters';
    $storage = \Drupal::entityTypeManager()->getStorage('field_config');
    if ($storage->load($field_config) !== NULL && $form_id === 'node_news_form') {
      $field_config_storage = $storage->load($field_config);
      $settings = $field_config_storage->getSettings();
      if (
        (
          is_array($settings['handler_settings'])
          && isset($settings['handler_settings']['target_bundles'])
          && !is_array($settings['handler_settings']['target_bundles'])
        )
        || empty($settings['handler_settings'])
      ) {
        $form['field_custom_filters']['#access'] = FALSE;
      }
    }
    if (!in_array($form_id, [
      'node_news_form',
      'node_news_edit_form',
      'node_news_quick_node_clone_form',
    ])) {
      return;
    }

    if (isset($form['title']['widget'][0]['value']['#description'])) {
      $form['title']['widget'][0]['value']['#description'] = t('Include a short unique title for your page and keywords.');
    }

    // Hide the body summary subfield in favour of the new Summary field.
    if (!empty($form['body']['widget'])) {
      foreach (Element::children($form['body']['widget']) as $delta) {
        if (!empty($form['body']['widget'][$delta]['summary'])) {
          $form['body']['widget'][$delta]['summary']['#access'] = FALSE;
        }
      }
    }

    // Change form layout.
    $form['#attached']['library'][] = 'tide_news/news_form';
    $form['#attributes']['class'][] = 'node-form-news';
    $form['#process'][] = '_tide_news_form_node_form_process';
  }

}
