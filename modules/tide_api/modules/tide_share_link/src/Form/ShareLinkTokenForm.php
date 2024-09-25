<?php

namespace Drupal\tide_share_link\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for Share Link Token edit forms.
 *
 * @ingroup tide_share_link
 */
class ShareLinkTokenForm extends ContentEntityForm {

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    $instance = parent::create($container);
    $instance->account = $container->get('current_user');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\tide_share_link\Entity\ShareLinkTokenInterface $entity */
    $form = parent::buildForm($form, $form_state);
    // Set the field description to the timestamp value widget.
    // @see https://www.drupal.org/project/drupal/issues/2508866
    if (!empty($form['expiry']['widget']['#description'])) {
      $first_key = array_key_first(Element::children($form['expiry']['widget']));
      if (!is_null($first_key)) {
        $form['expiry']['widget'][$first_key]['value']['#description'] = $form['expiry']['widget']['#description'];
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Share Link Token.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Share Link Token.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.share_link_token.canonical', [
      'node' => $entity->getSharedNode()->id(),
      'share_link_token' => $entity->id(),
    ]);
  }

}
