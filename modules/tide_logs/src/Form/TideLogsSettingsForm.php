<?php

namespace Drupal\tide_logs\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\tide_logs\Logger\TideLogsLogger;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Settings form for tide_logs.
 */
class TideLogsSettingsForm extends ConfigFormBase {

  /**
   * The Tide logger service.
   *
   * @var TideLogsLogger
   */
  protected TideLogsLogger $tideLogsLogger;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, TideLogsLogger $tide_logs_logger) {
    parent::__construct($config_factory);
    $this->tideLogsLogger = $tide_logs_logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('logger.tide_logs')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['tide_logs.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tide_logs_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('tide_logs.settings');

    $form['enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable module'),
      '#description' => $this->t('Send logs to SumoLogic.'),
      '#default_value' => $config->get('enable'),
    ];

    $form['description'] = [
      '#prefix' => '<div class="ll-settings-description">',
      '#suffix' => '</div>',
      '#markup' => $this->t(
        '<p>Current settings for the Tide Logs module. The defaults are set in configuration, this page is meant primarily for troubleshooting.</p>' .
        '<ul>' .
          '<li><b>' . $this->t('SumoLogic category') . ':</b> ' . $this->tideLogsLogger->getSumoLogicCategory() . '</li>' .
        '</ul>'
      ),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('tide_logs.settings')
      ->set('enable', $form_state->getValue('enable'))
      ->save();
  }

}
