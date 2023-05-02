<?php

namespace Drupal\tide_core\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\user\Entity\User;
use Drupal\user\Entity\Role;

/**
 * A handler to provide a field that is completely custom by the administrator.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("sorted_roles_views_field")
 */
class SortedRolesViewsField extends FieldPluginBase {
  /**
   * The current display.
   *
   * @var string
   *   The current display of the view.
   */
  protected $currentDisplay;

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->currentDisplay = $view->current_display;
  }

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Do nothing -- to override the parent query.
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    // First check whether the field should be hidden if the value(hide_alter_empty = TRUE) /the rewrite is empty (hide_alter_empty = FALSE).
    $options['hide_alter_empty'] = ['default' => FALSE];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $user = $values->_entity;
    $rolesSorted = [];
    if ($user instanceof User) {
      $roles = $user->getRoles();
      asort($roles);
      foreach ($roles as $roleId) {
        $roleEntity = Role::load($roleId);
        $rolesSorted[] = $roleEntity->label();
      }
    }

    return [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#items' => $rolesSorted,
    ];
  }
}
