<?php

namespace Drupal\tide_migration\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManager;
use Drupal\migrate_plus\Entity\MigrationGroup;
use Drupal\migrate_plus\Entity\MigrationGroupInterface;
use Drupal\migrate_source_ui\StubMigrationMessage;
use Drupal\migrate_tools\MigrateBatchExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tide migrationform.
 */
class TideMigrationForm extends FormBase {
  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The migration plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManager
   */
  protected $pluginManagerMigration;

  /**
   * The migration definitions.
   *
   * @var array
   */
  protected $definitions;

  /**
   * TideMigrationUiForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The migration plugin manager.
   * @param \Drupal\migrate\Plugin\MigrationPluginManager $plugin_manager_migration
   *   *   The migration plugin manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, MigrationPluginManager $plugin_manager_migration) {
    $this->entityTypeManager = $entity_type_manager;
    $this->pluginManagerMigration = $plugin_manager_migration;
    $this->definitions = $this->pluginManagerMigration->getDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.migration')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tide_migration_ui_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $options = [];

    $query = $this->entityTypeManager->getStorage('migration_group')->getQuery();
    $group_ids = $query->execute();
    $groups = $this->entityTypeManager->getStorage('migration_group')->loadMultiple($group_ids);

    foreach ($groups as $group) {
      if ($group instanceof MigrationGroupInterface) {
        $options[$group->id()] = $group->label();
      }
    }

    $form['tide_migration'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Tide migration'),
    ];

    $form['tide_migration']['migrations'] = [
      '#type' => 'select',
      '#title' => $this->t('Tide migration group'),
      '#options' => $options,
    ];
    $form['tide_migration']['source_file'] = [
      '#type' => 'file',
      '#title' => $this->t('Data source file'),
      '#description' => t('Select the data file you want to migrate, allowed extensions: csv, json or xml.'),
    ];
    $form['tide_migration']['update_existing_records'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Update existing records'),
      '#default_value' => 1,
    ];
    $form['tide_migration']['import'] = [
      '#type' => 'submit',
      '#value' => $this->t('Migrate'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    // Support the data files.
    $validators = ['file_validate_extensions' => ['csv json xml']];
    // Save file to private file system to protect data file.
    $file_destination = 'private://';
    $file = file_save_upload('source_file', $validators, $file_destination, 0, FileSystemInterface::EXISTS_REPLACE);
    if (isset($file)) {
      if ($file) {
        $form_state->setValue('file_path', $file->getFileUri());
      }
      else {
        $form_state->setErrorByName('source_file', $this->t('The file could not be uploaded.'));
      }
    }
    else {
      $form_state->setErrorByName('source_file', $this->t('You have to upload a source file.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $group_id = $form_state->getValue('migrations');

    $query = $this->entityTypeManager->getStorage('migration')->getQuery()
      ->accessCheck(TRUE);

    $migration_groups = MigrationGroup::loadMultiple();

    if (array_key_exists($group_id, $migration_groups)) {
      $query->condition('migration_group', $group_id);
    }
    else {
      $query->notExists('migration_group');
    }

    $migration_ids = array_values($query->execute());

    foreach ($migration_ids as $mid) {
      $migration = $this->pluginManagerMigration->createInstance($mid);
      $status = $migration->getStatus();
      if ($status !== MigrationInterface::STATUS_IDLE) {
        $migration->setStatus(MigrationInterface::STATUS_IDLE);
        $this->messenger()->addWarning($this->t('Migration @id reset to Idle', ['@id' => $mid]));
      }
      $options = [
        'file_path' => $form_state->getValue('file_path'),
      ];
      if ($form_state->getValue('update_existing_records')) {
        $options['update'] = 1;
      }
      $executable = new MigrateBatchExecutable($migration, new StubMigrationMessage(), $options);
      $executable->batchImport();
    }

  }

}
