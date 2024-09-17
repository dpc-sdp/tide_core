<?php

/**
 * @file
 * API.
 */

/**
 * Alter data before posting.
 *
 * By implementing this hook and enabling `tide_remote_post` as your POST
 * handler, you can modify the body of POST.
 * This hook mostly uses for dealing with composite data, and it doesn't break
 * webform storage.
 *
 * @param array $data
 *   Array keyed by webform field name.
 */
function hook_tide_webform_post_alter(array &$data, \Drupal\webform\Plugin\WebformHandlerInterface $handler) {
  if ($handler->getWebform()->id() == 'an_example_tide_webform_id') {
    if ($data['a_field_name']) {
      $data['a_field_name'] = [['hello'], ['word']];
    }
  }

}

/**
 * Alter request options(custom options) before posting.
 *
 * By implementing this hook and enabling `tide_remote_post` as your POST
 * handler, you can modify the request options of POST.
 *
 * @param array $options
 *   Array keyed by an option name. 'auth' for instance.
 */
function hook_tide_webform_request_options_alter(array &$options, \Drupal\webform\Plugin\WebformHandlerInterface $handler) {
  if ($handler->getWebform()->id() == 'an_example_tide_webform_id') {
    if ($options['an_auth_option']) {
      $options['an_auth_option'] = ['username', 'password'];
    }
  }

}
