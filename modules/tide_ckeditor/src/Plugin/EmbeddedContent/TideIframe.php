<?php

namespace Drupal\tide_ckeditor\Plugin\EmbeddedContent;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\embedded_content\EmbeddedContentInterface;
use Drupal\embedded_content\EmbeddedContentPluginBase;

/**
 * Plugin iframes.
 *
 * @EmbeddedContent(
 *   id = "tide_iframe",
 *   label = @Translation("Iframe"),
 *   description = @Translation("Renders an Iframe."),
 * )
 */
class TideIframe extends EmbeddedContentPluginBase implements EmbeddedContentInterface {

  /**
   * {@inheritdoc}
   */
  public function isInline(): bool {
    return FALSE;
  }

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'title' => NULL,
      'url' => NULL,
      'width' => NULL,
      'height' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    return [
      '#theme' => 'tide_iframe',
      '#title' => $this->configuration['title'],
      '#url' => $this->configuration['url'],
      '#width' => $this->configuration['width'],
      '#height' => $this->configuration['height'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $this->configuration['title'],
      '#required' => TRUE,
    ];
    $form['url'] = [
      '#type' => 'url',
      '#title' => $this->t('Url'),
      '#default_value' => $this->configuration['url'],
      '#maxlength' => 1024,
      '#required' => TRUE,
    ];
    $form['width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Width'),
      '#default_value' => $this->configuration['width'],
    ];
    $form['height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Height'),
      '#default_value' => $this->configuration['height'],
    ];
    return $form;
  }

}
