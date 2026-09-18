<?php

namespace Drupal\tide_media\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\media_library\Form\AddFormBase;
use Drupal\media_library\MediaLibraryUiBuilder;
use Drupal\media_library\OpenerResolverInterface;
use Drupal\video_embed_field\ProviderManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Creates video embed media items from URLs in the media library.
 */
class VideoEmbedFieldForm extends AddFormBase {

  /**
   * Constructs a VideoEmbedFieldForm object.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    MediaLibraryUiBuilder $library_ui_builder,
    OpenerResolverInterface $opener_resolver,
    protected ProviderManagerInterface $providerManager,
  ) {
    parent::__construct($entity_type_manager, $library_ui_builder, $opener_resolver);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('media_library.ui_builder'),
      $container->get('media_library.opener_resolver'),
      $container->get('video_embed_field.provider_manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return $this->getBaseFormId() . '_video_embed';
  }

  /**
   * {@inheritdoc}
   */
  protected function getMediaType(FormStateInterface $form_state) {
    $media_type = parent::getMediaType($form_state);
    if ($media_type->getSource()->getPluginId() !== 'video_embed_field') {
      throw new \InvalidArgumentException('The video embed add form can only create video embed media.');
    }
    return $media_type;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildInputElement(array $form, FormStateInterface $form_state) {
    $media_type = $this->getMediaType($form_state);
    $data_definition = $media_type->getSource()
      ->getSourceFieldDefinition($media_type)
      ->getItemDefinition();

    $form['container'] = [
      '#type' => 'container',
    ];
    $form['container']['video_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Add @type via URL', [
        '@type' => $media_type->label(),
      ]),
      '#maxlength' => $data_definition->getSetting('max_length'),
      '#allowed_providers' => $data_definition->getSetting('allowed_providers'),
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => 'https://',
      ],
    ];
    $form['container']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add'),
      '#button_type' => 'primary',
      '#validate' => ['::validateUrl'],
      '#submit' => ['::addButtonSubmit'],
      '#ajax' => [
        'callback' => '::updateFormCallback',
        'wrapper' => 'media-library-wrapper',
        'url' => Url::fromRoute('media_library.ui'),
        'options' => [
          'query' => $this->getMediaLibraryState($form_state)->all() + [
            FormBuilderInterface::AJAX_FORM_REQUEST => TRUE,
          ],
        ],
      ],
    ];
    return $form;
  }

  /**
   * Validates the submitted video URL.
   */
  public function validateUrl(array &$form, FormStateInterface $form_state): void {
    if (!$this->providerManager->loadProviderFromInput($form_state->getValue('video_url'))) {
      $form_state->setErrorByName('video_url', $this->t('The URL is not supported by an available video provider.'));
    }
  }

  /**
   * Creates an unsaved media item from the submitted URL.
   */
  public function addButtonSubmit(array $form, FormStateInterface $form_state): void {
    $this->processInputValues([$form_state->getValue('video_url')], $form, $form_state);
  }

}
