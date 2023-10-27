<?php

namespace Drupal\tide_tfa;

use Drupal\block\Entity\Block;

/**
 * Helper class for install/update ops.
 */
class TideTfaOperation {

  /**
   * TFA default validation plugin.
   */
  const DEFAULT_VALIDATION_PLUGIN = 'tfa_totp';

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
   * Name prefix.
   */
  const ISSUER = 'content-reference';

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
    $tfa_required_roles = [
      'authenticated' => 'authenticated',
      'administrator' => 'administrator',
      'approver' => 'approver',
      'site_admin' => 'site_admin',
      'editor' => 'editor',
      'previewer' => 'previewer',
      'contributor' => 'contributor',
      'event_author' => 'event_author',
      'grant_author' => 'grant_author',
      'site_auditor' => 'site_auditor',
    ];
    $allowed_validation_plugins = [
      'tfa_recovery_code' => 'tfa_recovery_code',
      self::DEFAULT_VALIDATION_PLUGIN => self::DEFAULT_VALIDATION_PLUGIN,
      'tfa_hotp' => 0,
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
        'issuer' => self::ISSUER,
      ],
      'tfa_recovery_code' => [
        'recovery_codes_amount' => '10',
      ],
      self::DEFAULT_VALIDATION_PLUGIN => [
        'time_skew' => '2',
        'site_name_prefix' => self::SITE_NAME_PREFIX,
        'name_prefix' => self::NAME_PREFIX,
        'issuer' => self::ISSUER,
      ],
    ];

    $tfa_settings = \Drupal::configFactory()->getEditable('tfa.settings');
    $tfa_settings->set('enabled', FALSE)
      ->set('required_roles', $tfa_required_roles)
      ->set('forced', 1)
      ->set('login_plugin_settings', $login_plugin_settings)
      ->set('allowed_validation_plugins', $allowed_validation_plugins)
      ->set('default_validation_plugin', self::DEFAULT_VALIDATION_PLUGIN)
      ->set('validation_plugin_settings', $validation_plugin_settings)
      ->set('encryption', self::ENCRYPTION_PROFILE)
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
