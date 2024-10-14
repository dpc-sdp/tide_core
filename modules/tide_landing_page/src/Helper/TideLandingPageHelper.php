<?php

namespace Drupal\tide_landing_page\Helper;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * Helper functions.
 */
class TideLandingPageHelper {

  /**
   * Convert given date to local time format.
   *
   * @param string $date
   *   The date element to process.
   *
   * @return string
   *   The converted date.
   */
  public static function localDateAndTimeFormatter($date) {
    if (!empty($date)) {
      // Parse date with GMT timezone.
      $storage_tz = DateTimeItemInterface::STORAGE_TIMEZONE;
      $drupal_date_time = new DrupalDateTime($date, $storage_tz);
      // Convert to local timezone.
      $system_tz = \Drupal::service('config.factory')->get('system.date')->get('timezone.default');
      $drupal_date_time->setTimezone(new \DateTimeZone($system_tz));
      return $drupal_date_time->format(\DateTime::ATOM);
    }
  }

}
