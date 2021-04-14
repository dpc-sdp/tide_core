<?php

namespace Drupal\tide_core\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;

/**
 * Enhanced MIME Type Filter.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("tide_enhanced_mime_type_filter")
 */
class EnhancedMineTypeFilter extends InOperator {

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->valueTitle = t('Enhanced MIME Type Filter');
    $this->definition['options callback'] = [$this, 'generateOptions'];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    if (!empty($this->value)) {
      parent::query();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    if (!empty($this->value)) {
      parent::validate();
    }
  }

  /**
   * Helper function that generates the options.
   */
  public function generateOptions() {
    return [
      'image/png' => 'PNG',
      'application/pdf' => 'PDF',
      'image/jpeg' => 'JPG',
      'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'DOCX',
      'image/svg+xml' => 'SVG',
      'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'XLSX',
      'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'PPTX',
      'application/msword' => 'DOC',
      'application/zip' => 'ZIP',
      'application/postscript' => 'EPS',
      'text/calendar' => 'ICS',
      'text/plain' => 'TXT',
      'application/rtf' => 'RTF',
      'image/gif' => 'GIF',
      'image/tiff' => 'TIFF',
      'audio/mpeg' => 'MP3',
      'application/vnd.ms-excel' => 'XLS',
      'application/vnd.ms-powerpoint' => 'PPT',
    ];
  }

}
