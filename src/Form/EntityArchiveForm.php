<?php

namespace Drupal\tide_core\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Drupal\workflows\Entity\Workflow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a confirmation form for archival of an entity.
 */
class EntityArchiveForm extends ConfirmFormBase {
  /**
   * The Workflow entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs an EntityArchiveForm object.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(MessengerInterface $messenger) {
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger')
    );
  }

  /**
   * Checks access for a specific request.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param string $bundle
   *   The configuration of the plugin.
   * @param string $entity_type_id
   *   The plugin id.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account, string $bundle = NULL, string $entity_type_id = NULL) {
    // If entity does not exist then return right away.
    if (!$this->entity = \Drupal::entityTypeManager()->getStorage($bundle)->load($entity_type_id)) {
      return AccessResult::forbidden();
    }
    // Load Editorial workflow and check if it applies to given entity.
    $workflow = Workflow::load('editorial');
    if ($workflow) {
      if ($workflow->getTypePlugin()
        ->appliesToEntityTypeAndBundle($this->entity->getEntityTypeId(), $this->entity->bundle())) {
        if ($this->entity->access('use ' . $workflow->id() . ' transition archived', $account)) {
          return AccessResult::allowed();
        }
      }
    }

    return AccessResult::forbidden();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, string $bundle = NULL, string $entity_type_id = NULL) {
    $this->entity = \Drupal::entityTypeManager()->getStorage($bundle)->load($entity_type_id);
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tide_core_entity_archive';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to archive entity %name?', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This action will unpublish the content.');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromUserInput(\Drupal::destination()->get());
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Archive');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->set('moderation_state', 'archived');
    $this->entity->save();

    $this->messenger->addStatus($this->t('Content @type: Archived %label.', [
      '@type' => $this->entity->bundle(),
      '%label' => $this->entity->label(),
    ]));

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
