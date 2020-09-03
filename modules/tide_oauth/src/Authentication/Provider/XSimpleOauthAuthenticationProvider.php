<?php

namespace Drupal\tide_oauth\Authentication\Provider;

use Drupal\simple_oauth\Authentication\Provider\SimpleOauthAuthenticationProvider;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class XSimpleOauthAuthenticationProvider.
 *
 * @internal
 * @package Drupal\tide_oauth\Authentication\Provider
 */
class XSimpleOauthAuthenticationProvider extends SimpleOauthAuthenticationProvider {

  /**
   * {@inheritdoc}
   */
  public function authenticate(Request $request) {
    // X-OAuth2-Authorization does not comply to OAuth2 so that we need to
    // set Authorization header as per the OAuth2 specs.
    // However, Authorization header will trigger JWT Authentication (if exists)
    // hence we need to clone the request instead of modifying the original.
    $oauth2_request = clone $request;
    $x_auth_header = trim($oauth2_request->headers->get('X-OAuth2-Authorization', '', TRUE));
    if (($x_auth_header === 'Bearer') || (strpos($x_auth_header, 'Bearer ') !== FALSE)) {
      $oauth2_request->headers->add(['Authorization' => $x_auth_header]);
    }

    $account = parent::authenticate($oauth2_request);
    if ($account) {
      // Inherit uploaded files for the current request.
      /* @link https://www.drupal.org/project/drupal/issues/2934486 */
      $request->files->add($oauth2_request->files->all());
      // Set consumer ID header on successful authentication, so negotiators
      // will trigger correctly.
      $request->headers->set('X-Consumer-ID', $account->getConsumer()->uuid());
    }

    return $account;
  }

}
