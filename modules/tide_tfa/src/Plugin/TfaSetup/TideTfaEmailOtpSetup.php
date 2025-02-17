<?php

namespace Drupal\tide_tfa\Plugin\TfaSetup;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\tfa_email_otp\Plugin\TfaSetup\TfaEmailOtpSetup;

/**
 * Tide TFA Email OTP Setup class.
 *
 * @TfaSetup(
 *   id = "tfa_email_otp_setup",
 *   label = @Translation("TFA Email OTP Setup"),
 *   description = @Translation("Email OTP Setup Plugin"),
 *   setupMessages = {
 *    "saved" = @Translation("Email OTP set."),
 *    "skipped" = @Translation("Email OTP not enabled.")
 *   }
 * )
 */
class TideTfaEmailOtpSetup extends TfaEmailOtpSetup {

  /**
   * {@inheritdoc}
   */
  public function getSetupForm(array $form, FormStateInterface $form_state) {
    $params = $form_state->getValues();
    $userData = $this->userData->get('tfa', $params['account']->id(), 'tfa_email_otp');

    // [SD-294] Changing the title and description.
    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Yes, email me a verification code every time I log in'),
      '#description' => $this->t('Each single-use verification code expires after use, or after 10 minutes if not used.'),
      '#required' => TRUE,
      '#default_value' => $userData['enable'] ?? 0,
    ];
    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['save'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getOverview(array $params) {
    $plugin_text = $this->t('Validation Plugin: @plugin', [
      '@plugin' => str_replace(' Setup', '', $this->getLabel()),
    ]);
    // [SD-294] Modify the description.
    $description = '';
    if ($params['enabled']) {
      $description .= $this->t('<p><b>Enabled</b></p>');
    }
    $output = [
      'heading' => [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        // [SD-294] Modify the heading value here.
        '#value' => $this->t('Email verification code'),
      ],
      'description' => [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $description,
      ],
      'link' => [
        '#theme' => 'links',
        '#access' => !$params['enabled'],
        '#links' => [
          'admin' => [
            'title' => $this->t('Enable two-factor authentication via email'),
            'url' => Url::fromRoute('tfa.validation.setup', [
              'user' => $params['account']->id(),
              'method' => $params['plugin_id'],
            ]),
          ],
        ],
      ],
    ];

    return $output;
  }

}
