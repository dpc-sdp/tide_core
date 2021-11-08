<?php

namespace Drupal\jira_rest\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Select;
use Drupal\Core\Url;

/**
 * Provides a select form element that displays available JIRA Endpoints.
 *
 * Properties:
 * - #empty_option: The label that will be displayed to denote no selection.
 * - #empty_value: The value of the option that is used to denote no selection.
 * - #jira_endpoint_filters: An array of filters to apply to the list of Jira Endpoints.
 * - #jira_endpoint_description: A boolean value that determines if information about
 *   JIRA Endpoints are added to the element's description.
 *
 * @FormElement("jira_endpoint_select")
 */
class JiraEndpointSelect extends Select {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();
    $class = get_class($this);

    // Add a process function.
    array_unshift($info['#process'], [$class, 'processJiraEndpointSelect']);

    // Add a property for endpoint description.
    $info['#jira_endpoint_description'] = TRUE;

    return $info;
  }

  /**
   * Processes a jira endpoint select list form element.
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The processed element.
   */
  public static function processJiraEndpointSelect(array &$element, FormStateInterface $form_state, array &$complete_form) {
    // Get the list of available endpoints and define the options.
    $options = \Drupal::service('jira_endpoint.repository')->getEndpointNamesAsOptions();
    $element['#options'] = $options;

    // Prefix the default description with information about endpoints,
    // unless disabled.
    if ($element['#jira_endpoint_description']) {
      $original_description = (isset($element['#description'])) ? $element['#description'] : '';
      // @todo this causes escaping.
      $endpoint_description = t('Choose an available endpoint. If the desired endpoint is not listed, <a href=":link">create a new JIRA Endpoint</a>.', [':link' => Url::fromRoute('entity.jira_endpoint.add_form')->toString()]);
      $element['#description'] = $endpoint_description . ' ' . $original_description;
    }

    return $element;
  }

}
