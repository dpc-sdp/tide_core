<?php

namespace Drupal\tide_site_alert\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Form\FormStateInterface;

/**
 * Hook implementations for the tide site alert module.
 */
class TideSiteAlertHooks {

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme() {
    return [
      'site_alert' => [
        'template' => 'tide-site-alert',
      ],
    ];
  }

  /**
   * Implements hook_form_FORM_ID_alter().
   */
  #[Hook('form_site_alert_form_alter')]
  public function formSiteAlertFormAlter(&$form, FormStateInterface $form_state, $form_id) {
    $options = [
      'Upcoming release notice:'          => 'Upcoming release notice:',
      'Upcoming hotfix notice:'           => 'Upcoming hotfix notice:',
      'Upcoming site maintenance notice:' => 'Upcoming site maintenance notice:',
      'Upcoming planned outage notice:'   => 'Upcoming planned outage notice:',
      'Hotfix in progress:'               => 'Hotfix in progress:',
      'Site maintenance in progress:'     => 'Site maintenance in progress:',
      'Outage in progress:'               => 'Outage in progress:',
      'Content freeze in progress:'       => 'Content freeze in progress:',
      'Successful post-release notice:'   => 'Successful post-release notice:',
      'Unsuccessful post-release notice:' => 'Unsuccessful post-release notice:',
      'Post-outage notice:'               => 'Post-outage notice:',
      'Custom notice:'                    => 'Custom notice:',
    ];
    $form['suggested_labels'] = [
      '#type'         => 'select',
      '#title'        => t('Suggested labels'),
      '#options'      => $options,
      '#empty_option' => t('- Select -'),
      '#ajax'         => [
        'callback' => 'tide_site_alert_textfield_callback',
        'wrapper'  => 'suggested-labels',
        'effect'   => 'fade',
      ],
    ];
    $form['label']['widget'][0]['value']['#prefix']
      = '<div id="suggested-labels">';
    $form['label']['widget'][0]['value']['#suffix'] = '</div>';
  }

}
