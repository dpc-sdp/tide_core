@tide
Feature: Fields for external site link content type

  Ensure that external site link content has the expected fields.

  @api
  Scenario: The content type has the expected fields (and labels where we can use them).
    Given I am logged in as a user with the "create external_site_link content" permission
    When I visit "node/add/external_site_link"
    And save screenshot

    Then I see field "Title"
    And I should see an "input#edit-title-0-value.required" element

    And I see field "Summary"
    And I should see a "textarea#edit-field-landing-page-summary-0-value.required" element

    And I see field "Website URL"
    And I should see an "input#edit-field-node-link-0-uri.required" element

    And the "#edit-field-featured-image" element should contain "Select Images"
    And I should see an "input#edit-field-featured-image-entity-browser-entity-browser-open-modal" element

    And I see field "Keywords"
    And I should see an "input#edit-field-content-keywords-0-value" element
    And I should not see an "input#edit-field-content-keywords-0-value.required" element
