<?php

/**
 * @file
 * Drush commands for Tide Core.
 */

use Drupal\redirect\Entity\Redirect;

/**
 * Implements hook_drush_command().
 */
function tide_core_drush_command() {
  $items = [];

  $items['redirect-import'] = [
    'description' => 'Imports a file and adds redirects to the redirect table',
    'arguments' => [
      'filepath' => 'Path to CSV (comma-separated), JSON or PHP file containing old and new URLs for redirects',

    ],
    'options' => [
      'home-url' => 'URL of the current domain being hosted by Drupal. (e.g vic.gov.au)',
      'lang' => 'Language code of the page',
      'status-code' => 'Status code to be returned by redirect module.',
    ],
  ];

  return $items;

};

/**
 * Get the node id of the page to redirect to.
 *
 * @param string $to
 *   URI of redirected page to get the internal path of.
 *
 * @return string
 *   When an internal node id is found or if the URL is to an external site.
 */
function get_internal_path($to) {
  if ((substr($to, 0, 4) == 'http')) {
    return $to;
  }
  $to = (substr($to, 0, 1) == '/') ? $to : '/' . $to;
  $path = \Drupal::service('path.alias_manager')->getPathByAlias($to);
  if (preg_match('/node\/(\d+)/', $path, $matches)) {
    return 'node/' . $matches[1];
  }
  return FALSE;
};

/**
 * Uses redirect entity to save entry to DB.
 *
 * @param string $from
 *   URI of page to redirect FROM.
 * @param string $to
 *   URI of page to redirect TO.
 * @param string $home_url
 *   URL of the current domain being hosted by Drupal. (e.g vic.gov.au)
 * @param string $lang
 *   Language code of page.
 * @param int $status_code
 *   Status code to return for redirect. Overridden if $to specifies code.
 *
 * @return bool
 *   returns FALSE if input was invalid or a node id could not be found.
 *
 * @throws \Exception
 *   If saving to the DB fails.
 */
function add_redirects($from, $to, $home_url, $lang, $status_code) {
  if (is_array($to)) {
    $status_code = $to[1];
    $to = $to[0];
  }

  $to = get_internal_path($to);

  if (!$to) {
    $to = '';
  }

  if ($from != '' && $to != '') {

    $redirectrepo = redirect_repository();
    if ($redirectrepo->findBySourcePath($from)) {
      return FALSE;
    }
    $vicgov = $to == 'https://' . $home_url || $to == 'http://' . $home_url || $to == $home_url;
    $wwwvicgov = $to == 'https://www.' . $home_url || $to == 'http://' . $home_url || $to == 'www.' . $home_url;
    if ($vicgov || $wwwvicgov) {
      $to = 'site-4/';
    }
    $redirect = Redirect::create();
    $redirect->setLanguage($lang);
    $redirect->setStatusCode($status_code);
    $queryStr = substr($from, 0, strpos($from, '?'));
    if (!empty($queryStr)) {
      $query = explode('&', $queryStr);
    }
    else {
      $query = [];
    }

    $redirect->setSource($from, $query);
    $redirect->setRedirect($to);
    $redirect->getRedirect();
    $redirect->save();

    return TRUE;
  }

  return FALSE;
};

/**
 * Puts wildcard entries before their specific entries in an array.
 *
 * @param array $redir_dict
 *   Associative array to sort.
 *
 * @return array
 *   Returns the sorted array.
 */
function sort_wildcard(array $redir_dict) {
  $redir_dict_sorted = $redir_dict;
  foreach ($redir_dict as $wildcard_key_old => $wildcard_key_new) {
    if (preg_match('/\%$/', $wildcard_key_old)) {
      foreach ($redir_dict as $no_wildcard_key_old => $no_wildcard_key_new) {
        if (explode('%', $wildcard_key_old)[0] == explode('.', $no_wildcard_key_old)[0]) {
          if (array_search($no_wildcard_key_old, array_keys($redir_dict)) < array_search($wildcard_key_old, array_keys($redir_dict))) {
            unset($redir_dict_sorted[$no_wildcard_key_old]);
            unset($redir_dict_sorted[$wildcard_key_old]);

            $redir_dict_sorted[$wildcard_key_old] = $wildcard_key_new;
            $redir_dict_sorted[$no_wildcard_key_old] = $no_wildcard_key_new;

          }
        }
      }
    }
  }
  return $redir_dict_sorted;
};

