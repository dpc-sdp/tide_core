<?php

namespace Drupal\tide_webform\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformSubmissionForm;

/**
 * Hook implementations for the tide webform module.
 */
class TideWebformHooks {

  /**
   * Implements hook_config_ignore_settings_alter().
   */
  #[Hook('config_ignore_settings_alter')]
  public function configIgnoreSettingsAlter(array &$settings) {
    // Ignore the Content Rating webform so that it won't be reverted
    // during config sync.
    $settings[] = 'webform.webform.tide_webform_content_rating';
  }

  /**
   * Implements hook_form_alter().
   */
  #[Hook('form_alter')]
  public function formAlter(&$form, FormStateInterface $form_state, $form_id) {
    if ($form_id == 'webform_settings_confirmation_form' && isset($form['confirmation_type']['confirmation_type']['#options'])) {
      $allowed_confirmation_types = [
        'page' => t('Page (displays the confirmation message)'),
        'inline' => t('Inline (replaces the webform with the confirmation message)'),
        'url' => t('URL (redirects to a custom path or URL)'),
      ];
      $disallowed_confirmation_types = ['message', 'modal', 'url_message', 'none'];
      $form['confirmation_type']['confirmation_type']['#options'] = array_merge(
        $form['confirmation_type']['confirmation_type']['#options'],
        $allowed_confirmation_types
      );
      foreach ($disallowed_confirmation_types as $type) {
        if (isset($form['confirmation_type']['confirmation_type']['#options'][$type])) {
          unset($form['confirmation_type']['confirmation_type']['#options'][$type]);
        }
      }
    }
    if (!empty($form['#webform_id']) && $form['#webform_id'] == 'tide_webform_content_rating') {
      $form['#attached']['library'][] = 'tide_webform/content_rating';

      // Restricting access for anonymous user from BE.
      if (\Drupal::currentUser()->isAnonymous()) {
        $form['#access'] = FALSE;
        $form['#markup'] = t('Access Denied.');
      }
    }

    if ($form_id == 'webform_ui_element_form') {
      $field_type = $form['properties']['type']['#value'] ?? '';
      $form['#after_build'][] = 'tide_webform_webform_ui_element_form_after_build';
      $form['#attached']['library'][] = 'tide_webform/webform';
      if (isset($form['properties']['form']['length_container']['maxlength']) && array_key_exists('#default_value', $form['properties']['form']['length_container']['maxlength']) && $form['properties']['type']['#value'] === 'textfield') {
        $default_value = NestedArray::getValue($form,
          [
            'properties',
            'form',
            'length_container',
            'maxlength',
            '#default_value',
          ]);
        if ($default_value === NULL) {
          NestedArray::setValue($form,
            [
              'properties',
              'form',
              'length_container',
              'maxlength',
              '#default_value',
            ],
            '255');
        }
      }
      // Check if the field type is either 'select' or 'term_select'.
      if (in_array($field_type, ['select', 'webform_term_select'])) {
        $current_path = \Drupal::service('path.current')->getPath();
        $matches = [];
        if (preg_match('/webform\/manage\/([^\/]+)\/element\/([^\/]+)/', $current_path, $matches)) {
          // Extract the Parent Webform ID.
          $webform_id = $matches[1];
        }
        // Fetch already saved default value from config.
        $element_key = $form['properties']['element']['key']['#default_value'];
        $element_settings = \Drupal::configFactory()->get('webform.settings')->get('element_settings') ?: [];
        if (isset($element_key) && isset($element_settings[$webform_id][$element_key]['searchable'])) {
          // Get the 'searchable' value for the specified element.
          $searchable_value = $element_settings[$webform_id][$element_key]['searchable'];
        }
        else {
          $searchable_value = 0;
        }
        $form['properties']['element']['searchable'] = [
          '#type' => 'checkbox',
          '#title' => t('Enable search'),
          '#description' => t('Check this box to allow select fields to opt into the searchable display.'),
          '#default_value' => $searchable_value,
        ];

        $form['#submit'][] = 'tide_webform_webform_ui_element_form_submit';
        $form['#webform_id'] = $form_id;
        $form['#parent_id'] = $webform_id;
      }
    }

    if ($form_id == 'webform_submission_filter_form') {
      $options = $form['filter']['state']['#options'];
      $options['processed'] = 'Exported [' . tide_webform_submission_filter_query(1) . ']';
      $options['unprocessed'] = 'New [' . tide_webform_submission_filter_query(0) . ']';
      $form['filter']['state']['#options'] = $options;
    }

    if (strstr($form_id, 'webform_submission') && strstr($form_id, 'notes_form')) {
      $webform_submission = \Drupal::routeMatch()->getParameter('webform_submission');
      $form['processed'] = [
        '#type' => 'checkbox',
        '#title' => t('Has this submission been exported?'),
        '#description' => t('If checked, this submission will be flagged as exported. If unchecked, it will be flagged as a new submission.'),
        '#default_value' => tide_webform_get_processed($webform_submission),
        '#return_value' => TRUE,
      ];
    }

    if ($form_id == 'webform_results_export') {
      $form['export']['download']['unprocessed_submissions'] = [
        '#type' => 'checkbox',
        '#title' => t('Only download new submissions'),
        '#default_value' => TRUE,
      ];
      $form['export']['download']['process_submissions'] = [
        '#type' => 'checkbox',
        '#title' => t('Download submissions marked as exported'),
        '#default_value' => TRUE,
      ];

      $form['#submit'][] = 'tide_webform_form_webform_results_export_form_submit';

    }

  }

