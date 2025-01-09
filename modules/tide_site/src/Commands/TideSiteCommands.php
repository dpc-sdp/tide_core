<?php

namespace Drupal\tide_site\Commands;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\taxonomy\Entity\Term;
use Drupal\tide_site\BreadcrumbCacheDependencyManager;
use Drush\Commands\DrushCommands;
use Drush\Drush;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class TideSiteCommands extends DrushCommands {

  use StringTranslationTrait;

  /**
   * Gets the breadcrumb parent relationships.
   *
   * @command rebuild-breadcrumb-relationships
   * @aliases rbr
   * @usage drush rebuild-breadcrumb-relationships
   */
  public function rebuildBreadcrumbRelationships() {
    try {
      $query = \Drupal::database()->select('node__field_breadcrumb_parent', 'bp')
        ->fields('bp', ['entity_id', 'field_breadcrumb_parent_target_id']);
      $results = $query->execute()->fetchAllKeyed();
      if (empty($results)) {
        Drush::output()->writeln("No breadcrumb relationships found.");
      }
      $bm = new BreadcrumbCacheDependencyManager();
      $bm->addDependency($results);
      Drush::output()->writeln("Breadcrumb relationships have been rebuilt.");
    }
    catch (\Exception $e) {
      $this->logger()->error('Error fetching breadcrumb parent data: ' . $e->getMessage());
      return [];
    }
  }

  /**
   * Update the domains on the site taxonomy based on an environment variable.
   *
   * @usage drush tide-site-env-domain-update
   *   Update the domains on the site taxonomy based on an environment variable.
   *
   * @command tide:site-env-domain-update
   * @aliases tide-si-domup,tide-site-env-domain-update
   *
   * @throws \Exception
   */
  public function siteEnvDomainUpdate() {
    try {
      $environment = getenv('LAGOON_GIT_BRANCH');
      if ($environment == 'production') {
        $this->output()->writeln($this->t('This command cannot run in Lagoon production environments.'));
      }
      else {
        $fe_domains = getenv('FE_DOMAINS');
        if (!empty($fe_domains)) {
          foreach (explode(',', $fe_domains) as $fe_domain) {
            $domain = explode('|', $fe_domain);
            $term = Term::load($domain[0]);
            // Check if the term exists before trying to manipulate it.
            if ($term) {
              $term->set('field_site_domains', str_replace('<br/>', "\r\n", $domain[1]));
              $term->save();
            }
            else {
              $this->output()->writeln($this->t('Term with ID @term_id not found.', ['@term_id' => $domain[0]]));
            }
          }
          $this->output()->writeln($this->t('Domains Updated.'));
        }
        else {
          $this->output()->writeln($this->t('No site specific domains were found in this environment.'));
        }
      }
    }
    catch (ConsoleException $exception) {
      throw new \Exception($exception->getMessage());
    }
  }

}
