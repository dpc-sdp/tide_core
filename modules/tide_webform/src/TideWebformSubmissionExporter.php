<?php

namespace Drupal\tide_webform;

use Drupal\Core\Archiver\ArchiverManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\user\Entity\User;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\Plugin\WebformExporterManagerInterface;
use Drupal\webform\WebformSubmissionExporter;
use Drupal\webform\WebformSubmissionExporterInterface;

/**
 * Webform submission exporter.
 */
class TideWebformSubmissionExporter extends WebformSubmissionExporter {

  /**
   * The inner service.
   *
   * @var \Drupal\webform\WebformSubmissionExporterInterface
   */
  protected $webformSubmissionExporter;

  /**
   * TideWebformSubmissionExporter function.
   *
   * @param \Drupal\webform\Plugin\WebformSubmissionExporterInterface $webformSubmissionExporter
   *   The webform submission exporter interface.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   File system service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager
   *   The stream wrapper manager.
   * @param \Drupal\Core\Archiver\ArchiverManager $archiver_manager
   *   The archiver manager.
   * @param \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager
   *   The webform element manager.
   * @param \Drupal\webform\Plugin\WebformExporterManagerInterface $exporter_manager
   *   The results exporter manager.
   */
  public function __construct(WebformSubmissionExporterInterface $webformSubmissionExporter, ConfigFactoryInterface $config_factory, FileSystemInterface $file_system, EntityTypeManagerInterface $entity_type_manager, StreamWrapperManagerInterface $stream_wrapper_manager, ArchiverManager $archiver_manager, WebformElementManagerInterface $element_manager, WebformExporterManagerInterface $exporter_manager) {
    $this->webformSubmissionExporter = $webformSubmissionExporter;
    parent::__construct($config_factory, $file_system, $entity_type_manager, $stream_wrapper_manager, $archiver_manager, $element_manager, $exporter_manager);
  }

  /**
   * {@inheritdoc}
   */
  public function getQuery(): QueryInterface {
    $query = parent::getQuery();
    $export_options = $this->getExportOptions();

    if ($export_options['unprocessed_submissions']) {
      $query->condition('processed', 0);
    }
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function generate() {
    parent::generate();
    $export_options = $this->getExportOptions();
    $entity_ids = $this->getQuery()->execute();
    if (isset($export_options['process_submissions']) && $export_options['process_submissions'] == 1 && !empty($entity_ids)) {
      \Drupal::database()->update('webform_submission')
        ->fields(['processed' => 1])
        ->condition('sid', $entity_ids, 'IN')
        ->execute();
      \Drupal::entityTypeManager()
        ->getStorage('webform_submission')
        ->resetCache($entity_ids);
    }

    $this->logExport($entity_ids, $export_options);
  }

  /**
   * Log export information to audit trail and Drupal logger.
   */
  public function logExport($entity_ids, $export_options) {
    $account = User::load(\Drupal::currentUser()->id());

    \Drupal::logger('tide_webform')->notice('@user exported @number webform submissions',
    [
      '@user' => $account->getDisplayName(),
      '@number' => count($entity_ids),
    ]);

    $log = [
      'type' => 'tide_webform',
      'operation' => 'download',
      'description' => t('event: @user exported @number webform submissions', [
        '@user' => $account->getDisplayName(),
        '@number' => count($entity_ids),
      ]),
      'ref_char' => $export_options['webform']->id(),
    ];
    admin_audit_trail_insert($log);

  }

}
