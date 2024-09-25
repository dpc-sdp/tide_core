<?php

namespace Drupal\tide_webform\Plugin\WebformHandler;

use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Url;
use Drupal\webform\Plugin\WebformHandler\RemotePostWebformHandler;
use Drupal\webform\WebformSubmissionInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;

/**
 * Tide Webform submission remote post handler.
 *
 * @WebformHandler(
 *   id = "tide_remote_post",
 *   label = @Translation("Tide remote post"),
 *   category = @Translation("External"),
 *   description = @Translation("Posts webform submissions to a URL with a chance to modify it."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 *   tokens = TRUE,
 * )
 */
class TideRemotePostWebformHandler extends RemotePostWebformHandler {

  /**
   * {@inheritdoc}
   */
  protected function getRequestData($state, WebformSubmissionInterface $webform_submission) {
    $data = parent::getRequestData($state, $webform_submission);
    \Drupal::moduleHandler()->alter('tide_webform_post', $data, $this);
    return $data;
  }

  /**
   * This is copied from its parent. Just for adding the alter hook.
   */
  protected function remotePost($state, WebformSubmissionInterface $webform_submission) {
    $state_url = $state . '_url';
    if (empty($this->configuration[$state_url])) {
      return;
    }

    $this->messageManager->setWebformSubmission($webform_submission);

    $request_url = $this->configuration[$state_url];
    $request_url = $this->replaceTokens($request_url, $webform_submission);
    $request_method = (!empty($this->configuration['method'])) ? $this->configuration['method'] : 'POST';
    $request_type = ($request_method !== 'GET') ? $this->configuration['type'] : NULL;

    // Get request options with tokens replaced.
    $request_options = (!empty($this->configuration['custom_options'])) ? Yaml::decode($this->configuration['custom_options']) : [];
    $request_options = $this->replaceTokens($request_options, $webform_submission);
    \Drupal::moduleHandler()
      ->alter('tide_webform_request_options', $request_options, $this);
    try {
      if ($request_method === 'GET') {
        // Append data as query string to the request URL.
        $query = $this->getRequestData($state, $webform_submission);
        $request_url = Url::fromUri($request_url, ['query' => $query])
          ->toString();
        $response = $this->httpClient->get($request_url, $request_options);
      }
      else {
        $method = strtolower($request_method);
        $request_options[($request_type === 'json' ? 'json' : 'form_params')] = $this->getRequestData($state, $webform_submission);
        $response = $this->httpClient->$method($request_url, $request_options);
      }
    }
    catch (RequestException $request_exception) {
      $response = $request_exception->getResponse();

      // Encode HTML entities to prevent broken markup from breaking the page.
      $message = $request_exception->getMessage();
      $message = nl2br(htmlentities($message));

      $this->handleError($state, $message, $request_url, $request_method, $request_type, $request_options, $response);
      return;
    }

    // Display submission exception if response code is not 2xx.
    if ($this->responseHasError($response)) {
      $message = $this->t('Remote post request return @status_code status code.', ['@status_code' => $response->getStatusCode()]);
      $this->handleError($state, $message, $request_url, $request_method, $request_type, $request_options, $response);
      return;
    }
    else {
      $this->displayCustomResponseMessage($response, FALSE);
    }

    // If debugging is enabled, display the request and response.
    $this->debug($this->t('Remote post successful!'), $state, $request_url, $request_method, $request_type, $request_options, $response, 'warning');

    // Replace [webform:handler] tokens in submission data.
    // Data structured for [webform:handler:remote_post:completed:key] tokens.
    $submission_data = $webform_submission->getData();
    $submission_has_token = (strpos(print_r($submission_data, TRUE), '[webform:handler:' . $this->getHandlerId() . ':') !== FALSE) ? TRUE : FALSE;
    if ($submission_has_token) {
      $response_data = $this->getResponseData($response);
      $token_data = ['webform_handler' => [$this->getHandlerId() => [$state => $response_data]]];
      $submission_data = $this->replaceTokens($submission_data, $webform_submission, $token_data);
      $webform_submission->setData($submission_data);
      // Resave changes to the submission data without invoking any hooks
      // or handlers.
      if ($this->isResultsEnabled()) {
        $webform_submission->resave();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getResponseData(ResponseInterface $response) {
    // Prepare data to feed confirmation_message field.
    $custom_response_message = $this->getCustomResponseMessage($response);
    if (!$custom_response_message) {
      return parent::getResponseData($response);
    }
    // Decodes response body.
    $body = (string) $response->getBody();
    $data = json_decode($body, TRUE);
    $data = (json_last_error() === JSON_ERROR_NONE) ? $data : $body;
    // Builds up the token data.
    $token_data = [
      'webform_handler' => [
        $this->getHandlerId() => $data,
      ],
    ];
    // Decodes the custom response message.
    $data = $this->replaceTokens($custom_response_message, $this->getWebform(), $token_data);
    // Sets error_message.
    $this->webformSubmission->setElementData('confirmation_message', $response->getStatusCode() . '|' . $data);
    $this->webformSubmission->resave();
    return parent::getResponseData($response);
  }

}
