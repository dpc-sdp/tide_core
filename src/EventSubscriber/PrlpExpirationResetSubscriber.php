<?php

namespace Drupal\tide_core\EventSubscriber;

use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\prlp\Event\PrlpPasswordBeforeSaveEvent;
use Drupal\prlp\PrlpEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Resets the password expiration status when the password is changed via PRLP.
 */
class PrlpExpirationResetSubscriber implements EventSubscriberInterface {

  use LoggerChannelTrait;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      PrlpEvents::PASSWORD_BEFORE_SAVE => ['resetPasswordExpiration', 0],
    ];
  }

  /**
   * Triggered when a user changes their password via PRLP.
   */
  public function resetPasswordExpiration(PrlpPasswordBeforeSaveEvent $event) {
    $user = $event->getUser();
    try {
      $date = \Drupal::service('date.formatter')->format(
        \Drupal::time()->getRequestTime(), 'custom',
        DateTimeItemInterface::DATETIME_STORAGE_FORMAT,
        DateTimeItemInterface::STORAGE_TIMEZONE
      );
      $user->set('field_last_password_reset', $date);
      $user->set('field_password_expiration', '0');
      $user->set('field_pending_expire_sent', '0');
    }
    catch (\InvalidArgumentException $e) {
      $this->getLogger('tide_core')->error(
        'Error resetting password expiration fields for User @uid: @message',
        ['@uid' => $user->id(), '@message' => $e->getMessage()]);
      \Drupal::messenger()->addError(
            t('There was an issue updating your password status. Please contact your site maintainer.')
          );
    }
  }

}
