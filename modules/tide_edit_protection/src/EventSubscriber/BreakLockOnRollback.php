<?php

namespace Drupal\tide_edit_protection\EventSubscriber;

use Drupal\content_lock\ContentLock\ContentLock;
use Drupal\Core\Language\LanguageInterface;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateRowDeleteEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Break content lock on migration rollback.
 *
 * @package Drupal\tide_edit_protection\EventSubscriber
 */
class BreakLockOnRollback implements EventSubscriberInterface {

  /**
   * Content lock.
   *
   * @var \Drupal\content_lock\ContentLock\ContentLock
   */
  protected $contentLock;

  /**
   * BreakLockOnRollback constructor.
   *
   * @param \Drupal\content_lock\ContentLock\ContentLock $content_lock
   *   Content lock.
   */
  public function __construct(ContentLock $content_lock) {
    $this->contentLock = $content_lock;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      MigrateEvents::PRE_ROW_DELETE => 'breakLockOnRollback',
    ];
  }

  /**
   * Break content lock upon migration rollback.
   *
   * @param \Drupal\migrate\Event\MigrateRowDeleteEvent $event
   *   The migration row delete event.
   */
  public function breakLockOnRollback(MigrateRowDeleteEvent $event) {
    if ($event->getMigration()->getDestinationPlugin()->getPluginId() !== 'entity:node') {
      return;
    }

    $destination_ids = $event->getDestinationIdValues();
    $destination_id = reset($destination_ids);
    $this->contentLock->release($destination_id, LanguageInterface::LANGCODE_NOT_SPECIFIED);
  }

}
