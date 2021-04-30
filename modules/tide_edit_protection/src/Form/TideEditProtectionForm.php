<?php

namespace Drupal\tide_edit_protection\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tide edit protection form.
 */
class TideEditProtectionForm extends ConfigFormBase {

  /**
   * Entity Type Manger.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManger;

  /**
   * Entity Type Bundle.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfo
   */
  protected $entityTypeBundle;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tide_edit_protection_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['tide_edit_protection.form'];
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManager $entityManager, EntityTypeBundleInfo $bundleInfo) {
    parent::__construct($config_factory);
    $this->entityTypeManger = $entityManager;
    $this->entityTypeBundle = $bundleInfo;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('config.factory'), $container->get('entity_type.manager'), $container->get('entity_type.bundle.info'));
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('tide_edit_protection.form');
    $entity_types = $entity_bundles = [];
    $entity_type_definitions = $this->entityTypeManger->getDefinitions();
    foreach ($entity_type_definitions as $entity_type) {
      $entity_types[$entity_type->id()] = $entity_type->getLabel();
    }
    $entity_bundle_info = $this->entityTypeBundle->getAllBundleInfo();
    foreach ($entity_bundle_info as $entity_type_id => $bundles) {
      foreach ($bundles as $bundle_id => $bundle) {
        $entity_bundles[$entity_type_id . '__' . $bundle_id] = $entity_types[$entity_type_id] . ' - ' . $bundle['label'];
      }
    }

    $config_entity_types = $config->get('entity_types');
    if (empty($config_entity_types)) {
      $config_entity_types = [];
    }
    $config_entity_bundles = $config->get('entity_bundles');
    if (empty($config_entity_bundles)) {
      $config_entity_bundles = [];
    }

    $entity_methods = [
      '' => $this->t('None'),
      'all' => $this->t('All entity forms'),
      'entity_type' => $this->t('Forms by entity type'),
      'entity_bundle' => $this->t('Forms by entity bundle'),
    ];

    $form['entity_method'] = [
      '#type' => 'select',
      '#title' => $this->t('Method'),
      '#default_value' => $config->get('entity_method'),
      '#options' => $entity_methods,
      '#description' => $this->t('Choose which entity forms will be matched for unsaved changes warning when leaving form page.'),
    ];

    $form['entity_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Entity types'),
      '#default_value' => $config_entity_types,
      '#options' => $entity_types,
      '#description' => $this->t('Choose special entity forms by entity type.'),
      '#states' => [
        'visible' => [
          ':input[name="entity_method"]' => ['value' => 'entity_type'],
        ],
      ],
    ];

    $form['entity_bundles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Entity bundles'),
      '#default_value' => $config_entity_bundles,
      '#options' => $entity_bundles,
      '#description' => $this->t('Choose special entity forms by entity bundles.'),
      '#states' => [
        'visible' => [
          ':input[name="entity_method"]' => ['value' => 'entity_bundle'],
        ],
      ],
    ];

    $form['alert_form_ids'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Other form ids'),
      '#default_value' => $config->get('alert_form_ids'),
      '#description' => $this->t('Specify forms by using their form id. Enter one form id per line. PHP regex [ tide_.*_form ] is supported.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('tide_edit_protection.form');

    $entity_method = $form_state->getValue('entity_method');
    $entity_types = array_filter($form_state->getValue('entity_types'));
    $entity_bundles = array_filter($form_state->getValue('entity_bundles'));
    $alert_form_ids = $form_state->getValue('alert_form_ids');

    if ('entity_type' !== $entity_method) {
      $entity_types = [];
    }
    if ('entity_bundle' !== $entity_method) {
      $entity_bundles = [];
    }

    $config->set('entity_method', $entity_method)
      ->set('entity_types', $entity_types)
      ->set('entity_bundles', $entity_bundles)
      ->set('alert_form_ids', $alert_form_ids)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
