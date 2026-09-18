<?php

namespace Drupal\tide_core\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides explicit container assignment for legacy service decorators.
 */
trait ContainerAwareTrait {

  /**
   * The service container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface|null
   */
  protected $container;

  /**
   * Sets the service container.
   */
  public function setContainer(?ContainerInterface $container): void {
    $this->container = $container;
  }

}
