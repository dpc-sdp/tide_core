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
 * Provides nodes deletion confirmation form.
 */
class NodeActionForm extends ConfirmFormBase {

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
   * The action name.
   *
   * @var string
   */
  protected $action;

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
    $this->tempStore = $temp_store_factory->get('tide_node_action_multiple_confirm');
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
    return $this->formatPlural(count($this->selection), 'Are you sure you want to ' . $this->action . ' this @item?', 'Are you sure you want to ' . $this->action . ' these @items?', [
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
    return 'tide_node_action_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $ops = [];
    $batch_size = 10;
    foreach (array_chunk(array_keys($this->selection), $batch_size) as $ids) {
      $ops[] = [get_class($this) . '::doAction', [$ids, $this->action]];
    }
    $batch = [
      'title' => t('Processing selected content'),
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
    $this->action = array_keys($this->tempStore->get($this->currentUser->id() . ':node'))[0];
    $this->selection = $this->tempStore->get($this->currentUser->id() . ':node')[$this->action];
    $items = [];
    $entities = $this->entityTypeManager->getStorage('node')
      ->loadMultiple(array_keys($this->selection));
    foreach ($entities as $entity) {
      $items[$entity->id()] = [
        'label' => [
          '#markup' => $this->t('@label', [
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
    return AccessResult::allowedIfHasPermission($this->currentUser, 'bypass node access')->orIf(
      AccessResult::allowedIfHasPermission($this->currentUser, 'administer nodes')
    );
  }

  /**
   * Do Publish.
   */
  public static function doAction($entity_ids, $action) {
    $controller = \Drupal::entityTypeManager()->getStorage('node');
    $entities = $controller->loadMultiple($entity_ids);
    $to_state = '';
    switch ($action) {
      case 'publish':
        $to_state = 'published';
        break;

      case 'archive':
        $to_state = 'archived';
        break;
    }

    foreach ($entities as $entity) {
      $entity->set('moderation_state', $to_state);
      $entity->setPublished($action == 'publish');
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
        \Drupal::messenger()->addStatus(t('Selected content has been processed'));
      }
    }
    else {
      // An error occurred.
      // $operations contains the operations that remained unprocessed.
      $message = t('An error occurred processing content');
      \Drupal::messenger()->addError($message, 'error');
    }
  }

  /**
   * Returns form title.
   */
  public function getTitle() {
    return 'Confirm ' . $this->action;
  }

}
