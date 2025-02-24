<?php

namespace Drupal\tide_site;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\path_alias\AliasManager as CoreAliasManager;
use Drupal\path_alias\AliasRepositoryInterface;
use Drupal\path_alias\AliasWhitelistInterface;

/**
 * Class alias manager.
 *
 * @package Drupal\tide_site
 */
class AliasManager extends CoreAliasManager {

  /**
   * The Alias Storage Helper service.
   *
   * @var \Drupal\tide_site\AliasStorageHelper
   */
  protected $aliasHelper;

  /**
   * {@inheritdoc}
   */
  public function __construct(AliasRepositoryInterface $repository, AliasWhitelistInterface $whitelist, LanguageManagerInterface $language_manager, CacheBackendInterface $cache, protected ?TimeInterface $time, AliasStorageHelper $alias_helper) {
    parent::__construct($repository, $whitelist, $language_manager, $cache, $time);
    $this->aliasHelper = $alias_helper;
  }

  /**
   * {@inheritdoc}
   */
  public function getAliasByPath($path, $langcode = NULL) {
    $alias = parent::getAliasByPath($path, $langcode);

    // Remove the site prefix from path alias when responding from
    // JSONAPI entity resource with site parameter.
    $request = \Drupal::request();
    $is_jsonapi = !empty($request->attributes) ? $request->attributes->get('_is_jsonapi', FALSE) : FALSE;
    if ($is_jsonapi) {
      $site_id = $request->get('site');
      if ($site_id) {
        $alias = $this->aliasHelper->getPathAliasWithoutSitePrefix(['alias' => $alias]);
      }
    }

    return $alias;
  }

}
