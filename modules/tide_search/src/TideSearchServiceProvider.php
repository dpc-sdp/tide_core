<?php

namespace Drupal\tide_search;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;

/**
 * Replaces the queuers and processors plugin managers with failing stubs.
 */
class TideSearchServiceProvider implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('elasticsearch_connector.index_factory');
    $definition->setClass('Drupal\tide_search\ElasticSearch\Parameters\Factory\TideSearchIndexFactory');
  }

}
