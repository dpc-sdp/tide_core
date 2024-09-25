@tide @jsonapi @suggest
Feature: JSON API Webform

  Ensure that the Grant Submission form is exposed via JSON API.

  Scenario: Send GET request to retrieve the Content Rating form
    When I send a GET request to "/api/v1/webform/webform?filter[drupal_internal__id][value]=tide_grant_submission"
    Then the response code should be 200
    And the response should be in JSON
    And the JSON node "meta.count" should exist
    And the JSON node "data" should exist
    And the JSON node "data[0].type" should be equal to "webform--webform"
    And the JSON node "data[0].id" should exist
    And the JSON node "data[0].attributes.drupal_internal__id" should be equal to "tide_grant_submission"
    And the JSON node "data[0].attributes.elements" should exist
    And the JSON node "data[0].attributes.elements.name_of_grant_or_program" should exist
    And the JSON node "data[0].attributes.elements.describe_the_grant_or_program" should exist
    And the JSON node "data[0].attributes.elements.topic" should exist
    And the JSON node "data[0].attributes.elements.who_is_the_grant_or_program_for_" should exist
    And the JSON node "data[0].attributes.elements.funding_level_from" should exist
    And the JSON node "data[0].attributes.elements.funding_level_to" should exist
    And the JSON node "data[0].attributes.elements.website_url_to_apply_for_grant_or_program" should exist
    And the JSON node "data[0].attributes.elements.website_url_for_grant_or_program_information" should exist
    And the JSON node "data[0].attributes.elements.required_contact_details" should exist
    And the JSON node "data[0].attributes.elements.contact_person" should exist
    And the JSON node "data[0].attributes.elements.department_agency_or_provider_organisation" should exist
    And the JSON node "data[0].attributes.elements.contact_email_address" should exist
    And the JSON node "data[0].attributes.elements.contact_telephone_number" should exist
    And the JSON node "data[0].attributes.elements.privacy_statement_disclaimer" should exist
    And the JSON node "data[0].attributes.elements.agree_privacy_statement" should exist
    And the JSON node "data[0].attributes.elements.open_date" should exist
    And the JSON node "data[0].attributes.elements.close_date" should exist
