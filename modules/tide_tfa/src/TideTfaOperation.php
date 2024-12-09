<?php

namespace Drupal\tide_tfa;

use Drupal\block\Entity\Block;
use Drupal\user\Entity\Role;

/**
 * Helper class for install/update ops.
 */
class TideTfaOperation {

  /**
   * TFA default validation plugin.
   */
  const DEFAULT_VALIDATION_PLUGIN = 'tfa_email_otp';

  /**
   * Encryption profile.
   */
  const ENCRYPTION_PROFILE = 'tfa_encryption';

  /**
   * Site name prefix.
   */
  const SITE_NAME_PREFIX = 1;

  /**
   * Name prefix.
   */
  const NAME_PREFIX = 'TFA';

  /**
   * Disabled site alert for TFA routes.
   */
  public static function disabledSiteAlertTfa() {
    $block = Block::load('tide_site_alert_header');
    if (\Drupal::service('module_handler')->moduleExists('tide_site_alert') && $block) {
      $block->setVisibilityConfig('request_path', [
        'id' => 'request_path',
        'pages' => "/user/*/security/tfa\n/user/*/security/tfa/*",
        'negate' => TRUE,
      ]);
      $block->save();
    }
  }

  /**
   * Setup TFA settings.
   */
  public static function setupTfaSettings() {
    // Get site name.
    $system_site_config = \Drupal::configFactory()->get('system.site');
    $site_name = $system_site_config->get('name');
    // Load all roles.
    $roles = Role::loadMultiple();
    // Initialize the $tfa_required_roles array.
    $tfa_required_roles = [];
    // Define the roles to exclude in a variable.
    $excluded_roles = ['authenticated', 'previewer', 'secure_file_user'];

    if (!empty($roles)) {
      // Iterate through the roles and map the role IDs.
      foreach ($roles as $role) {
        // Check if the current role is not in the excluded roles.
        if (!in_array($role->id(), $excluded_roles)) {
          // Map the role ID to itself.
          $tfa_required_roles[$role->id()] = $role->id();
        }
      }
    }

    $allowed_validation_plugins = [
      'tfa_recovery_code' => 0,
      'tfa_totp' => 0,
      'tfa_hotp' => 0,
      'tfa_email_otp' => 'tfa_email_otp',
    ];
    $login_plugin_settings = [
      'tfa_trusted_browser' => [
        'cookie_allow_subdomains' => 1,
        'cookie_expiration' => '30',
        'cookie_name' => 'tfa-trusted-browser',
      ],
    ];
    $validation_plugin_settings = [
      'tfa_hotp' => [
        'counter_window' => '10',
        'site_name_prefix' => self::SITE_NAME_PREFIX,
        'name_prefix' => self::NAME_PREFIX,
        'issuer' => $site_name,
      ],
      'tfa_recovery_code' => [
        'recovery_codes_amount' => '10',
      ],
      'tfa_totp' => [
        'time_skew' => '2',
        'site_name_prefix' => self::SITE_NAME_PREFIX,
        'name_prefix' => self::NAME_PREFIX,
        'issuer' => $site_name,
      ],
      'tfa_email_otp' => [
        'code_validity_period' => '600',
        'email_setting' => [
          'subject' => 'Single Digtial Presence CMS two-factor authentication code',
          'body' => '[user:display-name],\r\n\r\nThis code is valid for [length] minutes. \r\n\r\nYour code is: [code]\r\n\r\nThis code will expire once you have logged in.',
        ],
      ],
    ];
    $mail_settings = [
      'tfa_enabled_configuration' => [
        'subject' => 'Your Single Digtial Presence CMS account now has two-factor authentication',
        'body' => "[user:display-name],\r\n\r\nThanks for configuring two-factor authentication on your Single Digital Presence account!\r\n\r\nThis additional level of security will help to ensure that only you are able to log in to your account.\r\n\r\nIf you ever lose the device you configured, you should act quickly to delete its association with this account.\r\n\r\nFrom the SDP team\r\n\r\nRead more about 2FA: https://digital-vic.atlassian.net/servicedesk/customer/article/2439479507",
      ],
      'tfa_disabled_configuration' => [
        'subject' => 'Your Single Digtial Presence CMS account now has two-factor authentication',
        'body' => "[user:display-name],\r\n\r\nThanks for configuring two-factor authentication on your Single Digital Presence account!\r\n\r\nThis additional level of security will help to ensure that only you are able to log in to your account.\r\n\r\nIf you ever lose the device you configured, you should act quickly to delete its association with this account.\r\n\r\nFrom the SDP team\r\n\r\nRead more about 2FA: https://digital-vic.atlassian.net/servicedesk/customer/article/2439479507",
      ],
    ];

    $tfa_settings = \Drupal::configFactory()->getEditable('tfa.settings');
    $tfa_settings->set('enabled', FALSE)
      ->set('required_roles', $tfa_required_roles)
      ->set('login_plugin_settings', $login_plugin_settings)
      ->set('allowed_validation_plugins', $allowed_validation_plugins)
      ->set('default_validation_plugin', self::DEFAULT_VALIDATION_PLUGIN)
      ->set('validation_plugin_settings', $validation_plugin_settings)
      ->set('encryption', self::ENCRYPTION_PROFILE)
      ->set('users_without_tfa_redirect', TRUE)
      ->set('reset_pass_skip_enabled', TRUE)
      ->set('mail', $mail_settings)
      ->save();
  }

  /**
   * Setup TFA role permissions.
   */
  public static function setupTfaRolePermissions() {
    $roles_permissions = [
      'site_admin' => ['admin tfa settings'],
      'authenticated' => ['setup own tfa'],
    ];

    foreach ($roles_permissions as $rid => $permissions) {
      user_role_grant_permissions($rid, $permissions);
    }
  }

}
