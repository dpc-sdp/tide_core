<?php

namespace Drupal\tide_core\Plugin\KeyProvider;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\key\KeyInterface;
use Drupal\key\Plugin\KeyProvider\EnvKeyProvider;

/**
 * Allows a key to be stored with multiple environment variables.
 *
 * @KeyProvider(
 *   id = "tide_multiple_env",
 *   label = @Translation("Tide Multiple environment values"),
 *   description = @Translation("The Environment key provider allows a key to be retrieved from an environment variables."),
 *   storage_method = "env",
 *   key_value = {
 *     "accepted" = FALSE,
 *     "required" = FALSE
 *   }
 * )
 */
class TideMultipleEnvKeyProvider extends EnvKeyProvider {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['env_variable'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Environment variable'),
      '#description' => $this->t('Name of the environment variable.'),
      '#required' => TRUE,
      '#default_value' => $this->getConfiguration()['env_variable'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $key_provider_settings = $form_state->getValues();
    $env_variable = $key_provider_settings['env_variable'];
    $key_with_values = explode('|', $env_variable);
    foreach ($key_with_values as $env_key) {
      $key_value = getenv($env_key);
      // Does the env variable exist.
      if ($key_value === FALSE) {
        $form_state->setErrorByName('env_variable', $this->t('The environment variable does not exist or it is empty.'));
        return;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getKeyValue(KeyInterface $key) {
    $env_variable = $this->configuration['env_variable'];
    $key_with_values = explode('|', $env_variable);
    $result = [];
    foreach ($key_with_values as $env_key) {
      $key_value = getenv($env_key);
      if ($key_value === FALSE) {
        throw new \RuntimeException(sprintf('"%s" Key doesn\'t exist.', $env_key));
      }
      $result[strtolower($env_key)] = $key_value;
    }
    $encoded_data = Json::encode($result);

    if (!$encoded_data) {
      return NULL;
    }

    if (isset($this->configuration['strip_line_breaks']) && $this->configuration['strip_line_breaks'] == TRUE) {
      $encoded_data = rtrim($encoded_data, "\n\r");
    }

    if (isset($this->configuration['base64_encoded']) && $this->configuration['base64_encoded'] == TRUE) {
      $encoded_data = base64_decode($encoded_data);
    }
    return $encoded_data;
  }

}
