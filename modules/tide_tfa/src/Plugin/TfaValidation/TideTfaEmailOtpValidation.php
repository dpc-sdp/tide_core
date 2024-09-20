<?php

namespace Drupal\tide_tfa\Plugin\TfaValidation;

use Drupal\Core\Form\FormStateInterface;
use Drupal\tfa_email_otp\Plugin\TfaValidation\TfaEmailOtpValidation;

/**
 * Custom TFA Email OTP validation class for overriding methods.
 *
 * @TfaValidation(
 *   id = "tfa_email_otp",
 *   label = @Translation("TFA Email one-time password (EOTP)"),
 *   description = @Translation("TFA Email OTP Validation Plugin"),
 *   setupPluginId = "tfa_email_otp_setup",
 * )
 */
class TideTfaEmailOtpValidation extends TfaEmailOtpValidation {

  /**
   * {@inheritdoc}
   */
  public function validateForm(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    // If user is asking for sending the code,
    // no need to validate the input.
    // [SD-294].
    // Updating the op string to match with form alter change.
    if (isset($values['op']) && $values['op']->getUntranslatedString() === 'Email me a verification code') {
      // Send user the access code.
      $this->send();

      // TFA entry form requires an array for error messages,
      // when validation failed.
      // As sending a code to user is not an error,
      // We set an empty error message to avoid showing errors.
      $this->errorMessages['send'] = '';
      return FALSE;
    }

    if (!$this->validate($values['code'])) {
      if (!isset($this->errorMessages['code'])) {
        $this->errorMessages['code'] = $this->t('Invalid authentication code. Please try again.');
      }
      if ($this->alreadyAccepted) {
        $form_state->clearErrors();
        $this->errorMessages['code'] = $this->t('Invalid code, it was recently used for a login. Please try a new code.');
      }
      return FALSE;
    }
    else {
      // Store accepted code to prevent replay attacks.
      $this->storeAcceptedCode($values['code']);
      return TRUE;
    }
  }
}
