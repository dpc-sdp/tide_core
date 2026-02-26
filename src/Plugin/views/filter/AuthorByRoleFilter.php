<?php

namespace Drupal\tide_core\Plugin\views\filter;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Drupal\user\RoleInterface;
use Drupal\views\Plugin\views\filter\InOperator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filters content by author, limited to users with admin-configured roles.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("author_by_role_filter")
 */
class AuthorByRoleFilter extends InOperator {

  /**
   * {@inheritdoc}
   */
  protected $valueFormType = 'select';

  /**
   * The user storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $userStorage;

  /**
   * The role storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $roleStorage;

  /**
   * Constructs an AuthorByRoleFilter object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $user_storage
   *   The user entity storage.
   * @param \Drupal\Core\Entity\EntityStorageInterface $role_storage
   *   The role entity storage.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityStorageInterface $user_storage, EntityStorageInterface $role_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->userStorage = $user_storage;
    $this->roleStorage = $role_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('user'),
      $container->get('entity_type.manager')->getStorage('user_role')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['roles'] = ['default' => []];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $roles = $this->roleStorage->loadMultiple();
    unset($roles[RoleInterface::ANONYMOUS_ID]);
    unset($roles[RoleInterface::AUTHENTICATED_ID]);

    $role_options = [];
    foreach ($roles as $role) {
      $role_options[$role->id()] = $role->label();
    }

    $form['roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Roles'),
      '#description' => $this->t('Select the roles to include in the author dropdown. If none are selected, all active users will be shown.'),
      '#options' => $role_options,
      '#default_value' => $this->options['roles'] ?? [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    parent::submitOptionsForm($form, $form_state);
    $roles = $form_state->getValue(['options', 'roles']);
    $form_state->setValue(['options', 'roles'], array_filter($roles));
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    if (isset($this->valueOptions)) {
      return $this->valueOptions;
    }

    $this->valueOptions = [];
    // Anonymous user will display always.
    $this->valueOptions[User::getAnonymousUser()->id()] = User::getAnonymousUser()->getDisplayName();
    $roles = array_filter($this->options['roles'] ?? []);

    $query = $this->userStorage->getQuery()
      ->accessCheck(FALSE)
      ->sort('name');

    if (!empty($roles)) {
      $query->condition('roles', array_values($roles), 'IN');
    }

    $uids = $query->execute();

    if ($uids) {
      $users = $this->userStorage->loadMultiple($uids);
      foreach ($users as $user) {
        $this->valueOptions[$user->id()] = $user->getDisplayName();
      }
    }

    return $this->valueOptions;
  }

}
