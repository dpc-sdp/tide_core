<?php


function tide_core_drush_command() {
    $items = array();

    // Define redirect-import
    $items['redirect-import'] = array(
        'description' => "Imports a file and adds redirects to the redirect table",
        'arguments' => array(
            'filepath' => 'Path to CSV (comma-separated), JSON or PHP file containing old and new URLs for redirects',
        )
    );

    return $items;

};


function get_node_id($to) {
    if ((substr($to, 0, 4) == 'http')) {
        return $to;
    }
    $to = (substr($to, 0, 1) == '/') ? $to : '/' . $to;
    $path = \Drupal::service('path.alias_manager')->getPathByAlias($to);
    if(preg_match('/node\/(\d+)/', $path, $matches)) {
        return 'node/' . $matches[1];
    }
    return $to;
}

function add_redirects($from, $to) {
    if ($from != '' && $to != '') {
        $redirectrepo = redirect_repository();
        if ($redirectrepo->findBySourcePath($from)) {
            return;
        }
        $vicgov = $to == 'https://vic.gov.au' || $to == 'http://vic.gov.au' || $to == 'vic.gov.au';
        $wwwvicgov = $to == 'https://www.vic.gov.au' || $to == 'http://www.vic.gov.au' || $to == 'www.vic.gov.au';
        if ($vicgov || $wwwvicgov) {
            $to = 'site-4/';
        }
        $redirect = \Drupal\redirect\Entity\Redirect::create();
        $redirect->setLanguage('en');
        $redirect->setStatusCode(301);
        $queryStr = substr($from, 0, strpos($from, '?'));
        if (!empty($queryStr)) {
            $query = explode('&', $queryStr);
        }
        else {
            $query = [];
        }
        $redirect->setSource($from, $query);
        $redirect->setRedirect(get_node_id($to));
        $redirect->getRedirect();
        $redirect->save();
    }
}

function csv_parse($filepath) {

    $result = array();
    $file_handle = fopen($filepath, 'r');

    if (!$file_handle) {
        return false;
    }

    // Read in each line in the CSV file and convert to associative array
    while (($line = fgets($file_handle)) !== false) {
        $line = explode(',', $line);

        if (!$line || (count($line) < 2)) {
            return false;
        }

        $result[$line[0]] = $line[1];
    }

    fclose($file_handle);

    return $result;
};

function json_parse($filepath) {

    $result = file_get_contents($filepath);

    $result = json_decode($result);

    if ($result == null) {
        return false;
    }
    else {
        return $result;
    }

};

function php_parse($filepath) {

    include $filepath;

    if (!isset($redirects)) {
        return false;
    }

    $result = $redirects;

    //Check that the imported array is associative
    if (!(array() == $result) && !(array_keys($result) !== range(0, count($result) - 1))) {
        return false;
    }

    return $result;
};

function drush_tide_core_redirect_import($filepath = '') {

    if ($filepath == '') {
        return drush_set_error('ERROR', dt('No input file specified...'));
    }

    if (!file_exists($filepath)) {
        return drush_set_error ('ERROR', dt('The file specified does not exist...'));
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

    $total_processed = 0;

    foreach ($redir_dict as $old_url => $new_url) {
        add_redirects($old_url, $new_url);
        $total_processed++;
    }

    drush_log('Processed '. $total_processed . ' redirect entries.', 'success', null);

    return true;

};

