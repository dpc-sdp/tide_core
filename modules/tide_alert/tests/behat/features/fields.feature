@tide @skipped
# @TODO remove @skipped once the module is extracted to its own repo.
Feature: Fields for Alert content type

  Ensure that Alert content has the expected fields.

  @api @javascript
  Scenario: The content type has the expected fields (and labels where we can use them).
    Given I am logged in as a user with the "create grant content" permission
    When I visit "node/add/alert"
    And save screenshot
    Then I see field "Title"
    And I should see an "input#edit-title-0-value.required" element

    And I see field "Alert Type"
    And I should see an "input#edit-field-alert-type-0-target-id" element
    And I should see an "input#edit-field-alert-type-0-target-id" element

    And I see field "URL"
    And I should see an "input#edit-field-call-to-action-0-uri" element
    And I should see an "input#edit-field-call-to-action-0-uri" element
