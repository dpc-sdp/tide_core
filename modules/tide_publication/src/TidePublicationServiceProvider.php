<?php

namespace Drupal\tide_publication;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class tide_publication service provider.
 *
 * @package Drupal\tide_publication
 */
class TidePublicationServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    // Dynamically define the service tide_site.get_route_subscriber.
    $modules = $container->getParameter('container.modules');

    // Check if jsonapi is installed.
    if (isset($modules['jsonapi'])) {
      if ($container->hasDefinition('jsonapi.entity_resource')) {
        $entity_resource = $container->getDefinition('jsonapi.entity_resource');
        // Pretends to be a JSON:API Controller.
        $container->register('jsonapi.entity_resource.publication', '\Drupal\tide_publication\Controller\PublicationResource')
          ->setArguments($entity_resource->getArguments())
          ->addMethodCall('setContainer', [new Reference('service_container')])
          ->addMethodCall('setDependencies', [
            new Reference('entity_hierarchy.nested_set_storage_factory'),
            new Reference('entity_hierarchy.nested_set_node_factory'),
            new Reference('entity_hierarchy.entity_tree_node_mapper'),
            new Reference('module_handler'),
            new Reference('cache.data'),
          ]);
      }
    }
  }

}
