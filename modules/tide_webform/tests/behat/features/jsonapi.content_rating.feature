@jsonapi @suggest
Feature: JSON API Webform

  Ensure that the Content Rating form is exposed via JSON API.

  Scenario: Send GET request to retrieve the Content Rating form
    When I request "/api/v1/webform/webform?filter[drupal_internal__id][value]=tide_webform_content_rating" using HTTP GET
    Then the response code is 200
    And the response body contains JSON:
      """
      {
        "meta": {
          "count": "@variableType(integer)"
        },
        "data": [
          {
            "type": "webform--webform",
            "attributes": {
              "drupal_internal__id": "tide_webform_content_rating",
              "elements": {
                "url": "@variableType(object)",
                "was_this_page_helpful": "@variableType(object)",
                "comments": "@variableType(object)"
              }
            }
          }
        ]
      }
      """

  Scenario: Send POST request to the Content Rating form
    Given the "Content-Type" request header is "application/vnd.api+json"
    And the request body is:
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
    When I request "/api/v1/webform_submission/tide_webform_content_rating" using HTTP POST
    Then the response code is 201
    And the response body contains JSON:
      """
      {
        "data": {
          "type": "webform_submission--tide_webform_content_rating",
          "id": "@variableType(string)",
          "attributes": {
            "serial": "@variableType(integer)"
          }
        }
      }
      """
