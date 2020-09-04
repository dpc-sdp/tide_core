<?php

namespace Drupal\tide_oauth\PageCache;

use Drupal\simple_oauth\PageCache\DisallowSimpleOauthRequests;
use Symfony\Component\HttpFoundation\Request;

/**
 * Do not serve a page from cache if OAuth2 authentication is applicable.
 *
 * OAuth2 authentication accepts those headers:
 *  - Authorization: Bearer <TOKEN>.
 *  - Authorization: OAuth2 <TOKEN>.
 *  - X-OAuth2-Authorization: Bearer <TOKEN>.
 *  - X-OAuth2-Authorization: OAuth2 <TOKEN>.
 *
 * @package Drupal\tide_oauth\PageCache
 */
class DisallowXSimpleOauthRequests extends DisallowSimpleOauthRequests {

  /**
   * {@inheritdoc}
   */
  public function isOauth2Request(Request $request) {
    $is_oauth2_requests = parent::isOauth2Request($request);
    if ($is_oauth2_requests) {
      return TRUE;
    }

    // Both JWT and Simple OAuth expects the same 'Authorization: Bearer TOKEN'
    // header so we accept the extra 'Authorization: OAuth2 TOKEN'.
    $auth_header = trim($request->headers->get('Authorization', '', TRUE));
    if ((strpos($auth_header, 'OAuth2 ') !== FALSE) || ($auth_header === 'OAuth2')) {
      return TRUE;
    }
    // Also accept 'X-OAuth2-Authorization: Bearer TOKEN'
    // and 'X-OAuth2-Authorization: OAuth2 TOKEN' headers.
    $x_auth_header = trim($request->headers->get('X-OAuth2-Authorization', '', TRUE));
    return (strpos($x_auth_header, 'Bearer ') !== FALSE) || ($x_auth_header === 'Bearer')
      || (strpos($x_auth_header, 'OAuth2 ') !== FALSE) || ($x_auth_header === 'OAuth2');
  }

}
