<?php

/**
 * @file
 * CKEditor configuration file tide_spell_checker.
 */

// Create ckeditor custom config array.
$ckeditor_config = '';
$array_of_config = [];
$env_vars = getenv();
if ($env_vars) {
  foreach ($env_vars as $var => $value) {
    if (str_contains($var, 'CKEDITOR_')) {
      $var = strtolower(trim(str_replace('CKEDITOR_', '', $var)));
      $var = ($var == 'scayt_slang') ? 'scayt_sLang' : $var;
      $array_of_config[$var] = $value;
    }
  }
}
if (isset($array_of_config)) {
  foreach ($array_of_config as $key => $value) {
    $ckeditor_config = $ckeditor_config . "\r\n$key = \"$value\"";
  }
}
// Add ckeditor custom config.
if ($ckeditor_config) {
  $config['editor.editor.rich_text']['settings']['plugins']['customconfig']['ckeditor_custom_config'] = $ckeditor_config;
}
