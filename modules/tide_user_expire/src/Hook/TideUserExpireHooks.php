<?php

namespace Drupal\tide_user_expire\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Form\FormStateInterface;

/**
 * Hook implementations for the tide user expire module.
 */
class TideUserExpireHooks {

  /**
   * Implements hook_form_alter().
   */
  #[Hook('form_alter')]
  public function formAlter(&$form, FormStateInterface $form_state, $form_id) {
    // Validate user expire date.
    if (($form_id === "user_form") || ($form_id === 'user_register_form')) {
      $form['#validate'][] = '_tide_user_expire_validate';
    }

    // Alter user expire form.
    if ($form_id === 'user_expire_form') {
      $config = \Drupal::configFactory()->get('tide_user_expire.settings');
      $form['email_settings'] = [
        '#type' => 'details',
        '#title' => t('Email notification'),
      ];
      $form['email_settings']['tide_user_expire_from_email'] = [
        '#title' => t('From'),
        '#type' => 'textfield',
        '#required' => TRUE,
        '#default_value' => $config->get('tide_user_expire_from_email'),
        '#description' => t('Email "from" email address configuration.'),
      ];

      $form['email_settings']['tide_user_expire_email_subject'] = [
        '#title' => t('Email subject'),
        '#type' => 'textfield',
        '#required' => TRUE,
        '#default_value' => $config->get('tide_user_expire_email_subject'),
        '#description' => t('Email subject text.'),
      ];

      $form['email_settings']['tide_user_expire_email_content'] = [
        '#title' => t('Email template'),
        '#type' => 'textarea',
        '#required' => TRUE,
        '#default_value' => $config->get('tide_user_expire_email_content'),
        '#description' => t('Notify inactive users.'),
      ];

      // Add extra settings fields.
      $form['#submit'][] = 'tide_user_expire_submit';
    }
  }

  /**
   * Implements hook_mail_alter().
   */
  #[Hook('mail_alter')]
  public function mailAlter(&$message) {
    // Use email template setting in the config.
    if ($message['id'] === 'user_expire_expiration_warning') {
      $config = \Drupal::configFactory()->get('tide_user_expire.settings');
      $params = $message['params'];
      $token_data = [
        'user' => $params['account'],
      ];
      $message['from'] = $config->get('tide_user_expire_from_email');
      $message['subject'] = $config->get('tide_user_expire_email_subject');
      $token = Drupal::token();
      $body = $token->replace($config->get('tide_user_expire_email_content'), $token_data);

      unset($message['body']);
      $message['body'][] = $body;
    }
  }

}
