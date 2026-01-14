@tide @jsonapi @suggest @install:tide_grant
Feature: JSON API Webform

  Ensure that the Grant Submission form is exposed via JSON API.

  Scenario: Send GET request to retrieve the Content Rating form
    When I request "/api/v1/webform/webform?filter[drupal_internal__id][value]=tide_grant_submission" using HTTP GET
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
              "drupal_internal__id": "tide_grant_submission",
              "elements": {
                "name_of_grant_or_program": "@variableType(object)",
                "describe_the_grant_or_program": "@variableType(object)",
                "topic": "@variableType(object)",
                "who_is_the_grant_or_program_for_": "@variableType(object)",
                "funding_level_from": "@variableType(object)",
                "funding_level_to": "@variableType(object)",
                "website_url_to_apply_for_grant_or_program": "@variableType(object)",
                "website_url_for_grant_or_program_information": "@variableType(object)",
                "required_contact_details": "@variableType(object)",
                "contact_person": "@variableType(object)",
                "department_agency_or_provider_organisation": "@variableType(object)",
                "contact_email_address": "@variableType(object)",
                "contact_telephone_number": "@variableType(object)",
                "privacy_statement_disclaimer": "@variableType(object)",
                "agree_privacy_statement": "@variableType(object)",
                "open_date": "@variableType(object)",
                "close_date": "@variableType(object)"
              }
            }
          }
        ]
      }
      """
