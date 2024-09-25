<?php

namespace Drupal\tide_media\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for deleting a media entity.
 */
abstract class FullFileDeletionForm extends ContentEntityConfirmFormBase {

  /**
   * The media being deleted.
   *
   * @var \Drupal\file\FileInterface
   */
  protected $entity;

  /**
   * The file storage service.
   *
   * @var \Drupal\file\FileStorageInterface
   */
  protected $fileStorage;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The DateFormatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The media storage service.
   *
   * @var \Drupal\Media\MediaStorage
   */
  protected $mediaStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, FileSystemInterface $fileSystem, DateFormatterInterface $dateFormatter, EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL) {
    $this->fileStorage = $entityTypeManager->getStorage('file');
    $this->mediaStorage = $entityTypeManager->getStorage('media');
    $this->fileSystem = $fileSystem;
    $this->dateFormatter = $dateFormatter;
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('file_system'),
      $container->get('date.formatter'),
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'));
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $parent = parent::buildForm($form, $form_state);
    $file = $this->getCurrentFile();
    // If no file attached, just return.
    if (!$file) {
      return $parent;
    }
    if ($this->fileSystem->getDestinationFilename($file->getFileUri(), FileSystemInterface::EXISTS_ERROR)) {
      return $parent;
    }
    // Gets and parses uri.
    $uri = $file->getFileUri();
    $parsed = parse_url($uri);

    // Try to get a original file name.
    $filename = preg_replace('/_[0-9]+(\.)/', '.', $file->getFilename(), 1);
    $file_extension = pathinfo($filename, PATHINFO_EXTENSION);
    $original_file_name = pathinfo($filename, PATHINFO_FILENAME);
    // Escapes special characters.
    $original_file_name = preg_replace('/([^A-Za-z0-9\s])/', '\\\\$1', $original_file_name);
    // Search the directory.
    $scanned_results = $this->fileSystem->scanDirectory($parsed['scheme'] . '://' . $parsed['host'], '/^' . $original_file_name . '(_\d+)?\\' . '.' . $file_extension . '/');
    $parent['description']['#markup'] = t('Clicking the button will delete the file entirely from the system');
    $revision_ids = $this->mediaStorage->getQuery()
      ->allRevisions()
      ->condition('mid', $this->entity->id())
      ->accessCheck(TRUE)
      ->execute();
    if ($revision_ids && count($revision_ids) > 1) {
      foreach ($revision_ids as $revision_id => $mid) {
        if ($revision_id == $this->entity->getRevisionId()) {
          continue;
        }
        $revision = $this->mediaStorage->loadRevision($revision_id);
        $fid = $revision->getSource()->getSourceFieldValue($revision);
        if ($fid) {
          $media_revision_file = File::load($fid);
          if ($media_revision_file && !isset($scanned_results[$media_revision_file->getFileUri()])) {
            $scanned_results[$media_revision_file->getFileUri()] = 'revision';
          }
        }
      }
    }
    if ($scanned_results) {
      // Basic settings.
      $parent['info'] = [
        '#type' => 'table',
        '#header' => [
          'delete',
          'url',
          'created',
          'file status',
          'operations',
          'media',
        ],
      ];
      foreach ($scanned_results as $id => $item) {
        /** @var \Drupal\file\Entity\File $result */
        $result = $this->fileStorage->loadByProperties(['uri' => $id]);
        if ($result) {
          $result = reset($result);
          // Checks if the user is allowed to delete.
          // Only the file owner can update or delete the file entity,
          // In our case, just check if the user can view the file entity.
          if (!$result->access('view')) {
            continue;
          }

          // Gets related media entity.
          $has_media_entity = TRUE;
          $referenced = file_get_file_references($result, NULL, EntityStorageInterface::FIELD_LOAD_REVISION, '');
          if (empty($referenced)) {
            $has_media_entity = FALSE;
          }
          if ($item == 'revision') {
            $has_media_entity = FALSE;
          }
          $media = NULL;
          if ($has_media_entity) {
            foreach ($referenced as $data) {
              foreach (array_keys($data) as $entity_type_id) {
                if ($entity_type_id == 'media') {
                  foreach ($data[$entity_type_id] as $key => $value) {
                    $media = $value;
                  }
                }
              }
            }
          }
          // Builds the table.
          $parent['info'][$result->id()]['to_be_deleting'] = [
            '#type' => 'checkbox',
            '#title' => '',
            '#default_value' => $result->id() == $file->id() ?? TRUE,
            '#disabled' => $result->id() == $file->id() ?? TRUE,
          ];
          $parent['info'][$result->id()]['uri'] = [
            '#type' => 'link',
            '#title' => $result->getFilename(),
            '#attributes' => ['target' => '_blank'],
            '#url' => Url::fromUri(\Drupal::service('file_url_generator')->generateAbsoluteString($result->getFileUri())),
          ];
          $parent['info'][$result->id()]['created'] = [
            '#markup' => $this->dateFormatter->format($result->created->value, 'custom', 'd/M/Y - H:i'),
          ];
          $parent['info'][$result->id()]['file_status'] = [
            '#markup' => $result->isPermanent() ? 'Permanent' : 'Temporary',
          ];
          $parent['info'][$result->id()]['delete'] = [
            '#type' => 'dropbutton',
            '#links' => [
              'a' => [
                'title' => $this->t('Delete'),
                'url' => Url::fromRoute('tide_media.file.delete_action', [
                  'fid' => $result->id(),
                  'redirect_info' => $this->entity->getEntityTypeId() . '_' . $this->entity->id(),
                ]),
              ],
            ],
            '#access' => $result->id() !== $file->id() ?? FALSE,
            '#ajax' => [
              'dialogType' => 'modal',
              'progress' => [
                'type' => 'throbber',
                'message' => 'Loading...',
              ],
            ],
          ];
          $parent['info'][$result->id()]['linked_media'] = [
            '#type' => 'inline_template',
            '#template' => '{% if has_media == false %}<p>{{ title }}</p>{% else %}<a href="{{ link }}" _target="_blank">{{ title }}</a>{% endif %}',
            '#context' => [
              'has_media' => $has_media_entity,
              'title' => $has_media_entity ? $media->getName() : 'No media linked',
              'link' => $has_media_entity ? $media->toUrl()->toString() : NULL,
            ],
          ];
        }
      }
    }
    return $parent;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $value = 0;
    $deleted_files = [];
    $deleted_media = [];
    if (isset($form['info'])) {
      foreach (Element::children($form['info']) as $delta) {
        if ($form['info'][$delta]['to_be_deleting']['#value'] == 1) {
          $deleted_files[] = $delta;
          preg_match("/\/(\d+)$/", $form['info'][$delta]['linked_media']['#context']['link'], $matches);
          if (isset($matches[1]) && is_numeric($matches[1])) {
            $deleted_media[] = $matches[1];
          }
        }
        $value += $form['info'][$delta]['to_be_deleting']['#value'];
      }
      if ($value == 0) {
        $form_state->setError($form['info'], 'You must select at least 1 file to delete.');
      }
      $form_state->set('deleted_files', $deleted_files);
      $form_state->set('deleted_media', $deleted_media);
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $file_ids = $form_state->get('deleted_files');
    $media_ids = $form_state->get('deleted_media');
    if ($file_ids) {
      $files = File::loadMultiple($file_ids);
      try {
        $this->fileStorage->delete($files);
      }
      catch (\Exception $exception) {
        watchdog_exception('tide_media', $exception);
      }
    }
    if ($media_ids) {
      $media = Media::loadMultiple($media_ids);
      try {
        $this->mediaStorage->delete($media);
      }
      catch (\Exception $exception) {
        watchdog_exception('tide_media', $exception);
      }
    }
    $this->entity->delete();
    parent::submitForm($form, $form_state);
  }

  /**
   * Try to get file entity.
   */
  private function getCurrentFile() {
    $entity_type_id = $this->entity->getEntityTypeId();
    switch ($entity_type_id) {
      case 'media':
        $fid = $this->entity->getSource()->getSourceFieldValue($this->entity);
        return File::load($fid);

      case 'file':
        return $this->entity;

      default:
        return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Full File Deletion');
  }

}
