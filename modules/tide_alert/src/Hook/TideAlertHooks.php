<?php

namespace Drupal\tide_alert\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\tide_alert\SiteAlertItemList;
use Drupal\tide_alert\TideAlertFieldStorageDefinition;

/**
 * Hook implementations for the tide alert module.
 */
class TideAlertHooks {

  /**
   * Implements hook_form_BASE_FORM_ID_alter().
   */
  #[Hook('form_taxonomy_term_form_alter')]
  public function formTaxonomyTermFormAlter(array &$form, FormStateInterface $form_state, $form_id) {
    if (!\Drupal::moduleHandler()->moduleExists('tide_site')) {
      return;
    }

    if ($form_id != 'taxonomy_term_sites_form') {
      return;
    }

    /** @var \Drupal\taxonomy\TermForm $form_object */
    $form_object = $form_state->getFormObject();
    $term = $form_object->getEntity();
    if ($term->isNew()) {
      return;
    }

    $form['footer']['site_alerts'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => t('Site Alerts and Notifications'),
    ];

    $form['footer']['site_alerts']['view'] = views_embed_view('site_alerts', 'term_alerts');
  }

  /**
   * Implements hook_entity_bundle_field_info().
   */
  #[Hook('entity_bundle_field_info')]
  public function entityBundleFieldInfo(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
    $fields = [];

    if (\Drupal::moduleHandler()->moduleExists('tide_site') && $entity_type->id() === 'taxonomy_term') {
      $default_settings = [
        'handler' => 'default:node',
        'handler_settings' => [
          'target_bundles' => [
            'alert' => 'alert',
          ],
        ],
      ];
      $default_display = [
        'label' => 'hidden',
        'region' => 'hidden',
        'weight' => 100,
      ];

      if ($bundle === 'sites') {
        $fields['site_alerts'] = TideAlertFieldStorageDefinition::create('entity_reference')
          ->setLabel(t('Site Alerts and Notifications'))
          ->setComputed(TRUE)
          ->setClass(SiteAlertItemList::class)
          ->setReadOnly(FALSE)
          ->setInternal(FALSE)
          ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
          ->setSettings($default_settings)
          ->setDisplayOptions('view', $default_display)
          ->setDisplayOptions('form', $default_display)
          ->setTargetEntityTypeId('node')
          ->setTargetBundle('alert')
          ->setName('site_alerts')
          ->setDisplayConfigurable('form', FALSE)
          ->setDisplayConfigurable('view', FALSE);
      }
    }

    return $fields;
  }

  /**
   * Implements hook_ENTITY_TYPE_presave().
   */
  #[Hook('node_presave')]
  public function nodePresave(EntityInterface $entity) {
    if ($entity->bundle() == 'alert') {
      $moderation_state = $entity->get('moderation_state')->value;
      if ($moderation_state == 'archived' || $moderation_state == 'published') {
        _tide_alert_invalidate_referenced_taxonomies($entity->field_node_site);
      }
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_predelete().
   */
  #[Hook('node_predelete')]
  public function nodePredelete(EntityInterface $entity) {
    if ($entity->bundle() == 'alert') {
      _tide_alert_invalidate_referenced_taxonomies($entity->field_node_site);
    }
  }

}
