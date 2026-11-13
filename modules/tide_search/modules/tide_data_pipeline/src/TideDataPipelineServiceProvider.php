<?php

namespace Drupal\tide_data_pipeline;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\tide_data_pipeline\ResourceType\DataPipelinesResourceTypeRepository;
use Drupal\tide_data_pipeline\Routing\DataPipelinesRouteSubscriber;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Service provider class.
 *
 * @package Drupal\tide_data_pipeline
 */
class TideDataPipelineServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    $modules = $container->getParameter('container.modules');

    // Check if jsonapi_extras is installed.
    if (isset($modules['jsonapi_extras'])) {
      $container->register('tide_data_pipeline.route_subscriber', DataPipelinesRouteSubscriber::class)
        ->setArguments([new Reference('jsonapi.resource_type.repository'), '%jsonapi.base_path%'])
        ->addTag('event_subscriber');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    if ($container->hasDefinition('jsonapi_extras.resource_type.repository')) {
      $container->getDefinition('jsonapi_extras.resource_type.repository')
        ->setClass(DataPipelinesResourceTypeRepository::class);
    }
  }

}
