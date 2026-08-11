<?php

namespace Drupal\tide_demo_content\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Entity\EntityInterface;
use Drupal\tide_site\TideSiteFields;
use Drupal\yaml_content\ContentLoader\ContentLoaderInterface;

/**
 * Hook implementations for the tide demo content module.
 */
class TideDemoContentHooks {

  /**
   * Implements hook_tide_demo_content_entity_imported().
   */
  #[Hook('tide_demo_content_entity_imported')]
  public function tideDemoContentEntityImported(EntityInterface $entity, $content_data, ContentLoaderInterface $content_loader) {
    $entity_type = $entity->getEntityTypeId();
    // Assign an existing Site to imported entities.
    if (\Drupal::moduleHandler()->moduleExists('tide_site')) {
      /** @var \Drupal\tide_site\TideSiteHelper $site_helper */
      $site_helper = \Drupal::service('tide_site.helper');
      if (!$site_helper->isSupportedEntityType($entity_type)) {
        return;
      }

      /** @var \Drupal\Core\Entity\FieldableEntityInterface $entity */
      $entity_sites = $site_helper->getEntitySites($entity);
      $sites = $site_helper->getAllSites();
      if (!empty($sites)) {
        // Attempt to find the very first Site with the smallest tid.
        ksort($sites, SORT_ASC);
        try {
          foreach ($sites as $site_id => $site) {
            $trail = $site_helper->getSiteTrail($site_id);
            // Site term only has 1 item in its trail - choose this Site.
            if (count($trail) == 1) {
              $field_site_field_name = TideSiteFields::normaliseFieldName(TideSiteFields::FIELD_SITE, $entity_type);
              if ($entity->hasField($field_site_field_name)) {
                // This Site hasn't been assigned to the entity.
                if (!isset($entity_sites['ids'][$site_id])) {
                  $entity->{$field_site_field_name}[] = ['target_id' => $site_id];
                }
              }
              $field_primary_site_field_name = TideSiteFields::normaliseFieldName(TideSiteFields::FIELD_PRIMARY_SITE, $entity_type);
              // Update the Primary Site field if needed.
              if ($entity->hasField($field_primary_site_field_name) && $entity->get($field_primary_site_field_name)->isEmpty()) {
                $entity->$field_primary_site_field_name->target_id = $site_id;
              }
              // Should not create a new revision.
              if ($entity->getEntityType()->isRevisionable()) {
                $entity->setNewRevision(FALSE);
              }
              $entity->save();
            }
          }
        }
        catch (\Exception $exception) {
          \Drupal::messenger()->addError($exception->getMessage());
          tide_core_log_exception('tide_demo_content', $exception);
        }
      }
    }
  }

  /**
   * Implements hook_entity_delete().
   */
  #[Hook('entity_delete')]
  public function entityDelete(EntityInterface $entity) {
    // Removed the deleted entity from tracked demo entities.
    /** @var \Drupal\tide_demo_content\DemoContentRepository $repository */
    $repository = \Drupal::service('tide_demo_content.repository');
    $repository->untrackEntity($entity);
  }

  /**
   * Implements hook_config_ignore_settings_alter().
   */
  #[Hook('config_ignore_settings_alter')]
  public function configIgnoreSettingsAlter(array &$settings) {
    // Ignore all demo configs.
    $settings[] = '*tide_demo*';
    $settings[] = '*tide-demo*';
    $settings[] = '*tide_demo_content*';
    $settings[] = '*tide-demo-content*';
  }

}
