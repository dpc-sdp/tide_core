<?php

/**
 * @file
 * Tokens.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info_alter().
 */
function tide_site_token_info_alter(&$data) {
  $data['tokens']['url']['path-no-site-prefix'] = [
    'name' => t('Path without Site Prefix'),
    'description' => t('The base path component without site prefix.'),
  ];

  $data['tokens']['url']['fe_url'] = [
    'name' => t('Front-end URL'),
    'description' => t("The node's absolute front-end URL built from its primary site's first domain."),
  ];

  $data['tokens']['node']['publication-parent-prefix'] = [
    'name' => t('Publication parent with prefix'),
    'description' => t('The publication parent to use with linkit'),
  ];
}

/**
 * Implements hook_tokens().
 */
function tide_site_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  $language_manager = \Drupal::languageManager();
  $url_options = ['absolute' => TRUE];
  if (isset($options['langcode'])) {
    $url_options['language'] = $language_manager->getLanguage($options['langcode']);
    $langcode = $options['langcode'];
  }
  else {
    $langcode = $language_manager->getCurrentLanguage()->getId();
  }

  if ($type == 'url' && !empty($data['url'])) {
    /** @var \Drupal\Core\Url $url */
    $url = $data['url'];
    // To retrieve the correct path, modify a copy of the Url object.
    $path_url = clone $url;
    $path = '/';
    // Ensure the URL is routed to avoid throwing an exception.
    if ($url->isRouted()) {
      $path .= $path_url->setAbsolute(FALSE)->setOption('fragment', NULL)->getInternalPath();
    }
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'path-no-site-prefix':
          $value = !($url->getOption('alias')) ? \Drupal::service('path_alias.manager')->getAliasByPath($path, $langcode) : $path;
          $value = preg_replace("/^\/site\-(\d+)\//", '/', $value, 1);
          $replacements[$original] = $value;
          break;

        case 'fe_url':
          // [node:url] passes the node's canonical Url object. Recover the node
          // from the route to build its absolute front-end URL using the
          // primary site's first domain, with the /site-XXXX/ prefix stripped.
          if ($url->isRouted() && $url->getRouteName() === 'entity.node.canonical') {
            $nid = $url->getRouteParameters()['node'] ?? NULL;
            $node = $nid ? \Drupal::entityTypeManager()->getStorage('node')->load($nid) : NULL;
            if ($node) {
              /** @var \Drupal\tide_site\TideSiteHelper $helper */
              $helper = \Drupal::service('tide_site.helper');
              /** @var \Drupal\tide_site\AliasStorageHelper $alias_helper */
              $alias_helper = \Drupal::service('tide_site.alias_storage_helper');
              $site_url = $helper->getEntityPrimarySiteBaseUrl($node);
              // No primary site: leave empty instead of emitting /node/123.
              if ($site_url) {
                $fe_url = $helper->getNodeUrlFromPrimarySite($node);
                $fe_url = $alias_helper->getPathAliasWithoutSitePrefix(['alias' => $fe_url], $site_url);
                $replacements[$original] = $fe_url;
                // Invalidate when the node or its domain changes.
                $bubbleable_metadata->addCacheableDependency($node);
                $primary_site = $helper->getEntityPrimarySite($node);
                if ($primary_site) {
                  $bubbleable_metadata->addCacheableDependency($primary_site);
                }
              }
            }
          }
          break;
      }
    }
  }

  if ($type == 'node' && !empty($data['node'])) {
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'publication-parent-prefix':
          $node = $data['node'];
          if ($node->getType() == 'publication_page') {
            if ($node->hasField('field_publication') && !$node->get('field_publication')->isEmpty()) {
              $text = $node->field_publication->entity->getTitle();
              $value = "| Parent: " . $text;
            }
            $replacements[$original] = $value;
            break;
          }
      }
    }
  }

  return $replacements;
}
