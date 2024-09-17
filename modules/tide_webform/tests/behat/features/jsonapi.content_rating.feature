@jsonapi @suggest
Feature: JSON API Webform

  Ensure that the Content Rating form is exposed via JSON API.

  Scenario: Send GET request to retrieve the Content Rating form
    When I send a GET request to "/api/v1/webform/webform?filter[drupal_internal__id][value]=tide_webform_content_rating"
    Then the response code should be 200
    And the response should be in JSON
    And the JSON node "meta.count" should exist
    And the JSON node "data" should exist
    And the JSON node "data[0].type" should be equal to "webform--webform"
    And the JSON node "data[0].attributes.drupal_internal__id" should be equal to "tide_webform_content_rating"
    And the JSON node "data[0].attributes.elements" should exist
    And the JSON node "data[0].attributes.elements.url" should exist
    And the JSON node "data[0].attributes.elements.was_this_page_helpful" should exist
    And the JSON node "data[0].attributes.elements.comments" should exist
    And the JSON node "data[0].attributes.elements.comments.#maxlength" should be equal to "255"

  Scenario: Send POST request to the Content Rating form
    When I add "Content-Type" header equal to "application/vnd.api+json"
    Then I send a POST request to "/api/v1/webform_submission/tide_webform_content_rating" with body:
        """
        {
          "data": {
            "type": "webform_submission--tide_webform_content_rating",
            "attributes": {
              "remote_addr": "1.2.3.4",
              "data": "url: '/home'\nwas_this_page_helpful: 'Yes'\ncomments: 'TEST\n Content Rating comment'"

            }
          }
        }
        """
    Then the response code should be 201
    And the response should be in JSON
    And the JSON node "data.type" should be equal to "webform_submission--tide_webform_content_rating"
    And the JSON node "data.id" should exist
    And the JSON node "data.attributes.serial" should exist
