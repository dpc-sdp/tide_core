<?php

namespace Drupal\tide_test\Plugin\ConfigFilter;

use Drupal\config_filter\Plugin\ConfigFilterBase;
use Symfony\Component\Finder\Glob;

/**
 * Ignore all test config.
 *
 * @ConfigFilter(
 *   id = "tide_test_config_ignore",
 *   label = "Tide Test Config Ignore",
 *   weight = 100
 * )
 */
class TideTestIgnoreFilter extends ConfigFilterBase {

  /**
   * Configuration-name patterns excluded from exported configuration.
   *
   * @var string[]
   */
  protected $ignored = [
    '*.test',
    '*.test*',
    '*.test.*',
    '*_test*',
    '*.field_test*',
    '*.node--test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function matchConfigName($name) {
    foreach ($this->ignored as $pattern) {
      if (preg_match(Glob::toRegex($pattern, FALSE, FALSE), $name)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function filterWrite($name, array $data) {
    $excluded_modules = ['tide_test' => 'tide_test'];
    $excluded_permissions = [
      'create test content',
      'delete any test content',
      'delete own test content',
      'delete test revisions',
      'edit any test content',
      'edit own test content',
      'revert test revisions',
      'view test revisions',
    ];
    $excluded_node_types = ['test' => 'test'];

    if ($name === 'core.extension') {
      $data['module'] = array_diff_key($data['module'], $excluded_modules);
    }
    elseif ($this->matchConfigName($name)) {
      return NULL;
    }
    elseif (preg_match(Glob::toRegex('user.role.*', FALSE, FALSE), $name)) {
      if (isset($data['permissions'])) {
        $data['permissions'] = array_values(array_diff($data['permissions'], $excluded_permissions));
      }
    }
    elseif (preg_match(Glob::toRegex('workflows.workflow.*', FALSE, FALSE), $name)) {
      if (isset($data['type_settings']['entity_types']['node'])) {
        $data['type_settings']['entity_types']['node'] = array_values(array_diff($data['type_settings']['entity_types']['node'], $excluded_node_types));
      }
    }
    elseif (preg_match(Glob::toRegex('field.field.*', FALSE, FALSE), $name)) {
      if (isset($data['field_type']) && $data['field_type'] === 'entity_reference') {
        if (isset($data['settings']['handler_settings']['target_bundles'])) {
          $data['settings']['handler_settings']['target_bundles'] = array_diff_key($data['settings']['handler_settings']['target_bundles'], $excluded_node_types);
        }
      }
    }

    if (isset($data['dependencies']['config'])) {
      foreach ($data['dependencies']['config'] as $key => $config_name) {
        if ($this->matchConfigName($config_name)) {
          unset($data['dependencies']['config'][$key]);
        }
      }
      $data['dependencies']['config'] = array_values($data['dependencies']['config']);
    }

    if (isset($data['dependencies']['module'])) {
      foreach ($data['dependencies']['module'] as $key => $module_name) {
        if (in_array($module_name, $excluded_modules)) {
          unset($data['dependencies']['module'][$key]);
        }
      }
      $data['dependencies']['module'] = array_values($data['dependencies']['module']);
    }

    return $data;
  }

}
