<?php

namespace Drupal\tide_oauth\Authentication\Provider;

use Drupal\simple_oauth\Authentication\Provider\SimpleOauthAuthenticationProvider;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class XSimpleOauthAuthenticationProvider provides authentication.
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
    $auth_header = trim($request->headers->get('Authorization', '', TRUE));
    if ((strpos($auth_header, 'OAuth2 ') !== FALSE) || ($auth_header === 'OAuth2')) {
      $oauth2_request->headers->add([
        'Authorization' => str_replace('OAuth2', 'Bearer', $auth_header),
      ]);
    }
    else {
      $x_auth_header = trim($oauth2_request->headers->get('X-OAuth2-Authorization', '', TRUE));
      if (($x_auth_header === 'Bearer') || (strpos($x_auth_header, 'Bearer ') !== FALSE)) {
        $oauth2_request->headers->add(['Authorization' => $x_auth_header]);
      }
      elseif (($x_auth_header === 'OAuth2') || (strpos($x_auth_header, 'OAuth2 ') !== FALSE)) {
        $oauth2_request->headers->add([
          'Authorization' => str_replace('OAuth2', 'Bearer', $x_auth_header),
        ]);
      }
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
