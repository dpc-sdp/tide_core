<?php

namespace Drupal\tide_cms_support\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a 'Help' block.
 *
 * @Block(
 *   id = "tide_help_block",
 *   admin_label = @Translation("Tide CMS Help"),
 *   forms = {
 *     "settings_tray" = FALSE,
 *   },
 * )
 */
class HelpBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Creates a HelpBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The current route match.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RequestStack $requestStack, ModuleHandlerInterface $moduleHandler, RouteMatchInterface $routeMatch) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->request = $requestStack->getCurrentRequest();
    $this->moduleHandler = $moduleHandler;
    $this->routeMatch = $routeMatch;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request_stack'),
      $container->get('module_handler'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Do not show on a 403 or 404 page.
    if ($this->request->attributes->has('exception')) {
      return [];
    }

    $implementations = $this->moduleHandler->getModuleList();
    $build = [];
    $args = [
      $this->routeMatch->getRouteName(),
      $this->routeMatch,
    ];
    foreach ($implementations as $module => $moduleInfo) {
      if ($help = $this->moduleHandler->invoke($module, 'tide_help', $args)) {
        $build[] = is_array($help) ? $help : ['#markup' => $help];
      }
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['route']);
  }

}
