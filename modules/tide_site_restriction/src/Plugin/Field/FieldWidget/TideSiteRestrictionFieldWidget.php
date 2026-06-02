<?php

namespace Drupal\tide_site_restriction\Plugin\Field\FieldWidget;

use Drupal\content_moderation\ModerationInformation;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsButtonsWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\tide_site\TideSiteFields;
use Drupal\tide_site_restriction\Helper;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'tide_site_restriction_field_widget' widget.
 *
 * @FieldWidget(
 *   id = "tide_site_restriction_field_widget",
 *   label = @Translation("Tide site restriction"),
 *   description = @Translation("Site selector widget."),
 *   field_types = {
 *     "entity_reference"
 *   },
 *   multiple_values = TRUE
 * )
 */
class TideSiteRestrictionFieldWidget extends OptionsButtonsWidget implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Current User.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Tide Site Restriction helper.
   *
   * @var \Drupal\tide_site_restriction\Helper
   */
  protected $helper;

  /**
   * ModerationInformation helper class.
   *
   * @var \Drupal\content_moderation\ModerationInformation
   */
  protected $moderationInformation;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityTypeManagerInterface $entityTypeManager, AccountProxyInterface $currentUser, Helper $helper, ModerationInformation $moderation_information) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $currentUser;
    $this->helper = $helper;
    $this->moderationInformation = $moderation_information;
    $this->multiple = !empty($settings['multiple_values']);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('tide_site_restriction.helper'),
      $container->get('content_moderation.moderation_information')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $field_name = $this->fieldDefinition->getName();
    $entity_type = $this->fieldDefinition->getTargetEntityTypeId();

    // The JS coupling only makes sense for the node Primary Site / Site fields.
    $is_primary = TideSiteFields::isSiteField($field_name, TideSiteFields::FIELD_PRIMARY_SITE);
    $is_site = TideSiteFields::isSiteField($field_name, TideSiteFields::FIELD_SITE);
    if ($entity_type !== 'node' || (!$is_primary && !$is_site)) {
      return $element;
    }

    // Users who can bypass the site restriction (e.g. administrator,
    // site_admin) may freely select any sites: skip the JS coupling and the
    // single-tree validation entirely.
    if ($this->helper->canBypassRestriction($this->currentUser)) {
      return $element;
    }

    // Attach the behaviour and the data it needs. Both field instances push
    // into the same drupalSettings bucket; the shared payload is idempotent and
    // each instance only declares its own role.
    $element['#attached']['library'][] = 'tide_site_restriction/site_fields';
    $element['#attached']['drupalSettings']['tideSiteRestriction'] = [
      'fields' => [$is_primary ? 'primary' : 'site' => $field_name],
      'isNew' => $this->isNewEntityForm($form_state),
    ];

    // Backend safety net: enforce the single-tree invariant on the Site field.
    if ($is_site) {
      $element['#tide_primary_field'] = TideSiteFields::normaliseFieldName(TideSiteFields::FIELD_PRIMARY_SITE, $entity_type);
      $element['#tide_site_field'] = $field_name;
      $element['#element_validate'][] = [static::class, 'validateSiteTree'];
    }

    return $element;
  }

  /**
   * Determines whether the widget is rendered on a new entity form.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return bool
   *   TRUE if the entity being edited is new.
   */
  protected function isNewEntityForm(FormStateInterface $form_state) {
    $form_object = $form_state->getFormObject();
    return $form_object instanceof EntityFormInterface && $form_object->getEntity()->isNew();
  }

  /**
   * Element validate handler enforcing the Primary Site tree invariant.
   *
   * The Site field must contain the selected Primary Site and may only contain
   * terms that belong to that Primary Site's tree (the Primary Site or one of
   * its descendants). This mirrors the JS behaviour for php submits.
   */
  public static function validateSiteTree(array &$element, FormStateInterface $form_state) {
    $primary_field = $element['#tide_primary_field'] ?? NULL;
    $site_field = $element['#tide_site_field'] ?? NULL;
    if (!$primary_field || !$site_field) {
      return;
    }

    $primary_tid = static::extractTargetIds($form_state->getValue($primary_field));
    $primary_tid = reset($primary_tid);
    // When no Primary Site is selected, the field's own required validation
    // reports it; there is nothing to cross-check here.
    if (empty($primary_tid)) {
      return;
    }

    $site_tids = static::extractTargetIds($form_state->getValue($site_field));

    if (!in_array($primary_tid, $site_tids)) {
      $form_state->setError($element, t('The Site field must include the selected Primary Site.'));
      return;
    }

    /** @var \Drupal\tide_site_restriction\Helper $helper */
    $helper = \Drupal::service('tide_site_restriction.helper');
    foreach ($site_tids as $site_tid) {
      $trail = $helper->getSiteTrail($site_tid) ?: [];
      if (!in_array($primary_tid, $trail)) {
        $form_state->setError($element, t('Selected Site must belong to the selected Primary Site.'));
        return;
      }
    }

    // Besides the Primary Site itself, at most one child (at any depth) may be
    // selected.
    $children = array_diff($site_tids, [$primary_tid]);
    if (count($children) > 1) {
      $form_state->setError($element, t('You may select at most one Site in addition to the Primary Site.'));
    }
  }

  /**
   * Normalises a submitted reference field value into a list of target ids.
   *
   * @param mixed $value
   *   The raw form value (scalar, list of scalars or list of items).
   *
   * @return array
   *   The list of non-empty target ids as strings.
   */
  protected static function extractTargetIds($value) {
    $ids = [];
    if (is_array($value)) {
      foreach ($value as $item) {
        if (is_array($item) && isset($item['target_id'])) {
          $ids[] = $item['target_id'];
        }
        elseif (is_scalar($item)) {
          $ids[] = $item;
        }
      }
    }
    elseif (is_scalar($value) && $value !== '') {
      $ids[] = $value;
    }
    return array_values(array_filter(array_map('strval', $ids), function ($id) {
      return $id !== '' && $id !== '0' && $id !== '_none';
    }));
  }

  /**
   * {@inheritdoc}
   */
  protected function getOptions(FieldableEntityInterface $entity) {
    $options = parent::getOptions($entity);
    $selected = [];
    if ($this->helper->canBypassRestriction($this->currentUser)) {
      return $options;
    }
    $field_name = $this->fieldDefinition->getName();
    if ($entity->hasField($field_name) && !$entity->get($field_name)->isEmpty()) {
      $values = $entity->get($field_name)->getValue();
      $selected = array_column($values, 'target_id');
    }
    return $this->userOptionsFilter($this->currentUser, $options, $selected);
  }

  /**
   * Filters options based on user's permissions.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The user.
   * @param array $options
   *   The default options.
   * @param array $selected
   *   The selected options.
   *
   * @return array
   *   The options.
   */
  protected function userOptionsFilter(AccountProxyInterface $account, array $options, array $selected) {
    $allSites = array_merge($this->helper->getUserSitesTrail(User::load($account->id())), $selected);
    $options = array_intersect_key($options, array_flip($allSites));
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $results = parent::massageFormValues($values, $form, $form_state);
    // If the user could bypass the restrictions, returns results directly.
    if ($this->helper->canBypassRestriction($this->currentUser)) {
      return $results;
    }
    // If the form object was not an entity form, eg, entity browser form,
    // Returns results directly.
    $options = [];
    if ($form_state->getFormObject() instanceof EntityFormInterface) {
      $entity = $form_state->getFormObject()->getEntity();
      // Calculates the results if the user could not bypass the restrictions
      // and the FormObject was entity form.
      if (!$entity->isNew() && $this->multiple) {
        /** @var \Drupal\Core\Entity\EntityStorageInterface|\Drupal\Core\Entity\RevisionableStorageInterface $entityStorage */
        $entityStorage = $this->entityTypeManager->getStorage($entity->getEntityTypeId());
        $latestRevisionId = $entityStorage->getLatestRevisionId($entity->id());
        if ($latestRevisionId) {
          /** @var \Drupal\Core\Entity\EntityInterface|\Drupal\Core\Entity\RevisionableInterface $latest */
          $last_revision = $entityStorage->loadRevision($latestRevisionId);
        }
        $revision_value = $last_revision->get($this->fieldDefinition->getName())->getValue();
        $user_sites = $this->helper->getUserSites(User::load($this->currentUser->id()));
        $diff = array_diff(array_column($revision_value, 'target_id'), $user_sites);
        $results = array_unique(array_merge(array_column($results, 'target_id'), $diff));
        $chunks = array_chunk($results, 1);
        $key = ['target_id'];
        // Reassemble the results array.
        $results = array_map(function ($chunk) use ($key) {
          return array_combine($key, $chunk);
        }, $chunks);
      }
    }
    // Get Parent Ids.
    foreach ($results as $result) {
      $parents = $this->helper->getSiteTrail($result['target_id']);
      $parentSiteId = reset($parents);
      if ($parentSiteId == $result['target_id']) {
        continue;
      }
      $options[] = ['target_id' => $parentSiteId];
    }
    return array_unique(array_merge($results, $options), SORT_REGULAR);
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return TideSiteFields::isSiteField($field_definition->getName());
  }

}
