<?php

namespace Drupal\tide_oauth\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Form\FormStateInterface;

/**
 * Hook implementations for the tide oauth module.
 */
class TideOauthHooks {

  /**
   * Implements hook_tide_api_jsonapi_custom_query_parameters_alter().
   */
  #[Hook('tide_api_jsonapi_custom_query_parameters_alter')]
  public function tideApiJsonapiCustomQueryParametersAlter(&$custom_params) {
    // DefaultExceptionHtmlSubscriber may append those query to JSON:API
    // requests if the authorisation is invalid.
    $custom_params[] = 'destination';
    $custom_params[] = '_exception_statuscode';
  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme($existing, $type, $theme, $path) {
    return [
      'consumer__with_field_long_description' => [
        'template' => 'consumer--with_field_long_description',
        'base hook' => 'consumer',
      ],
    ];
  }

  /**
   * Implements hook_theme_suggestions_HOOK_alter().
   */
  #[Hook('theme_suggestions_consumer_alter')]
  public function themeSuggestionsConsumerAlter(array &$suggestions, array $variables) {
    /** @var \Drupal\consumers\Entity\Consumer $consumer */
    $consumer = $variables['elements']['#consumer'] ?? NULL;
    if (!$consumer) {
      return;
    }
    if ($consumer->hasField('field_long_description')) {
      $suggestions[] = 'consumer__with_field_long_description';
    }
  }

  /**
   * Implements hook_form_BASE_FORM_ID_alter().
   *
   * Hide the Permissions section in Editorial Preview consumer.
   *
   * @see \Drupal\simple_oauth\Controller\Oauth2AuthorizeForm::buildForm()
   */
  #[Hook('form_simple_oauth_authorize_form_alter')]
  public function formSimpleOauthAuthorizeFormAlter(&$form, FormStateInterface $form_state, $form_id) {
    $client_uuid = \Drupal::request()->get('client_id');
    if (!$client_uuid) {
      return;
    }
    /** @var \Drupal\consumers\ConsumerStorage $consumer_storage */
    $consumer_storage = \Drupal::entityTypeManager()->getStorage('consumer');
    /** @var \Drupal\consumers\Entity\Consumer[] $consumers */
    $consumers = $consumer_storage->loadByProperties(['uuid' => $client_uuid]);
    if (empty($consumers)) {
      return;
    }
    $consumer = reset($consumers);
    if ($consumer->machine_name->value === 'editorial_preview') {
      $form['scopes']['#access'] = FALSE;
    }

    $form['submit']['#value'] = t('Grant access');
  }

}