/**
 * Verify and parse CSV file.
 *
 * @param string $filepath
 *   Path to CSV file.
 *
 * @return array
 *   Returns the associative array with CSV data.
 */
function csv_parse($filepath) {
  $result = [];
  $file_handle = fopen($filepath, 'r');

  if (!$file_handle) {
    return FALSE;
  }

  while (($line = fgets($file_handle)) !== FALSE) {
    $line = explode(',', $line);

    if (!$line || (count($line) < 2)) {
      return FALSE;
    }

    // Strip out the EOL character at the end of a CSV entry.
    if (count($line) == 3) {
      $line[2] = explode(PHP_EOL, $line[2])[0];
      $line[1] = [
        $line[1],
        $line[2],
      ];
    }
    else {
      $line[1] = explode(PHP_EOL, $line[1])[0];
    }

    $result[$line[0]] = $line[1];
  }

  fclose($file_handle);

  return $result;
};

/**
 * Verify that the provided JSON file is valid and return the result.
 *
 * @param string $filepath
 *   Path to JSON file.
 *
 * @return array
 *   Returns the associative array with JSON data.
 */
function json_parse($filepath) {
  $result = file_get_contents($filepath);

  $result = json_decode($result, TRUE);

  if ($result == NULL) {
    return FALSE;
  }
  else {
    return $result;
  }

};

/**
 * Verify and parse PHP.
 *
 * @param string $filepath
 *   Path to PHP file.
 *
 * @return array
 *   Returns the associative array with CSV data.
 */
function php_parse($filepath) {
  $redirects = [];

  include $filepath;

  if (count($redirects) == 0) {
    return FALSE;
  }

  // Check that the imported array is associative.
  if (!([] == $redirects) && !(array_keys($redirects) !== range(0, count($redirects) - 1))) {
    return FALSE;
  }

  return $redirects;
};

/**
 * Callback function for redirect-import.
 *
 * @param string $filepath
 *   Path to input.
 *
 * @return bool
 *   Returns true if importing redirect data was successful.
 *
 * @throws \Exception
 *   Returns drush error if file reading fails.
 */
function drush_tide_core_redirect_import($filepath = '') {
  $redir_dict = [];
  $home_url = drush_get_option('home-url', 'vic.gov.au');
  $lang = drush_get_option('lang', 'en');
  $status_code = drush_get_option('status-code', 301);

  if ($filepath == '') {
    return drush_set_error('ERROR', dt('No input file specified...'));
  }

  if (!file_exists($filepath)) {
    return drush_set_error('ERROR', dt('The file specified does not exist...'));
  }

  if (pathinfo($filepath, PATHINFO_EXTENSION) == 'json') {
    $redir_dict = json_parse($filepath);

    if (!$redir_dict) {
      return drush_set_error('DECODE ERROR', dt('Failed to process the specified JSON file...'));
    }

  }

  if (pathinfo($filepath, PATHINFO_EXTENSION) == 'csv') {
    $redir_dict = csv_parse($filepath);

    if (!$redir_dict) {
      return drush_set_error('DECODE ERROR', dt('Failed to process the specified CSV file...'));
    }
  }

  if (pathinfo($filepath, PATHINFO_EXTENSION) == 'php') {
    $redir_dict = php_parse($filepath);

    if (!$redir_dict) {
      return drush_set_error('DECODE ERROR', dt('Failed to process the specified PHP file...'));
    }
  }

  $redir_dict = sort_wildcard($redir_dict);

  $total_processed = 0;
  $total_added = 0;
  $total_ignored = 0;

  drush_print(print_r($redir_dict, TRUE));

  foreach ($redir_dict as $old_url => $new_url) {

    if (add_redirects($old_url, $new_url, $home_url, $lang, $status_code)) {
      $total_added++;
    }
    else {
      $total_ignored++;
    }

    $total_processed++;
  }

  drush_log('Processed ' . $total_processed . ' redirect entries.', 'success', NULL);
  drush_log('Added: ' . $total_added . ' Ignored: ' . $total_ignored, 'success', NULL);

  return TRUE;

};
