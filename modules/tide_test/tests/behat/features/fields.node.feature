@tide
Feature: Fields for Test content type

  Ensure that Test content has the expected fields.

  @api @nosuggest
  Scenario: The content type has the expected fields (and labels where we can use them).
    Given I am logged in as a user with the "create test content" permission
    When I visit "node/add/test"
    And save screenshot
    Then I see field "Title"
    And I should see an "input#edit-title-0-value.required" element

    And I see field "Body"
    And I should see a "textarea#edit-body-0-value" element
    And I should see a "textarea#edit-body-0-value.required" element