  /**
   * Implements hook_form_FORM_ID_alter().
   *
   * @see \Drupal\webform\Form\AdminConfig\WebformAdminConfigElementsForm::buildForm()
   */
  #[Hook('form_webform_admin_config_elements_form_alter')]
  public function formWebformAdminConfigElementsFormAlter(&$form, FormStateInterface $form_state, $form_id) {
    $config = \Drupal::config('tide_webform.defaults');
    $form['privacy_statement'] = [
      '#type' => 'details',
      '#title' => t('Default Privacy statement'),
      '#description' => t('Set the default values for new Privacy Statement webform elements.'),
      '#description_display' => 'before',
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    $form['privacy_statement']['add_to_new_form'] = [
      '#type' => 'checkbox',
      '#title' => t('Add a default Privacy Statement element when creating a new webform'),
      '#default_value' => $config->get('privacy_statement.add_to_new_form'),
    ];

    $form['privacy_statement']['agreement'] = [
      '#type' => 'textfield',
      '#title' => t('Title'),
      '#description' => t('This message will be displayed with a checkbox on frontend.'),
      '#default_value' => $config->get('privacy_statement.agreement'),
      '#required' => TRUE,
    ];

    $form['privacy_statement']['heading'] = [
      '#type' => 'textfield',
      '#title' => t('Heading'),
      '#default_value' => $config->get('privacy_statement.heading'),
    ];

    $form['privacy_statement']['content'] = [
      '#type' => 'webform_html_editor',
      '#title' => t('Content'),
      '#default_value' => $config->get('privacy_statement.content'),
      '#required' => TRUE,
    ];

    $form['privacy_statement']['token_tree_link'] = \Drupal::service('webform.token_manager')->buildTreeElement();

    $form['#submit'][] = 'tide_webform_form_webform_admin_config_elements_form_submit';
  }

  /**
   * Implements hook_entity_base_field_info_alter().
   *
   * @see \Drupal\webform\Entity\WebformSubmission::baseFieldDefinitions()
   */
  #[Hook('entity_base_field_info_alter')]
  public function entityBaseFieldInfoAlter(&$fields, EntityTypeInterface $entity_type) {
    if ($entity_type->id() == 'webform_submission') {
      // Expose the data attribute as a field.
      if (empty($fields['data'])) {
        $fields['data'] = BaseFieldDefinition::create('string_long')
          ->setName('data')
          ->setLabel('Data')
          ->setDescription(t('Webform Submission Data'))
          ->setDefaultValue('')
          ->setTargetEntityTypeId('webform_submission')
          ->setComputed(TRUE);
      }
    }
  }

  /**
   * Implements hook_entity_base_field_info().
   *
   * @see \Drupal\webform\Entity\WebformSubmission::baseFieldDefinitions()
   */
  #[Hook('entity_base_field_info')]
  public function entityBaseFieldInfo(EntityTypeInterface $entity_type) {
    $fields = [];
    if ($entity_type->id() == 'webform_submission') {
      $fields['processed'] = BaseFieldDefinition::create('boolean')
        ->setName('Exported')
        ->setLabel(t('Exported'))
        ->setDescription(t('A flag that indicates whether the submission has been downloaded'))
        ->setDefaultValue(FALSE)
        ->setDisplayConfigurable('form', FALSE);
    }

    return $fields;
  }

  /**
   * Implements hook_ENTITY_TYPE_create_access().
   *
   * @see \Drupal\webform\WebformSubmissionAccessControlHandler::checkAccess()
   */
  #[Hook('webform_submission_create_access')]
  public function webformSubmissionCreateAccess(AccountInterface $account, array $context, $entity_bundle) {
    // Check webform create access.
    // This hook should be only invoked if the webform_submission is created
    // via JSON:API requests.
    $is_jsonapi = \Drupal::request()->attributes->get('_is_jsonapi', FALSE);
    if ($is_jsonapi) {
      $webform = Webform::load($entity_bundle);
      if ($webform) {
        /** @var \Drupal\webform\WebformAccessRulesManagerInterface $webform_access_rules_manager */
        $webform_access_rules_manager = \Drupal::service('webform.access_rules_manager');
        /** @var \Drupal\Core\Access\AccessResultInterface $webform_access */
        $webform_access = $webform_access_rules_manager->checkWebformAccess('create', $account, $webform);
        if ($webform_access->isAllowed()) {
          return $webform_access;
        }
      }
    }
    return AccessResult::neutral();
  }

  /**
   * Implements hook_form_FORM_ID_alter().
   */
  #[Hook('form_webform_add_form_alter')]
  public function formWebformAddFormAlter(&$form, FormStateInterface $form_state, $form_id) {
    /** @var \Drupal\webform\WebformEntityAddForm $form_object */
    $form_object = $form_state->getFormObject();
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $form_object->getEntity();
    if ($webform->isNew()) {
      // Expose the webform to API requests by default.
      $access_rules = $webform->getAccessRules();
      $access_rules['configuration']['roles'][] = 'anonymous';
      $access_rules['configuration']['roles'][] = 'authenticated';
      $webform->setAccessRules($access_rules);

      // Add a Privacy Statement element to the new webform.
      $default_privacy_statement = \Drupal::config('tide_webform.defaults')->get('privacy_statement');
      if (!empty($default_privacy_statement['add_to_new_form'])) {
        $elements = $webform->getElementsDecoded();
        if (empty($elements)) {
          $elements['privacy_statement'] = [
            '#type' => 'webform_privacy_statement',
            '#required' => TRUE,
            '#privacy_statement_heading' => $default_privacy_statement['heading'] ?? '',
            '#privacy_statement_content' => $default_privacy_statement['content'] ?? '',
            '#title' => $default_privacy_statement['agreement'] ?? t('I have read and understood the privacy statement.'),
          ];

          $webform->setElements($elements);
        }
      }

      // Make default submit button name.
      if (!isset($elements['actions'])) {
        $elements['actions'] = [
          '#type' => 'webform_actions',
          '#title' => 'Submit',
        ];

        $webform->setElements($elements);
      }
    }

    $form_object->setEntity($webform);
  }

  /**
   * Implements hook_ENTITY_TYPE_presave().
   */
  #[Hook('webform_submission_presave')]
  public function webformSubmissionPresave(WebformSubmission $webform_submission) {
    // Preparing confirmation message.
    $webform = $webform_submission->getWebform();
    if ($webform->getElement('confirmation_message') !== NULL) {
      $webform_submission->set('notes', $webform_submission->uuid());
    }
    $elements = $webform_submission->getWebform()->getElementsDecoded();
    // SDPAP-5181 Front end submits the value as numeric.
    // Triggers invalid validation error from core.
    // See /Drupal/Core/Form/FormValidator.php $is_empty_value.
    $submission_data = $webform_submission->getData();
    foreach ($elements as $key => $element) {
      // It will not validate for multi step form.
      if (isset($element['#type']) && $element['#type'] == 'wizard_page') {
        return;
      }
      // It will not validate for textarea if both maxlenth and counter.
      if (isset($element['#type']) && $element['#type'] === 'textarea') {
        if (isset($element['#counter_type']) && isset($element['#maxlength'])) {
          return;
        }
      }
      if (isset($element['#type']) && $element['#type'] == 'number') {
        if (is_numeric($submission_data[$key])) {
          $submission_data[$key] = strval($submission_data[$key]);
          $webform_submission->setData($submission_data);
        }
      }
      // SDPAP-6627 temporary fix until contrib module patch is created.
      // Validation isolated to specific webform.
      // @todo refactior the code and submit a patch.
      // Look at webform issue - 3170790.
      // Server side validation from API submission.
      if (isset($element['#required']) && $element['#required'] == TRUE && $webform->get('id') === 'tide_webform_content_rating') {
        if (empty($submission_data[$key]) && $submission_data[$key] == "") {
          // Throw an exception to prevent saving the webform.
          throw new \Exception("The required field '{$element['#title']}' is empty.");
        }
      }
    }

    $errors = WebformSubmissionForm::validateWebformSubmission($webform_submission);
    if ($errors) {
      foreach ($elements as $key => $element) {
        if (isset($errors[$key]) && !empty($errors[$key])) {
          throw new \Exception($errors[$key]);
        }
      }
    }
  }

  /**
   * Implements hook_entity_type_alter().
   */
  #[Hook('entity_type_alter')]
  public function entityTypeAlter(array &$entity_types) {
    /** @var \Drupal\Core\Entity\EntityTypeInterface[] $entity_types */
    $entity_types['webform_submission']->setListBuilderClass('Drupal\tide_webform\TideWebformSubmissionListBuilder');
  }

  /**
   * Alter a webform element's default properties.
   *
   * @param array &$properties
   *   An associative array containing an element's default properties.
   * @param array $definition
   *   The webform element's definition.
   *
   * @see webform_example_element_properties.module
   */
  #[Hook('webform_element_default_properties_alter')]
  public function webformElementDefaultPropertiesAlter(array &$properties, array &$definition) {
    if (($definition["id"] === "textfield") && (empty($properties["counter_type"]))) {
      $properties["counter_type"] = "character";
      $properties["counter_maximum"] = 255;
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_update().
   */
  #[Hook('webform_update')]
  public function webformUpdate(EntityInterface $entity) {
    \Drupal::cache()->invalidate('webform_text_fields_default_maxlength');
  }

  /**
   * Implements hook_webform_third_party_settings_form_alter().
   */
  #[Hook('webform_third_party_settings_form_alter')]
  public function webformThirdPartySettingsFormAlter(array &$form, FormStateInterface $form_state) {
    $webform = $form_state->getFormObject()->getEntity();
    $third_party_settings = $webform->getThirdPartySettings('tide_webform');
    $user_input = $form_state->getUserInput();
    $taxonomy_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    $captcha_type = NULL;
    $allowed_values = [];
    try {
      $field_definition = \Drupal::entityTypeManager()->getStorage('field_storage_config')->load('taxonomy_term.field_captcha_type');
      if ($field_definition) {
        $allowed_values = $field_definition->getSetting('allowed_values');
      }
      else {
        throw new \Exception('Field storage configuration not found for taxonomy_term.field_captcha_type');
      }
    }
    catch (\Exception $e) {
      \Drupal::messenger()->addWarning('Unable to load CAPTCHA type options. Please contact the site administrator.');
      \Drupal::logger('tide_webform')
        ->error('Error loading CAPTCHA type options for webform @webform: @message',
               ['@webform' => $webform->id(), '@message' => $e->getMessage()]);
      return;
    }
    if (isset($third_party_settings['captcha_type'])) {
      $captcha_type = $third_party_settings['captcha_type'];
    }
    if (isset($user_input['third_party_settings']['tide_webform']['captcha_type'])) {
      $captcha_type = $user_input['third_party_settings']['tide_webform']['captcha_type'];
    }
    $query = $taxonomy_storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('vid', 'captcha_widgets')
      ->condition('field_captcha_type', $captcha_type);
    $tids = $query->execute();
    $terms = $taxonomy_storage->loadMultiple($tids);
    $options = array_map(function (Term $term) {
      return $term->label() . ' (' . $term->id() . ')';
    }, $terms);

    $form['third_party_settings']['tide_webform'] = [
      '#type' => 'fieldset',
      '#title' => t('Tide webform CAPTCHA'),
      '#open' => TRUE,
    ];

    $form['third_party_settings']['tide_webform']['enable_captcha'] = [
      '#type' => 'checkbox',
      '#default_value' => !empty($third_party_settings['enable_captcha']) ? $third_party_settings['enable_captcha'] : NULL,
      '#title' => t('Enable captcha'),
    ];

    $form['third_party_settings']['tide_webform']['captcha_type'] = [
      '#type' => 'select',
      '#title' => t('Captcha type'),
      '#options' => [
        'all' => '-Select-',
      ] + $allowed_values,
      '#default_value' => !empty($third_party_settings['captcha_type']) ? $third_party_settings['captcha_type'] : NULL,
      '#ajax' => [
        'callback' => '_tide_webform_type_dropdown_callback',
        'wrapper' => 'captcha-type-dropdown-container',
      ],
    ];
    $score_threshold = NULL;
    if (isset($third_party_settings['score_threshold']) && $third_party_settings['score_threshold'] === 0.0) {
      $score_threshold = '0.0';
    }
    elseif (!empty($third_party_settings['score_threshold'])) {
      $score_threshold = (string) $third_party_settings['score_threshold'];
    }

    $form['third_party_settings']['tide_webform']['score_threshold'] = [
      '#type' => 'textfield',
      '#title' => t('Score threshold (reCAPTCHA v3)'),
      '#size' => 2,
      '#maxlength' => 3,
      '#number_type' => 'decimal',
      '#element_validate' => ['_tide_webform_threshold_validate'],
      '#states' => [
        'visible' => [
          ':input[name="third_party_settings[tide_webform][captcha_type]"]' => ['value' => 'google_recaptcha_v3'],
        ],
      ],
      '#default_value' => $score_threshold,
      '#description' => 'Enter a value between 0.0 and 1.0. Use only one decimal place (e.g., 0.0, 0.5, 1.0).',
    ];

    $form['third_party_settings']['tide_webform']['captcha_type_dropdown_container'] = [
      '#type' => 'fieldset',
      '#attributes' => ['id' => 'captcha-type-dropdown-container'],
    ];

    $form['third_party_settings']['tide_webform']['captcha_type_dropdown_container']['captcha_details'] = [
      '#type' => \Drupal::moduleHandler()->moduleExists('select2') ? 'select2' : 'select',
      '#title' => ('Site key'),
      '#target_type' => 'taxonomy_term',
      '#options' => $options,
      '#tags' => TRUE,
      '#select2' => [
        'allowClear' => TRUE,
        'dropdownAutoWidth' => FALSE,
        'width' => '20%',
        'closeOnSelect' => FALSE,
      ],
      '#default_value' => !empty($third_party_settings['captcha_details']['term_id']) ? $third_party_settings['captcha_details']['term_id'] : NULL,
      '#selection_settings' => [
        'target_bundles' => ['captcha_widgets'],
      ],
    ];

    $form['#validate'][] = '_tide_webform_form_validate';
  }

}
