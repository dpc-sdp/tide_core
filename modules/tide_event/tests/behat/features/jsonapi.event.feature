@tide @jsonapi @suggest @install:tide_event
Feature: JSON API Webform

  Ensure that the Event Submission form is exposed via JSON API.

  Scenario: Send GET request to retrieve the Content Rating form
    When I request "/api/v1/webform/webform?filter[drupal_internal__id][value]=tide_event_submission" using HTTP GET
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
            "id": "@variableType(string)",
            "attributes": {
              "drupal_internal__id": "tide_event_submission",
              "elements": {
                "name_of_event": "@variableType(object)",
                "category": "@variableType(object)",
                "location": "@variableType(object)",
                "description": "@variableType(object)",
                "requirements": "@variableType(object)",
                "open_date": "@variableType(object)",
                "close_date": "@variableType(object)",
                "free": "@variableType(object)",
                "price_from": "@variableType(object)",
                "price_to": "@variableType(object)",
                "website_url_for_event_information": "@variableType(object)",
                "website_url_for_booking": "@variableType(object)",
                "required_contact_details": "@variableType(object)",
                "contact_person": "@variableType(object)",
                "contact_email_address": "@variableType(object)",
                "contact_telephone_number": "@variableType(object)",
                "privacy_statement_disclaimer": "@variableType(object)",
                "agree_privacy_statement": "@variableType(object)"
              }
            }
          }
        ]
      }
      """