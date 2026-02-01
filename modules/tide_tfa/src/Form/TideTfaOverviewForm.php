<?php

namespace Drupal\tide_tfa\Form;

use Drupal\tfa\Form\TfaOverviewForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\UserInterface;

class TideTfaOverviewForm extends TfaOverviewForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, UserInterface $user = NULL) {
    $output['info'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $this->t('Multi-factor authentication provides 
       additional security for your account. With multi-factor authentication enabled, 
       you log in to the CMS with a verification code in addition to your username and 
       password.') . '</p>',
    ];
    // $form_state['storage']['account'] = $user;.
    $config = $this->config('tfa.settings');
    $user_tfa = $this->tfaGetTfaData($user->id(), $this->userData);
    $enabled = isset($user_tfa['status']) && $user_tfa['status'];

    if ($config->get('enabled')) {
      $enabled = isset($user_tfa['status'], $user_tfa['data']) && !empty($user_tfa['data']['plugins']) && $user_tfa['status'];
      $enabled_plugins = $user_tfa['data']['plugins'] ?? [];

      $validation_plugins = $this->tfaValidation->getDefinitions();
      if ($validation_plugins) {
        $output['validation'] = [
          '#type' => 'details',
          '#title' => $this->t('Validation plugins'),
          '#open' => TRUE,
        ];

        foreach ($validation_plugins as $plugin_id => $plugin) {
          if (!empty($config->get('allowed_validation_plugins')[$plugin_id])) {
            $output['validation'][$plugin_id] = $this->tfaPluginSetupFormOverview($plugin, $user, !empty($enabled_plugins[$plugin_id]));
          }
        }
      }

      if ($enabled) {
        $login_plugins = $this->tfaLogin->getDefinitions();
        if ($login_plugins) {
          $output['login'] = [
            '#type' => 'details',
            '#title' => $this->t('Login plugins'),
            '#open' => TRUE,
            '#access' => FALSE,
          ];

          foreach ($login_plugins as $plugin_id => $plugin) {
            if (!empty($config->get('login_plugins')[$plugin_id])) {
              $output['login'][$plugin_id] = $this->tfaPluginSetupFormOverview($plugin, $user, TRUE);
              $output['login']['#access'] = TRUE;
            }
          }
        }

        $send_plugins = $this->tfaSend->getDefinitions();
        if ($send_plugins) {
          $output['send'] = [
            '#type' => 'details',
            '#title' => $this->t('Send plugins'),
            '#open' => TRUE,
          ];

          foreach ($send_plugins as $plugin_id => $plugin) {
            if (!empty($config->get('send_plugins')[$plugin_id])) {
              $output['send'][$plugin_id] = $this->tfaPluginSetupFormOverview($plugin, $user, TRUE);
            }
          }
        }
      }

      if (!empty($user_tfa)) {
        if ($enabled && !empty($user_tfa['data']['plugins'])) {
          $disable_url = Url::fromRoute('tfa.disable', ['user' => $user->id()]);
          if ($disable_url->access()) {
            $status_text = $this->t('Status: <strong>TFA enabled</strong>, set
            @time. <a href=":url">Disable TFA</a>', [
              '@time' => $this->dateFormatter->format($user_tfa['saved']),
              ':url' => $disable_url->toString(),
            ]);
          }
          else {
            $status_text = $this->t('Status: Multi-factor authentication enabled');
          }
        }
        else {
          $status_text = $this->t('Status: Multi-factor authentication disabled');
        }
        $output['status'] = [
          '#type' => 'markup',
          '#markup' => '<p>' . $status_text . '</p>',
        ];
      }

      $output['validation_skip_status'] = [
        '#type'   => 'markup',
        '#markup' => '<p>' . $this->t('Authentication setup: @remaining logins remain before multi-factor authentication is required', [
          '@remaining' => $config->get('validation_skip') - $user_tfa['validation_skipped'],
        ]) . '</p>',
      ];
    }
    else {
      $output['disabled'] = [
        '#type' => 'markup',
        '#markup' => '<b>Currently there are no enabled plugins.</b>',
      ];
    }

    if ($this->canPerformReset($user)) {
      $output['actions'] = ['#type' => 'actions'];
      $output['actions']['reset_skip_attempts'] = [
        '#type' => 'submit',
        '#value' => $this->t('Reset skip validation attempts'),
        '#submit' => ['::resetSkipValidationAttempts'],
      ];
      $output['account'] = [
        '#type' => 'value',
        '#value' => $user,
      ];
    }

    return $output;
  }
}