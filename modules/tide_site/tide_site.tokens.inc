<?php

/**
 * @file
 * Tokens.
 */

use Drupal\tide_site\Hook\TideSiteHooks;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info_alter().
 */
#[\Drupal\Core\Hook\Attribute\LegacyHook]
function tide_site_token_info_alter(&$data) {
  return \Drupal::service(TideSiteHooks::class)->tokenInfoAlter($data);
}

/**
 * Implements hook_tokens().
 */
#[\Drupal\Core\Hook\Attribute\LegacyHook]
function tide_site_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  return \Drupal::service(TideSiteHooks::class)->tokens($type, $tokens, $data, $options, $bubbleable_metadata);
}
