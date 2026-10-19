<?php

namespace Drupal\tide_block_inactive_users\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\UserInterface;

/**
 * Hook implementations for the tide block inactive users module.
 */
class TideBlockInactiveUsersHooks {

  /**
   * Implements hook_cron().
   */
  #[Hook('cron')]
  public function cron() {
    $config = \Drupal::configFactory()->get('tide_block_inactive_users.settings');
    if ($config->get('cron') === TRUE) {
      \Drupal::service('tide_inactive_users_management.commands')->notify();
      \Drupal::service('tide_inactive_users_management.commands')->block();
    }
  }

  /**
   * Implements hook_form_alter().
   */
  #[Hook('form_alter')]
  public function formAlter(&$form, FormStateInterface $form_state, $form_id) {
    if ($form_id === 'tide_block_inactive_users_settings') {
      unset($form['actions']['block_inactive_users_update']);
    }
    if ($form_id === 'block_inactive_users_settings') {
      $route_name = \Drupal::routeMatch()->getRouteName();
      if ($route_name === 'block_inactive_users.settings') {
        if (isset($form['actions']['block_inactive_users_update'])) {
          $form['actions']['block_inactive_users_update']['#access'] = FALSE;
        }
        if (isset($form['users_settings']['block_inactive_users_idle_time']['#attributes'])) {
          $form['users_settings']['block_inactive_users_idle_time']['#attributes']['min'] = 1;
        }
        if (isset($form['email_settings']['block_inactive_users_send_email'])) {
          $form['email_settings']['block_inactive_users_send_email']['#access'] = FALSE;
          $form['email_settings']['block_inactive_users_from_email']['#required'] = TRUE;
          $form['email_settings']['block_inactive_users_email_subject']['#required'] = TRUE;
          $form['email_settings']['block_inactive_users_email_content']['#required'] = TRUE;
        }
        if (isset($form['users_settings']['block_inactive_users_idle_time']['#description'])) {
          $form['users_settings']['block_inactive_users_idle_time']['#description'] = t('Notify inactive users.');
        }
      }
    }
  }

  /**
   * Implements hook_user_login().
   */
  #[Hook('user_login')]
  public function userLogin(UserInterface $account) {
    // Try to remove the key/value if user logged in.
    \Drupal::keyValue('tide_inactive_users_management')->delete($account->id());
  }

  /**
   * Implements hook_mail().
   */
  #[Hook('mail')]
  public function mail($key, &$message, $params) {
    switch ($key) {
      case 'inactive_users':
        $message['from'] = $params['from'];
        $message['subject'] = $params['subject'];
        $message['body'][] = $params['message'];
        break;
    }
  }

}
