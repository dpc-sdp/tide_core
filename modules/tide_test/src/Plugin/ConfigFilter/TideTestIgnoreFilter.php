<?php

namespace Drupal\tide_test\Plugin\ConfigFilter;

use Drupal\config_ignore\Plugin\ConfigFilter\IgnoreFilter;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Ignore all test config.
 *
 * @ConfigFilter(
 *   id = "tide_test_config_ignore",
 *   label = "Tide Test Config Ignore",
 *   weight = 100
 * )
 */
class TideTestIgnoreFilter extends IgnoreFilter implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, StorageInterface $active) {
    $configuration['ignored'] = [
      '*.test',
      '*.test*',
      '*.test.*',
      '*_test*',
      '*.field_test*',
      '*.node--test',
    ];
    parent::__construct($configuration, $plugin_id, $plugin_definition, $active);
  }

  /**
   * {@inheritdoc}
   */
  public function filterWrite($name, array $data) {
    // @codingStandardsIgnoreStart Drupal.Functions.DiscouragedFunctions.Discouraged
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
    elseif (fnmatch('user.role.*', $name)) {
      if (isset($data['permissions'])) {
        $data['permissions'] = array_values(array_diff($data['permissions'], $excluded_permissions));
      }
    }
    elseif (fnmatch('workflows.workflow.*', $name)) {
      if (isset($data['type_settings']['entity_types']['node'])) {
        $data['type_settings']['entity_types']['node'] = array_values(array_diff($data['type_settings']['entity_types']['node'], $excluded_node_types));
      }
    }
    elseif (fnmatch('field.field.*', $name)) {
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
    // @codingStandardsIgnoreEnd Drupal.Functions.DiscouragedFunctions.Discouraged
  }

}
