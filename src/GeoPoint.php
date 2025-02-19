<?php

namespace Drupal\tide_core;

use Drupal\node\NodeInterface;

/**
 * Provides methods for handling geolocation-related functionality.
 *
 * @package Drupal\tide_core
 */
class GeoPoint {

  /**
   * Helper function to extract and validate latitude and longitude from a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node object to extract latitude and longitude from.
   *
   * @return string|bool
   *   Returns the valid latitude, longitude as a string in the format "lat,long"
   *   or FALSE if the latitude/longitude is invalid.
   */
  public function extractAndValidateLatLong(NodeInterface $node) {
    // Check if the node has the required latitude and longitude fields.
    if (
      $node->hasField('field_latitude') && $node->hasField('field_longitude')
      && !$node->get('field_latitude')->isEmpty() && !$node->get('field_longitude')->isEmpty()
    ) {
      // Retrieve the latitude and longitude values from the node.
      $latitude = $node->get('field_latitude')->value;
      $longitude = $node->get('field_longitude')->value;

      // Validate if latitude, longitude are numeric and within the valid ranges.
      if (is_numeric($latitude) && is_numeric($longitude)) {
        // Cast the values to float after validation.
        $latitude = (float) $latitude;
        $longitude = (float) $longitude;

        // Validate the latitude and longitude ranges.
        if (
          $latitude > -90 && $latitude < 90
          && $longitude > -180 && $longitude < 180
        ) {
          return "{$latitude},{$longitude}";
        }
      }
    }

    return FALSE;
  }

}
