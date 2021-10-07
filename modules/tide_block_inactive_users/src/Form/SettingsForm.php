<?php

namespace Drupal\tide_block_inactive_users\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class SettingsForm.
 *
 * @package Drupal\tide_block_inactive_users\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * A logger instance.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerChannelFactory;

  /**
   * An entity type manager instance.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Create function for dependency injection.
   */
  public static function create(ContainerInterface $container) {

    $loggerFactory = $container->get('logger.factory');

    return new static($loggerFactory,
      $container->get('entity_type.manager'));
  }

  /**
   * Constructor.
   */
  public function __construct(LoggerChannelFactoryInterface $loggerChannelFactory, EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->loggerChannelFactory = $loggerChannelFactory;

  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return "tide_block_inactive_users_settings";
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['tide_block_inactive_users.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('tide_block_inactive_users.settings');
    $roles = user_role_names();
    unset($roles['anonymous']);
    unset($roles['administrator']);

    $form["users_settings"] = [
      "#type" => "details",
      "#title" => $this->t("Block Users Management settings"),
      '#open' => TRUE,
    ];

    $form['users_settings']['idle_time'] = [
      '#title' => $this->t('Time period'),
      '#required' => TRUE,
      '#type' => 'number',
      '#attributes' => [
        'min' => 1,
      ],
      '#default_value' => $config->get('idle_time'),
      '#description' => $this->t('How long do you want to wait to block inactive users, after sent notifications.'),
    ];

    $form['users_settings']['time_unit'] = [
      '#type' => 'select',
      '#multiple' => FALSE,
      '#title' => $this->t('Time unit'),
      '#default_value' => $config->get('time_unit'),
      '#options' => [
        'day' => 'Day(s)',
        'week' => 'Week(s)',
        'month' => 'Month(s)',
        'hour' => 'Hour(s)',
      ],
      '#required' => TRUE,
    ];

    $form['users_settings']['cron'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Drupal Cron?'),
      '#default_value' => $config->get('cron'),
      '#description' => $this->t('If checked, it will run with Drupal cron, while if unchecked you have setup your own cron job.'),
    ];

    $form['actions']['block_inactive_users_update'] = [
      '#type' => 'submit',
      '#value' => $this->t('Disable inactive users'),
    ];

    return parent::buildForm($form, $form_state);

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable('tide_block_inactive_users.settings')
      ->set('cron', $form_state->getValue('cron'))
      ->set('time_unit', $form_state->getValue('time_unit'))
      ->set('idle_time', $form_state->getValue('idle_time'))
      ->save();
    parent::submitForm($form, $form_state);

  }

}
