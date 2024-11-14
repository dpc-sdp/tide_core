<?php

namespace Drupal\tide_tfa\Controller;

use Drupal\Component\Utility\Crypt;
use Drupal\prlp\Controller\PrlpController;
use Drupal\tfa\Controller\TfaUserControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Custom controller to override the TfaUserControllerBase.
 */
class TideTfaUserController extends TfaUserControllerBase {

  /**
   * The request stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Get the parent instance with inherited dependencies.
    $instance = parent::create($container);
    $instance->requestStack = $container->get('request_stack');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function doResetPassLogin($uid, $timestamp, $hash, $request = NULL) {
    // Ensure a valid request object.
    if (!$request) {
      $request = $this->requestStack->getCurrentRequest();
    }

    // Check if the PRLP module is enabled.
    if (!\Drupal::moduleHandler()->moduleExists('prlp')) {
      // If PRLP is not enabled, call the parent method.
      return parent::doResetPassLogin($uid, $timestamp, $hash, $request);
    }

    // Create an instance of PrlpController.
    $prlp_controller = new PrlpController(
      \Drupal::service('date.formatter'),
      \Drupal::entityTypeManager()->getStorage('user'),
      \Drupal::service('user.data'),
      \Drupal::service('logger.factory')->get('prlp'),
      \Drupal::service('flood'),
      \Drupal::service('event_dispatcher')
    );

    /** @var \Drupal\user\UserInterface $user */
    $user = $this->userStorage->load($uid);
    $this->setUser($user);

    // Let Drupal core deal with the one-time login,
    // if TFA is not enabled or
    // current user can skip TFA while resetting password.
    if ($this->isTfaDisabled() || $this->canSkipPassReset()) {
      // Use PRLP's resetPassLogin instead of the core function.
      return $prlp_controller->prlpResetPassLogin($request, $uid, $timestamp, $hash);
    }

    // Whether the TFA Validation Plugin is set and ready for use.
    $tfa_ready = $this->isReady();

    // Check for authentication plugin.
    if ($tfa_ready && $this->pluginAllowsLogin()) {
      $this->messenger()->addStatus($this->t('You have logged in on a trusted browser.'));
      return $prlp_controller->prlpResetPassLogin($request, $uid, $timestamp, $hash);
    }

    // Borrow the following codes from the core function:
    $current = \Drupal::time()->getRequestTime();

    // Verify that the user exists and is active.
    if ($user === NULL || !$user->isActive()) {
      throw new AccessDeniedHttpException();
    }

    // Time out, in seconds, until login URL expires.
    $timeout = $this->config('user.settings')->get('password_reset_timeout');
    if ($user->getLastLoginTime() && $current - $timestamp > $timeout) {
      $this->messenger()->addError($this->t('You have tried to use a one-time login link that has expired. Please request a new one using the form below.'));
      return $this->redirect('user.pass');
    }
    elseif ($user->isAuthenticated() && ($timestamp >= $user->getLastLoginTime()) && ($timestamp <= $current) && hash_equals($hash, user_pass_rehash($user, $timestamp))) {
      if ($tfa_ready) {
        $this->session->migrate();
        $token = Crypt::randomBytesBase64(55);
        $request->getSession()->set('pass_reset_' . $uid, $token);

        $this->logger->notice('User %name used one-time login link at time %timestamp.', [
          '%name' => $user->getDisplayName(),
          '%timestamp' => $timestamp,
        ]);

        $this->tempStoreUid($user->id());

        return $this->redirect('tfa.entry', [
          'uid' => $uid,
          'hash' => $this->getLoginHash($user),
        ], [
          'query' => ['pass-reset-token' => $token],
          'absolute' => TRUE,
        ]);
      }
      else {
        if ($this->canLoginWithoutTfa($this->getLogger('tfa'))) {
          return $this->redirectToUserForm($user, $request, $timestamp);
        }
        else {
          return $this->redirect('<front>');
        }
      }
    }

    // Use PRLP's resetPassLogin instead of the core function.
    return $prlp_controller->prlpResetPassLogin($request, $uid, $timestamp, $hash);
  }

  /**
   * Determines if the user can skip tfa on password reset.
   *
   * This function checks the TFA settings to see if the option to skip TFA
   * during password reset is enabled. If enabled, users will not be required
   * to complete two-factor authentication when resetting their password.
   *
   * @return bool
   *   TRUE if the user can skip TFA on password reset, FALSE otherwise.
   */
  public function canSkipPassReset() {
    return $this->tfaSettings->get('reset_pass_skip_enabled');
  }

}
