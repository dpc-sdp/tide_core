@tide @jsonapi @suggest
Feature: JSON API Webform

  Ensure that the Event Submission form is exposed via JSON API.

  Scenario: Send GET request to retrieve the Content Rating form
    When I send a GET request to "/api/v1/webform/webform?filter[drupal_internal__id][value]=tide_event_submission"
    Then the response code should be 200
    And the response should be in JSON
    And the JSON node "meta.count" should exist
    And the JSON node "data" should exist
    And the JSON node "data[0].type" should be equal to "webform--webform"
    And the JSON node "data[0].id" should exist
    And the JSON node "data[0].attributes.drupal_internal__id" should be equal to "tide_event_submission"
    And the JSON node "data[0].attributes.elements" should exist
    And the JSON node "data[0].attributes.elements.name_of_event" should exist
    And the JSON node "data[0].attributes.elements.category" should exist
    And the JSON node "data[0].attributes.elements.location" should exist
    And the JSON node "data[0].attributes.elements.description" should exist
    And the JSON node "data[0].attributes.elements.requirements" should exist
    And the JSON node "data[0].attributes.elements.open_date" should exist
    And the JSON node "data[0].attributes.elements.close_date" should exist
    And the JSON node "data[0].attributes.elements.free" should exist
    And the JSON node "data[0].attributes.elements.price_from" should exist
    And the JSON node "data[0].attributes.elements.price_to" should exist
    And the JSON node "data[0].attributes.elements.website_url_for_event_information" should exist
    And the JSON node "data[0].attributes.elements.website_url_for_booking" should exist
    And the JSON node "data[0].attributes.elements.required_contact_details" should exist
    And the JSON node "data[0].attributes.elements.contact_person" should exist
    And the JSON node "data[0].attributes.elements.contact_email_address" should exist
    And the JSON node "data[0].attributes.elements.contact_telephone_number" should exist
    And the JSON node "data[0].attributes.elements.privacy_statement_disclaimer" should exist
    And the JSON node "data[0].attributes.elements.agree_privacy_statement" should exist
