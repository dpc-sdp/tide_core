<?php

namespace Drupal\tide_media\Form;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tide Media Settings Form.
 *
 * @package Drupal\tide_media\Form
 */
class TideMediaSettingsForm extends ConfigFormBase {

  /**
   * Discovery cache bin.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheDiscovery;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, CacheBackendInterface $discovery_cache, LanguageManagerInterface $language_manager) {
    parent::__construct($config_factory);
    $this->cacheDiscovery = $discovery_cache;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('cache.discovery'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tide_media_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'tide_media.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('tide_media.settings');

    $form['file_absolute_url'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Return absolute URL for files.'),
      '#default_value' => $config->get('file_absolute_url'),
    ];

    $form['force_https'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Always use HTTPS for absolute URL.'),
      '#default_value' => $config->get('force_https'),
      '#states' => [
        'enabled' => [
          ':input[name=file_absolute_url]' => ['checked' => TRUE],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration.
    $this->configFactory->getEditable('tide_media.settings')
      ->set('file_absolute_url', $form_state->getValue('file_absolute_url'))
      ->set('force_https', $form_state->getValue('force_https'))
      ->save();

    parent::submitForm($form, $form_state);
    $cid = 'entity_base_field_definitions:file:' . $this->languageManager->getCurrentLanguage()->getId();
    $this->cacheDiscovery->delete($cid);
  }

}
