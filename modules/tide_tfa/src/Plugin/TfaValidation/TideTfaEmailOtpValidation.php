<?php

namespace Drupal\tide_tfa\Plugin\TfaValidation;

use Drupal\Core\Render\Markup;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\ReplaceCommand;
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
  public function getForm(array $form, FormStateInterface $form_state) {
    $code_sent = $this->hasActiveOtp();

    // Send automatically on first land.
    if (!$code_sent && empty($userInput)) {
      $this->send();
    }

    // Hide the heading if the resend link was clicked.
    $form['email_otp_entry_heading'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Enter the 8-digit verification code that was sent to your registered email.'),
      '#prefix' => '<div id="tfa-email-heading-wrapper">',
      '#suffix' => '</div>',
    ];

    $form['send_message_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'send-button-wrapper'],
    ];

    // Used in Ajax call back.
    // To hide the emepty error box.
    // This makes sure to not load the whole form.
    $form['messages'] = [
      '#markup' => '<div class="messages hidden"></div>',
      '#prefix' => '<div id="tfa-email-message-area">',
      '#suffix' => '</div>',
    ];

    $form['code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Verification code'),
      '#required' => TRUE,
      '#description' => $this->t('The verification code field is mandatory.'),
      '#attributes' => ['autocomplete' => 'off'],
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['#attributes']['style'] = 'display: block;';
    $form['actions']['login'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Verify'),
      '#prefix' => '<div id="tfa-email-verify-button">',
      '#suffix' => '</div>',
    ];

    // Convert the Resend button into a link-style.
    $form['actions']['rsend_link'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send me a new verification code.'),
      '#prefix' => '<div id="tfa-email-send-button">' . $this->t('Didn’t receive an email or need a new code?&nbsp;'),
      '#suffix' => '</div>',
      '#attributes' => [
        'style' => 'background:none; border:none; padding:0; color:#003CC5; text-decoration:underline; cursor:pointer; box-shadow:none; font-size: inherit; font-weight:400;',
      ],
      '#limit_validation_errors' => [['']],
      '#ajax' => [
        'callback' => [$this, 'updateButtonValue'],
        'event' => 'click',
        'wrapper' => 'send-button-wrapper',
        'progress' => ['type' => 'throbber', 'message' => $this->t('Sending...')],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    // If user is asking for sending the code,
    // no need to validate the input.
    if (isset($values['op']) && in_array($values['op']->getUntranslatedString(), ['Send me a new verification code.'], TRUE)) {
      // Check flood control for email sending to prevent email bombing.
      $flood_identifier = 'tfa_email_otp_send_' . $this->uid;
      if (!$this->flood->isAllowed('tfa_email_otp.send', static::EMAIL_SEND_FLOOD_THRESHOLD, static::EMAIL_SEND_FLOOD_WINDOW, $flood_identifier)) {
        $this->errorMessages['send'] = $this->t('Too many code requests. Please wait before requesting another code.');
        return FALSE;
      }

      // Register the send attempt before sending.
      $this->flood->register('tfa_email_otp.send', static::EMAIL_SEND_FLOOD_WINDOW, $flood_identifier);

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
        $this->errorMessages['code'] = $this->t('Enter a valid verification code.');
      }
      return FALSE;
    }
    else {
      return TRUE;
    }
  }

  /**
   * AJAX callback to update the form buttons and display status messages.
   *
   * This function is triggered when the "resend-link" submit is clicked.
   * This updates the message area with any status messages.
   *
   * @param array &$form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response to send back to the browser, containing the updated
   *   form elements and any status messages.
   */
  public function updateButtonValue(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // Clear all pending messages from the Drupal system.
    // This ensures they don't pop up later if the page is refreshed.
    \Drupal::messenger()->deleteAll();

    // Wipe the message area in the browser.
    // By sending an empty string to the #tfa-email-message-area,
    // Remove any existing HTML (the green or red boxes).
    $response->addCommand(new HtmlCommand('#tfa-email-message-area', ''));

    $response->addCommand(new InvokeCommand('#tfa-email-heading-wrapper', 'addClass', ['hidden']));

    $message_output = $this->buildIdentityConfirmationMessage();
    $response->addCommand(new ReplaceCommand('#send-button-wrapper', $message_output));

    // Update the buttons as before.
    $response->addCommand(new HtmlCommand('#tfa-email-verify-button', $form['actions']['login']));
    $response->addCommand(new HtmlCommand('#tfa-email-send-button', $form['actions']['rsend_link']));

    return $response;
  }

  /**
   * Helper to build the custom TFA identity confirmation message.
   *
   * @return array
   * A render array containing the markup.
   */
  private function buildIdentityConfirmationMessage(): array {
    $module_handler = \Drupal::service('module_handler');
    $path = $module_handler->getModule('tide_tfa')->getPath();
    $icon_url = base_path() . $path . '/icons/info–icon.png';

    // Build the HTML string manually.
    // So the tags does not get stripped out.
    $html = '<div id="send-button-wrapper">';
    $html .= '  <div class="messages messages--status" role="contentinfo" style="border-left: 6px solid #3371FF; background-image: none !important;">';
    
    // Header with custom icon
    $html .= '<div class="messages__header">';
    $html .= '  <span style="background: #FFFFFF url(' . $icon_url . ') no-repeat center; ' .
            'background-size: 21px; width: 20px; height: 20px; border-radius: 10px; ' .
            'display: inline-block; margin-right: 20px;"></span>';
    $html .= '  <strong">' . $this->t('Confirm your identity') . '</strong>';
    $html .= '</div>';

    // Content
    $html .= '    <div class="messages__content"">';
    $html .=        $this->t('You have requested a new verification code. Enter the 8-digit verification code that was sent to your registered email.');
    $html .= '    </div>';
    
    $html .= '  </div>';
    $html .= '</div>';

    return [
      '#markup' => Markup::create($html),
    ];
  }

}
