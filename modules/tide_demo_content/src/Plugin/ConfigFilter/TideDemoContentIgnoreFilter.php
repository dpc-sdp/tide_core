<?php

namespace Drupal\tide_demo_content\Plugin\ConfigFilter;

use Drupal\config_filter\Plugin\ConfigFilterBase;
use Symfony\Component\Finder\Glob;

/**
 * Ignore all demo config.
 *
 * @ConfigFilter(
 *   id = "tide_demo_content_config_ignore",
 *   label = "Tide Demo Content Config Ignore",
 *   weight = 100
 * )
 */
class TideDemoContentIgnoreFilter extends ConfigFilterBase {

  /**
   * Configuration-name patterns excluded from exported configuration.
   *
   * @var string[]
   */
  protected $ignored = [
    '*tide_demo_content*',
    '*tide-demo-content*',
    '*tide_demo*',
    '*tide-demo*',
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
  public function filterWrite($name, array $data) : ?array {
    if ($name === 'core.extension') {
      $excluded_modules = ['tide_demo_content' => 'tide_demo_content'];
      $data['module'] = array_diff_key($data['module'], $excluded_modules);
    }
    elseif ($this->matchConfigName($name)) {
      return NULL;
    }
    return $data;
  }

}
