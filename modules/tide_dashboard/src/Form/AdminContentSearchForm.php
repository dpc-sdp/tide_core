<?php

namespace Drupal\tide_dashboard\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a simplified search form for Admin content route.
 *
 * @package Drupal\tide_dashboard\Form
 */
class AdminContentSearchForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * AdminContentSearchForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'admin_content_search_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['search'] = [
      '#type' => 'fieldset',
      '#open' => TRUE,
      '#collapsible' => FALSE,
      '#title' => $this->t('Search content'),
      '#tree' => FALSE,
      '#attributes' => [
        'class' => ['form--inline'],
      ],
    ];

    $form['search']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#title_display' => 'invisible',
      '#default_value' => '',
      '#attributes' => [
        'title' => $this->t('Enter the terms you wish to search for.'),
        'placeholder' => $this->t('Enter a search term or content title'),
      ],
    ];

    // Get the list of content types, sorted by label.
    $content_types = [];
    foreach ($this->entityTypeManager->getStorage('node_type')->loadMultiple() as $type) {
      $content_types[$type->id()] = $type->label();
    }
    asort($content_types);
    $content_types = ['_' => $this->t('- Any content type -')] + $content_types;
    $form['search']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Content type'),
      '#title_display' => 'invisible',
      '#field_prefix' => $this->t('in'),
      '#options' => $content_types,
      '#default_value' => '_',
    ];

    // Get the list of Sites in hierarchy.
    $sites = ['_' => $this->t('- Any site -')];
    /** @var \Drupal\taxonomy\TermStorageInterface $term_storage */
    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $tree = $term_storage->loadTree('sites', 0, NULL, TRUE);
    foreach ($tree as $term) {
      $sites[$term->id()] = $term->depth ? (str_repeat('-', $term->depth) . ' ' . $term->label()) : $term->label();
    }
    $form['search']['field_node_site_target_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Site'),
      '#title_display' => 'invisible',
      '#default_value' => '_',
      '#options' => $sites,
    ];

    $form['search']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
      '#attributes' => [
        'class' => ['form-item'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $route = 'system.admin_content';
    $options = [];

    $title = $form_state->getValue('title');
    if ($title) {
      $options['query']['title'] = $title;
    }
    $type = $form_state->getValue('type');
    if ($type && $type !== '_') {
      $options['query']['type'] = $type;
    }
    $site = $form_state->getValue('field_node_site_target_id');
    if ($site && $site !== '_') {
      $options['query']['field_node_site_target_id'] = [$site];
    }

    $form_state->setRedirect($route, [], $options);
  }

}
