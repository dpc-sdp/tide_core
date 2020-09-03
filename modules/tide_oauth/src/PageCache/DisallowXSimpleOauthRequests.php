<?php

namespace Drupal\tide_oauth\PageCache;

use Drupal\simple_oauth\PageCache\DisallowSimpleOauthRequests;
use Symfony\Component\HttpFoundation\Request;

/**
 * Do not serve a page from cache if OAuth2 authentication is applicable.
 *
 * OAuth2 authentication accepts both Authorization and X-OAuth2-Authorization.
 *
 * @package Drupal\tide_oauth\PageCache
 */
class DisallowXSimpleOauthRequests extends DisallowSimpleOauthRequests {

  /**
   * {@inheritdoc}
   */
  public function isOauth2Request(Request $request) {
    // Accepts X-OAuth2-Authorization header for OAuth2 requests due to both
    // JWT and Simple OAuth use the same 'Authorization: Bearer xxx' header.
    $is_oauth2_requests = parent::isOauth2Request($request);
    if ($is_oauth2_requests) {
      return TRUE;
    }
    $x_auth_header = trim($request->headers->get('X-OAuth2-Authorization', '', TRUE));
    return (strpos($x_auth_header, 'Bearer ') !== FALSE) || ($x_auth_header === 'Bearer');
  }

}
