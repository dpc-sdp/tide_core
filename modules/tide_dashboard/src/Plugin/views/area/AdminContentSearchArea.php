<?php

namespace Drupal\tide_dashboard\Plugin\views\area;

use Drupal\Core\Form\FormBuilderInterface;
use Drupal\tide_dashboard\Form\AdminContentSearchForm;
use Drupal\views\Plugin\views\area\AreaPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the Admin Content Search Form header for views.
 *
 * @ingroup views_area_handlers
 *
 * @ViewsArea("views_tide_dashboard_admin_content_search_form")
 *
 * @package Drupal\tide_dasboard\Plugin\views\area
 */
class AdminContentSearchArea extends AreaPluginBase {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Constructs a new AdminContentSearchArea.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FormBuilderInterface $form_builder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {
    return $this->formBuilder->getForm(AdminContentSearchForm::class);
  }

}
