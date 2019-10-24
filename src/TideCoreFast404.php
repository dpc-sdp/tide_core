<?php

namespace Drupal\tide_core;

use Drupal\Core\Site\Settings;
use Drupal\Component\Render\FormattableMarkup;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Drupal\fast404\Fast404;

/**
 * TideCoreFast404: A value object for manager Fast 404 logic.
 *
 * @package Drupal\tide_core
 */
class TideCoreFast404 extends Fast404 {

  /**
   * Override Fast404 response.
   *
   * @param bool $return
   *   Decide whether to return the response object or simply send it.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   If this returns anything, it will be a response object.
   */
  public function response($return = FALSE) {
    $message = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML+RDFa 1.0//EN" "http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><title>404 Not Found</title></head><body><h1>Not Found</h1>The requested URL was not found on this server, please login by clicking <a href="@path">this link</a>.</body></html>';
    $return_gone = Settings::get('fast404_return_gone', FALSE);
    $custom_404_path = Settings::get('fast404_HTML_error_page', FALSE);
    if ($return_gone) {
      header((Settings::get('fast404_HTTP_status_method', 'mod_php') == 'FastCGI' ? 'Status:' : 'HTTP/1.0') . ' 410 Gone');
    }
    else {
      header((Settings::get('fast404_HTTP_status_method', 'mod_php') == 'FastCGI' ? 'Status:' : 'HTTP/1.0') . ' 404 Not Found');
    }
    // If a file is set to provide us with Fast 404 joy, load it.
    if (($this->loadHtml || Settings::get('fast404_HTML_error_all_paths', FALSE) === TRUE) && file_exists($custom_404_path)) {
      $message = @file_get_contents($custom_404_path, FALSE);
    }
    $response = new Response(new FormattableMarkup($message, ['@path' => $this->request->getBaseUrl() . '/?destination=' . $this->request->getPathInfo()]), 404);
    if ($return) {
      return $response;
    }
    else {
      $response->send();
      throw new ServiceUnavailableHttpException(3, $this->t('The requested URL "@path" was not found on this server. Try again shortly.', ['@path' => $this->request->getPathInfo()]));
    }
  }

}
