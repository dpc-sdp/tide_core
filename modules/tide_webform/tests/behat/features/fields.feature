@tide
Feature: 'Show Content Rating?' field

  Ensure that the 'Show Content Rating?' field exist

  @api @suggest
  Scenario: Page content type has the expected fields (and labels where we can use them).
    Given I am logged in as a user with the "create test content" permission
    When I visit "node/add/test"
    And save screenshot

    And I see field "Show Content Rating?"
    And I should see an "input#edit-field-show-content-rating-value" element
    And I should not see an "input#edit-field-show-content-rating-value.required" element
