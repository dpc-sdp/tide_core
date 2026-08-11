<?php

namespace Drupal\tide_site_simple_sitemap\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\simple_sitemap\Entity\SimpleSitemap;
use Drupal\simple_sitemap\Entity\SimpleSitemapInterface;
use Drupal\taxonomy\Entity\Term;

/**
 * Hook implementations for the tide site simple sitemap module.
 */
class TideSiteSimpleSitemapHooks {

  /**
   * Implements hook_ENTITY_TYPE_delete().
   */
  #[Hook('taxonomy_term_delete')]
  public function taxonomyTermDelete($term) {
    // Remove the sitemap of the deleted site term¡.
    try {
      /** @var \Drupal\taxonomy\TermInterface $term */
      if ($term->bundle() == 'sites') {
        $sitemap_id = 'site-' . $term->id();
        $sitemap = SimpleSitemap::load($sitemap_id);
        $sitemap_storage = \Drupal::entityTypeManager()->getStorage('simple_sitemap');
        if ($sitemap instanceof SimpleSitemapInterface) {
          $sitemap_storage->deleteContent($sitemap);
          $sitemap_storage->load($sitemap_id)->delete();
        }
      }
    }
    catch (Exception $exception) {
      tide_core_log_exception('tide_site_simple_sitemap', $exception);
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_insert().
   */
  #[Hook('simple_sitemap_insert')]
  public function simpleSitemapInsert(SimpleSitemap $entity) {
    $sitemap_id = $entity->id();
    if ($term_id = _tide_site_simple_sitemap_extract_site_number($sitemap_id)) {

      if (Term::load($term_id) && $entity->getType()->getOriginalId() === 'tide_default_sitemap_type') {
        /** @var Drupal\simple_sitemap\Manager\Generator $custom_generator */
        $custom_generator = \Drupal::service('simple_sitemap.generator');
        $custom_generator->setSitemaps($entity->id());
        $site_based_custom_link = $custom_generator->customLinkManager()->get('/');

        if (isset($site_based_custom_link[$sitemap_id]) && empty($site_based_custom_link[$sitemap_id])) {
          $custom_generator->customLinkManager()->add(
            '/',
            ["path" => "/", "priority" => "1.0", "changefreq" => "daily"]
          );
        }
      }
    }
  }

}
