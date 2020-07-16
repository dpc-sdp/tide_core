<?php

namespace Drupal\tide_core\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an nodes deletion confirmation form.
 */
class NodePublishForm extends ConfirmFormBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The tempstore.
   *
   * @var \Drupal\Core\TempStore\SharedTempStore
   */
  protected $tempStore;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The selection, in the entity_id => langcodes format.
   *
   * @var array
   */
  protected $selection = [];

  /**
   * The entity type definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  /**
   * Constructs a new DeleteMultiple object.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager, PrivateTempStoreFactory $temp_store_factory, MessengerInterface $messenger) {
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->tempStore = $temp_store_factory->get('tide_node_publish_multiple_confirm');
    $this->messenger = $messenger;
    $this->entityType = $this->entityTypeManager->getDefinition('node');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('tempstore.private'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->formatPlural(count($this->selection), 'Are you sure you want to publish this @item?', 'Are you sure you want to publish these @items?', [
      '@item' => $this->entityType->getSingularLabel(),
      '@items' => $this->entityType->getPluralLabel(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    $route = Url::fromUserInput('/admin/content')->getRouteName();
    return new Url($route);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tide_node_publish_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $ops = [];
    $batch_delete_size = 10;
    foreach (array_chunk(array_keys($this->selection), $batch_delete_size) as $ids) {
      $ops[] = [get_class($this) . '::doPublish', [$ids]];
    }
    $batch = [
      'title' => t('Publishing all contents'),
      'operations' => $ops,
      'finished' => [get_class($this), 'finishBatch'],
    ];
    batch_set($batch);
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->selection = $this->tempStore->get($this->currentUser->id() . ':node');
    $items = [];
    $entities = $this->entityTypeManager->getStorage('node')
      ->loadMultiple(array_keys($this->selection));
    foreach ($entities as $entity) {
      $items[$entity->id()] = [
        'label' => [
          '#markup' => $this->t('@label <em>will be published</em>', [
            '@label' => $entity->label(),
            '@entity_type' => $this->entityType->getSingularLabel(),
          ]),
        ],
      ];
    }
    $form['entities'] = [
      '#theme' => 'item_list',
      '#items' => $items,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * Access check.
   */
  public function access(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($this->currentUser, 'bypass node access')
      ->orIf(AccessResult::allowedIfHasPermission($this->currentUser, 'administer nodes'));
  }

  /**
   * Do Publish.
   */
  public static function doPublish($entity_ids) {
    $controller = \Drupal::entityTypeManager()->getStorage('node');
    $entities = $controller->loadMultiple($entity_ids);
    foreach ($entities as $entity) {
      $entity->set('moderation_state', 'published');
      $entity->setPublished(TRUE);
      $entity->save();
    }
  }

  /**
   * Finish batch.
   */
  public static function finishBatch($success, $results, $operations) {
    if ($success) {
      if (!empty($results['errors'])) {
        foreach ($results['errors'] as $error) {
          \Drupal::messenger()->addError($error, 'error');
        }
      }
      else {
        \Drupal::messenger()->addStatus(t('All contents published'));
      }
    }
    else {
      // An error occurred.
      // $operations contains the operations that remained unprocessed.
      $message = t('An error occurred publishing contents');
      \Drupal::messenger()->addError($message, 'error');
    }
  }

}
